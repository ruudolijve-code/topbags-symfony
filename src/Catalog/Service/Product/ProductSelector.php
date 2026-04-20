<?php

namespace App\Catalog\Service\Product;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Guide\BaggageProfile;
use App\Service\Guide\LightweightClassifier;

final class ProductSelector
{
    public function __construct(
        private ProductRepository $productRepository,
        private LightweightClassifier $lightweightClassifier,
    ) {}

    /**
     * @return array<string, Product[]>
     */
    public function select(BaggageProfile $profile): array
    {
        $result = [];

        foreach ($profile->allowedScopes as $scope) {

            $categoryIds = $profile->getCategoryIdsForScope($scope);

            if ($categoryIds === []) {
                $result[$scope] = [];
                continue;
            }

            /* ==============================================
             * 1️⃣ Basis ophalen
             * ============================================== */
            $products = $this->productRepository->findForProfileBase(
                categoryIds: $categoryIds,
                scope: $scope,
                limit: 80
            );

            if ($products === []) {
                $result[$scope] = [];
                continue;
            }

            /* ==============================================
             * 2️⃣ Volume filter (alleen hoofd-bagage)
             * ============================================== */
            if (
                in_array($scope, ['main', 'hold'], true)
                && is_array($profile->volume)
            ) {
                $filtered = $this->filterByVolume(
                    $products,
                    $profile->volume
                );

                // fallback: als niks overblijft → geen harde uitsluiting
                if ($filtered !== []) {
                    $products = $filtered;
                }
            }

            /* ==============================================
             * 3️⃣ Volume ranking (dichtst bij midden van range)
             * ============================================== */
            if (is_array($profile->volume)) {
                $products = $this->rankByVolumeFit(
                    $products,
                    $profile->volume
                );
            }

            /* ==============================================
             * 4️⃣ Lightweight ranking (secondary)
             * ============================================== */
            if ($profile->maxLightweightClass !== null) {
                $products = $this->rankByLightweight(
                    $products,
                    $profile->maxLightweightClass
                );
            }

            /* ==============================================
             * 5️⃣ Scope-based limit
             * ============================================== */
            $limit = match ($scope) {
                'personal' => 6,
                'cabin'    => 8,
                'hold'     => 8,
                'main'     => 10,
                default    => 8,
            };

            $result[$scope] = array_slice($products, 0, $limit);
        }

        return $result;
    }

    /* ==========================================================
     * 🔹 Volume hard filter
     * ========================================================== */
    private function filterByVolume(array $products, array $range): array
    {
        return array_values(array_filter($products, function (Product $p) use ($range) {

            $volume = $p->getVolumeL();

            if ($volume === null) {
                return false;
            }

            if (isset($range['min']) && $range['min'] !== null && $volume < $range['min']) {
                return false;
            }

            if (isset($range['max']) && $range['max'] !== null && $volume > $range['max']) {
                return false;
            }

            return true;
        }));
    }

    /* ==========================================================
     * 🔹 Volume ranking (belangrijk!)
     * ========================================================== */
    private function rankByVolumeFit(array $products, array $range): array
    {
        $mid = ($range['min'] + $range['max']) / 2;

        usort($products, function (Product $a, Product $b) use ($mid) {

            $volA = $a->getVolumeL() ?? 0;
            $volB = $b->getVolumeL() ?? 0;

            return abs($volA - $mid) <=> abs($volB - $mid);
        });

        return $products;
    }

    /* ==========================================================
     * 🔹 Lightweight ranking
     * ========================================================== */
    private function rankByLightweight(array $products, string $maxAllowedClass): array
    {
        usort($products, function (Product $a, Product $b) use ($maxAllowedClass) {

            $rankA = $this->scoreLightweight($a, $maxAllowedClass);
            $rankB = $this->scoreLightweight($b, $maxAllowedClass);

            return $rankA <=> $rankB;
        });

        return $products;
    }

    private function scoreLightweight(Product $product, string $maxAllowedClass): int
    {
        $class = $product->getLightweightClass();

        if ($class === null) {
            return 999;
        }

        $productRank = $this->lightweightClassifier->rank($class);
        $allowedRank = $this->lightweightClassifier->rank($maxAllowedClass);

        if ($productRank <= $allowedRank) {
            return $productRank;
        }

        return $productRank + 100;
    }
}