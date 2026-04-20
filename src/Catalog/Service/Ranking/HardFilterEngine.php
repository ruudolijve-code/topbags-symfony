<?php

namespace App\Catalog\Service\Ranking;

use App\Guide\Service\AirlineBaggageAdvisor;
use App\Guide\Repository\TransportCategoryMapRepository;
use App\Guide\Dto\AdviceResult;
use App\Guide\Dto\GuideInput;
use App\Catalog\Entity\Product;
use App\Guide\Entity\TravelProfile;

final class HardFilterEngine
{
    public function __construct(
        private AirlineBaggageAdvisor $airlineAdvisor,
        private TransportCategoryMapRepository $transportCategoryMapRepository
    ) {}

    public function filter(
        array $products,
        AdviceResult $advice,
        ?TravelProfile $profile,
        array $rulesByScope,
        GuideInput $in
    ): array {

        $out = [
            'personal' => [],
            'cabin'    => [],
            'hold'     => [],
            'main'     => [],
        ];

        $transport = $in->transportSlug;
        $isFlight  = $transport === 'plane';

        // 🔥 Belangrijk: categorie IDs ophalen per scope
        $allowedCategoryIdsByScope = [];

        if ($transport) {
            foreach (['personal','cabin','hold','main'] as $scope) {

                $ids = $this->transportCategoryMapRepository
                    ->findCategoryIdsForTransport($transport, [$scope]);

                $allowedCategoryIdsByScope[$scope] = $ids;
            }
        }

        foreach ($products as $p) {

            $variant = $p->getMasterVariant();
            if (!$variant) {
                continue;
            }

            if (!$this->passesTransportFilter($p, $in)) {
                continue;
            }

            /* ==================================================
             * FLIGHT
             * ================================================== */
            if ($isFlight) {

                foreach (['personal','cabin','hold'] as $scope) {

                    if (!in_array($scope, $advice->allowedScopes, true)) {
                        continue;
                    }

                    if (empty($rulesByScope[$scope])) {
                        continue;
                    }

                    // 🔥 1️⃣ Category mapping check
                    if (!$this->productMatchesScopeCategory(
                        $p,
                        $allowedCategoryIdsByScope[$scope] ?? []
                    )) {
                        continue;
                    }

                    // 🔥 2️⃣ Airline rule check
                    foreach ($rulesByScope[$scope] as $rule) {

                        if ($this->airlineAdvisor->fitsProduct($p, $rule)) {
                            $out[$scope][] = $p;
                            break;
                        }
                    }
                }

                continue;
            }

            /* ==================================================
             * NON FLIGHT
             * ================================================== */

            if ($this->productMatchesScopeCategory(
                $p,
                $allowedCategoryIdsByScope['personal'] ?? []
            )) {
                $out['personal'][] = $p;
            } else {
                $out['main'][] = $p;
            }
        }

        return $out;
    }

    private function productMatchesScopeCategory(
        Product $product,
        array $allowedCategoryIds
    ): bool {

        if (empty($allowedCategoryIds)) {
            return false;
        }

        foreach ($product->getCategories() as $cat) {
            if (in_array($cat->getId(), $allowedCategoryIds, true)) {
                return true;
            }
        }

        return false;
    }

    private function passesTransportFilter(Product $p, GuideInput $in): bool
    {
        // je bestaande transport logic mag blijven
        return true;
    }
}