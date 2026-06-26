<?php

declare(strict_types=1);

namespace App\Magazine\Service;

use App\Catalog\Entity\Product;
use App\Catalog\Repository\ProductRepository;
use App\Catalog\Service\VariantImagePathResolver;

final class LightweightSuitcaseProvider
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly VariantImagePathResolver $imagePathResolver,
    ) {
    }

    public function getItems(int $limit = 8): array
    {
        $products = $this->productRepository->findLightestByGramPerLiter($limit);

        $items = [];

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            $variant = $product->getMasterVariant();

            if (!$variant) {
                foreach ($product->getVariants() as $candidate) {
                    if ($candidate->isActive()) {
                        $variant = $candidate;
                        break;
                    }
                }
            }

            if (!$variant) {
                continue;
            }

            $volume = (float) $product->getVolumeL();
            $weight = (float) $product->getWeightKg();

            if ($volume <= 0 || $weight <= 0) {
                continue;
            }

            $items[] = [
                'product' => $product,
                'variant' => $variant,
                'mediaPath' => $this->imagePathResolver->fromVariant($variant),
                'gramPerLiter' => round(($weight * 1000) / $volume, 1),
            ];
        }

        return $items;
    }
}