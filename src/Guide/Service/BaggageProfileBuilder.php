<?php

namespace App\Guide\Service;

use App\Guide\Repository\TransportCategoryMapRepository;
use App\Catalog\Repository\MaterialRepository;
use App\Catalog\Entity\Category;

final class BaggageProfileBuilder
{
    public function __construct(
        private TransportCategoryMapRepository $categoryMapRepo,
        private MaterialRepository $materialRepo,
        private LightweightClassifier $lightweightClassifier
    ) {}

    public function build(
        array $input,
        array $allowed,
        ?array $volume
    ): BaggageProfile {

        $transport = (string) ($input['transport'] ?? '');
        $isPlane   = ($transport === 'plane');

        /* ==================================================
         * 1️⃣ Allowed scopes
         * ================================================== */

        if ($isPlane) {

            $allowedScopes = array_keys(
                array_filter($allowed, static fn ($v) => $v === true)
            );

        } else {

            // Niet-vliegen → altijd hoofd-bagage + personal
            $allowedScopes = ['main', 'personal'];
        }

        /* ==================================================
         * 2️⃣ Categorieën per scope
         * ================================================== */

        $categoryIdsByScope = [];

        if ($isPlane) {

            foreach ($allowedScopes as $scope) {

                $categories = $this->categoryMapRepo
                    ->findCategoriesForTransport(
                        transport: $transport,
                        baggageTypes: [$scope]
                    );

                $categoryIdsByScope[$scope] = array_map(
                    static fn (Category $c): int => $c->getId(),
                    $categories
                );
            }

        } else {

            // 🎒 Personal
            $personalCategories = $this->categoryMapRepo
                ->findCategoriesForTransport(
                    transport: $transport,
                    baggageTypes: ['personal']
                );

            $categoryIdsByScope['personal'] = array_map(
                static fn (Category $c): int => $c->getId(),
                $personalCategories
            );

            // 🧳 Main (EXPLICIET!)
            $mainCategories = $this->categoryMapRepo
                ->findCategoriesForTransport(
                    transport: $transport,
                    baggageTypes: ['main']
                );

            $categoryIdsByScope['main'] = array_map(
                static fn (Category $c): int => $c->getId(),
                $mainCategories
            );
        }

        /* ==================================================
         * 3️⃣ Materiaalvoorkeur
         * ================================================== */

        $preference = $input['preferences'] ?? null;

        $materials = match ($preference) {
            'robust'      => $this->materialRepo->findRigidMaterialSlugs(),
            'lightweight' => $this->materialRepo->findLightMaterialSlugs(),
            default       => [],
        };

        /* ==================================================
         * 4️⃣ Lightweight constraint (hard filter)
         * ================================================== */

        $maxLightweightClass = $preference === 'lightweight'
            ? $this->lightweightClassifier->maxAllowed('light')
            : null;

        /* ==================================================
         * 5️⃣ Ranking prioriteit
         * ================================================== */

        $priority = match (true) {
            $isPlane               => 'compliance',
            $preference === 'robust' => 'durability',
            default                => 'comfort',
        };

        /* ==================================================
         * 6️⃣ Bouw profiel (pure data)
         * ================================================== */

        return new BaggageProfile(
            transport: $transport,
            allowedScopes: $allowedScopes,
            categoryIdsByScope: $categoryIdsByScope,
            volume: $volume,
            materials: $materials,
            maxLightweightClass: $maxLightweightClass,
            priority: $priority,
            frequency: $input['frequency'] ?? null,
            preference: $preference
        );
    }
}