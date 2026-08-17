<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\Catalog\Entity\Product;
use App\StyleGuide\Enum\RecommendedBagCategory;
use App\StyleGuide\ValueObject\BagFitProfile;
use App\StyleGuide\ValueObject\BagRecommendationProfile;
use App\StyleGuide\ValueObject\ProductMatch;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class StyleGuideProductScorer
{
    private const BACKPACK_CATEGORY_SLUGS = [
        'rugzakken',
        'laptoprugzakken',
        'schoolrugzakken',
        'vrijetijdsrugzakken',
        'daypacks',
        'rugtassen',
    ];

    private const CROSSBODY_CATEGORY_SLUGS = [
        'crossbodys',
        'telefoontasje',
    ];

    private const SHOULDER_BAG_CATEGORY_SLUGS = [
        'schoudertassen',
    ];

    private const HANDBAG_CATEGORY_SLUGS = [
        'handtassen',
    ];

    private const SHOPPER_CATEGORY_SLUGS = [
        'shopper',
    ];

    public function score(
        Product $product,
        StyleGuideCriteria $criteria,
        BagFitProfile $fitProfile,
        BagRecommendationProfile $recommendationProfile,
    ): ProductMatch {
        $score = 0;
        $reasons = [];
        $scoreBreakdown = [];

        /*
         * Fysieke maatvoering.
         */
        $dimensionScore = $this->scoreDimensions(
            $product,
            $fitProfile,
        );

        $score += $dimensionScore;
        $scoreBreakdown['dimensions'] = $dimensionScore;

        if ($dimensionScore >= 35) {
            $reasons[] = 'Formaat sluit uitstekend aan';
        } elseif ($dimensionScore >= 25) {
            $reasons[] = 'Formaat sluit goed aan';
        }

        /*
         * Inhoud in liters is ondersteunend.
         */
        $volumeScore = $this->scoreVolume(
            $product,
            $fitProfile,
        );

        $score += $volumeScore;
        $scoreBreakdown['volume'] = $volumeScore;

        if ($volumeScore >= 15) {
            $reasons[] = 'Inhoud sluit goed aan';
        }

        /*
         * Laptopgeschiktheid.
         */
        $laptopScore = $this->scoreLaptop(
            $product,
            $fitProfile,
        );

        $score += $laptopScore;
        $scoreBreakdown['laptop'] = $laptopScore;

        if (
            $fitProfile->requiresLaptopCompartment
            && $laptopScore > 0
        ) {
            if ($fitProfile->requiredLaptopInch !== null) {
                $reasons[] = sprintf(
                    'Geschikt voor %s inch laptop',
                    $this->formatInch(
                        $fitProfile->requiredLaptopInch,
                    ),
                );
            } else {
                $reasons[] = 'Met laptopvak';
            }
        }

        /*
         * A4 is al onderdeel van de minimale fysieke maat.
         */
        $a4Score = $fitProfile->requiresA4Fit
            ? 5
            : 0;

        $score += $a4Score;
        $scoreBreakdown['a4'] = $a4Score;

        if ($a4Score > 0) {
            $reasons[] = 'Ruimte voor A4-documenten';
        }

        /*
         * Expliciet gekozen draagwijze van de klant.
         *
         * Deze keuze is leidend en weegt daarom zwaarder
         * dan de automatisch aanbevolen categorie.
         */
        $carryMethodScore = $this->scoreCarryMethod(
            $product,
            $criteria,
        );

        $score += $carryMethodScore;
        $scoreBreakdown['carry_method'] =
            $carryMethodScore;

        if ($carryMethodScore >= 20) {
            $reasons[] =
                'Past bij jouw gekozen draagwijze';
        }

        /*
         * Automatisch aanbevolen type tas op basis
         * van het fysieke draagprofiel.
         */
        $categoryScore =
            $this->scoreRecommendedCategory(
                $product,
                $recommendationProfile,
            );

        $score += $categoryScore;
        $scoreBreakdown['recommended_category'] =
            $categoryScore;

        if ($categoryScore >= 10) {
            $reasons[] =
                'Sluit aan op het aanbevolen type tas';
        }

        /*
         * Volgende scorelagen:
         *
         * - materiaal
         * - kwaliteits-/positioneringsniveau
         * - stijlwereld
         * - gebruiksmoment
         * - outfit
         */
        return new ProductMatch(
            product: $product,
            score: max(0, $score),
            reasons: array_values(
                array_unique($reasons),
            ),
            scoreBreakdown: $scoreBreakdown,
        );
    }

    private function scoreDimensions(
        Product $product,
        BagFitProfile $profile,
    ): int {
        $width = $product->getWidthCm();
        $height = $product->getHeightCm();
        $depth = $product->getDepthCm();

        if (
            $width === null
            || $height === null
            || $depth === null
        ) {
            return 0;
        }

        $ratios = [];

        if ($profile->minimumWidthCm > 0.0) {
            $ratios[] =
                $width / $profile->minimumWidthCm;
        }

        if ($profile->minimumHeightCm > 0.0) {
            $ratios[] =
                $height / $profile->minimumHeightCm;
        }

        if ($profile->minimumDepthCm > 0.0) {
            $ratios[] =
                $depth / $profile->minimumDepthCm;
        }

        if ($ratios === []) {
            return 0;
        }

        /*
         * De FitCandidateFilter heeft al gecontroleerd
         * of de minimale maten worden gehaald.
         *
         * Hier belonen we een tas die voldoende ruimte
         * biedt zonder onnodig groot te zijn.
         */
        $averageRatio =
            array_sum($ratios) / count($ratios);

        return match (true) {
            $averageRatio <= 1.15 => 45,
            $averageRatio <= 1.30 => 40,
            $averageRatio <= 1.50 => 32,
            $averageRatio <= 1.80 => 24,
            $averageRatio <= 2.20 => 14,
            default => 5,
        };
    }

    private function scoreVolume(
        Product $product,
        BagFitProfile $profile,
    ): int {
        $productVolume = $product->getVolumeL();

        if (
            $productVolume === null
            || $productVolume <= 0.0
            || $profile->minimumVolumeL <= 0.0
        ) {
            return 0;
        }

        $ratio =
            $productVolume / $profile->minimumVolumeL;

        if ($ratio < 1.0) {
            return 0;
        }

        return match (true) {
            $ratio <= 1.40 => 20,
            $ratio <= 1.80 => 17,
            $ratio <= 2.50 => 12,
            $ratio <= 3.50 => 7,
            default => 2,
        };
    }

    private function scoreLaptop(
        Product $product,
        BagFitProfile $profile,
    ): int {
        if (!$profile->requiresLaptopCompartment) {
            return 0;
        }

        if (!$product->isLaptopCompartment()) {
            return 0;
        }

        if ($profile->requiredLaptopInch === null) {
            return 20;
        }

        $maximumInch = $product->getLaptopMaxInch();

        if ($maximumInch === null) {
            return 0;
        }

        $difference =
            $maximumInch - $profile->requiredLaptopInch;

        if ($difference < 0.0) {
            return 0;
        }

        return match (true) {
            $difference <= 0.5 => 25,
            $difference <= 1.5 => 22,
            $difference <= 3.0 => 16,
            default => 10,
        };
    }

    private function scoreCarryMethod(
        Product $product,
        StyleGuideCriteria $criteria,
    ): int {
        $categorySlugs =
            $this->getCategorySlugs($product);

        return match (
            $criteria->carryMethod->getCode()
        ) {
            'rugzak' =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::BACKPACK_CATEGORY_SLUGS,
                ) ? 25 : 0,

            'crossbody' =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::CROSSBODY_CATEGORY_SLUGS,
                ) ? 25 : 0,

            'schoudertas' =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::SHOULDER_BAG_CATEGORY_SLUGS,
                ) ? 25 : 0,

            'handtas' =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::HANDBAG_CATEGORY_SLUGS,
                ) ? 25 : 0,

            'shopper' =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::SHOPPER_CATEGORY_SLUGS,
                ) ? 25 : 0,

            default => 0,
        };
    }

    private function scoreRecommendedCategory(
        Product $product,
        BagRecommendationProfile $recommendationProfile,
    ): int {
        $categorySlugs =
            $this->getCategorySlugs($product);

        return match (
            $recommendationProfile->recommendedCategory
        ) {
            RecommendedBagCategory::BACKPACK =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::BACKPACK_CATEGORY_SLUGS,
                ) ? 15 : 0,

            RecommendedBagCategory::CROSSBODY =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::CROSSBODY_CATEGORY_SLUGS,
                ) ? 15 : 0,

            RecommendedBagCategory::SHOULDER_BAG =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::SHOULDER_BAG_CATEGORY_SLUGS,
                ) ? 15 : 0,

            RecommendedBagCategory::SHOPPER =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::SHOPPER_CATEGORY_SLUGS,
                ) ? 15 : 0,

            RecommendedBagCategory::HANDBAG =>
                $this->hasAnyCategory(
                    $categorySlugs,
                    self::HANDBAG_CATEGORY_SLUGS,
                ) ? 15 : 0,
        };
    }

    /**
     * @return list<string>
     */
    private function getCategorySlugs(
        Product $product,
    ): array {
        $slugs = [];

        foreach ($product->getCategories() as $category) {
            $slugs[] = $category->getSlug();
        }

        return array_values(
            array_unique($slugs),
        );
    }

    /**
     * @param list<string> $productCategorySlugs
     * @param list<string> $wantedCategorySlugs
     */
    private function hasAnyCategory(
        array $productCategorySlugs,
        array $wantedCategorySlugs,
    ): bool {
        return array_intersect(
            $productCategorySlugs,
            $wantedCategorySlugs,
        ) !== [];
    }

    private function formatInch(
        float $inch,
    ): string {
        if (floor($inch) === $inch) {
            return number_format(
                $inch,
                0,
                ',',
                '.',
            );
        }

        return number_format(
            $inch,
            1,
            ',',
            '.',
        );
    }
}