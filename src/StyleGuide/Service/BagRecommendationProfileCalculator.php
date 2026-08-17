<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\StyleGuide\Enum\CarryMethod;
use App\StyleGuide\Enum\RecommendedBagCategory;
use App\StyleGuide\ValueObject\BagFitProfile;
use App\StyleGuide\ValueObject\BagRecommendationProfile;

final class BagRecommendationProfileCalculator
{
    public function calculate(
        BagFitProfile $fitProfile,
    ): BagRecommendationProfile {
        [$sizeKey, $sizeLabel] = $this->determineSize(
            $fitProfile,
        );

        $recommendedCategory = $this->determineRecommendedCategory(
            $fitProfile,
        );

        return new BagRecommendationProfile(
            sizeKey: $sizeKey,
            sizeLabel: $sizeLabel,

            headline: $this->buildHeadline(
                $sizeLabel,
                $fitProfile,
            ),

            description: $this->buildDescription(
                $fitProfile,
            ),

            recommendedCategory: $recommendedCategory,

            preferredCarryMethod:
                $this->determinePreferredCarryMethod(
                    $recommendedCategory,
                ),

            features: $this->buildFeatures(
                $fitProfile,
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function buildFeatures(
        BagFitProfile $fitProfile,
    ): array {
        $features = [];

        if ($fitProfile->requiresLaptopCompartment) {
            if ($fitProfile->requiredLaptopInch !== null) {
                $features[] = sprintf(
                    'Laptopvak voor minimaal %s inch',
                    $this->formatInch(
                        $fitProfile->requiredLaptopInch,
                    ),
                );
            } else {
                $features[] = 'Laptopvak';
            }
        }

        if ($fitProfile->requiresA4Fit) {
            $features[] = 'Geschikt voor A4-documenten';
        }

        if ($fitProfile->hasVerticalLoad) {
            $features[] = 'Ruimte voor een drinkfles';
        }

        if ($fitProfile->hasBulkyLoad) {
            $features[] = 'Extra ruimte voor grotere spullen';
        }

        return $features;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function determineSize(
        BagFitProfile $profile,
    ): array {
        /*
         * Volume is ondersteunend, niet leidend.
         *
         * Een laptop of A4-map vraagt relatief weinig inhoud
         * in liters, maar kan wel een flinke minimale breedte
         * of hoogte vereisen.
         */
        $dimensionScore = max(
            $profile->minimumWidthCm / 40,
            $profile->minimumHeightCm / 35,
            $profile->minimumDepthCm / 15,
        );

        $volumeScore =
            $profile->minimumVolumeL / 15;

        $score = max(
            $dimensionScore,
            $volumeScore,
        );

        return match (true) {
            $score <= 0.45 => [
                'compact',
                'Compact',
            ],

            $score <= 0.70 => [
                'medium',
                'Middelgroot',
            ],

            $score <= 0.95 => [
                'large',
                'Ruim',
            ],

            default => [
                'extra_large',
                'Extra ruim',
            ],
        };
    }

    private function determineRecommendedCategory(
        BagFitProfile $profile,
    ): RecommendedBagCategory {
        /*
         * Dit is voorlopig alleen een inhoudelijke aanbeveling
         * op basis van het fysieke draagprofiel.
         *
         * De expliciet gekozen draagwijze uit de Stijlgids
         * wordt straks als aparte voorkeur meegenomen in de scorer.
         */

        if (
            $profile->requiresLaptopCompartment
            && (
                $profile->requiresA4Fit
                || $profile->minimumWidthCm >= 32
            )
        ) {
            return RecommendedBagCategory::BACKPACK;
        }

        if (
            $profile->hasVerticalLoad
            || $profile->hasBulkyLoad
            || $profile->minimumVolumeL >= 8
        ) {
            return RecommendedBagCategory::SHOPPER;
        }

        if (
            $profile->minimumVolumeL <= 3
            && !$profile->requiresA4Fit
            && !$profile->requiresLaptopCompartment
        ) {
            return RecommendedBagCategory::CROSSBODY;
        }

        return RecommendedBagCategory::SHOULDER_BAG;
    }

    private function determinePreferredCarryMethod(
        RecommendedBagCategory $category,
    ): CarryMethod {
        return match ($category) {
            RecommendedBagCategory::BACKPACK =>
                CarryMethod::BACKPACK,

            RecommendedBagCategory::CROSSBODY =>
                CarryMethod::CROSSBODY,

            RecommendedBagCategory::SHOULDER_BAG =>
                CarryMethod::SHOULDER_BAG,

            RecommendedBagCategory::SHOPPER =>
                CarryMethod::SHOPPER,

            RecommendedBagCategory::HANDBAG =>
                CarryMethod::HANDBAG,
        };
    }

    private function buildHeadline(
        string $sizeLabel,
        BagFitProfile $profile,
    ): string {
        if (
            $profile->requiresLaptopCompartment
            && $profile->requiresA4Fit
        ) {
            return sprintf(
                '%s met ruimte voor laptop en documenten',
                $sizeLabel,
            );
        }

        if ($profile->requiresLaptopCompartment) {
            return sprintf(
                '%s met passend laptopvak',
                $sizeLabel,
            );
        }

        if ($profile->requiresA4Fit) {
            return sprintf(
                '%s met ruimte voor A4-documenten',
                $sizeLabel,
            );
        }

        return sprintf(
            '%s voor jouw dagelijkse spullen',
            $sizeLabel,
        );
    }

    private function buildDescription(
        BagFitProfile $profile,
    ): string {
        $parts = [];

        if ($profile->requiresLaptopCompartment) {
            if ($profile->requiredLaptopInch !== null) {
                $parts[] = sprintf(
                    'een laptop tot minimaal %s inch',
                    $this->formatInch(
                        $profile->requiredLaptopInch,
                    ),
                );
            } else {
                $parts[] = 'een laptop';
            }
        }

        if ($profile->requiresA4Fit) {
            $parts[] = 'A4-documenten';
        }

        if ($profile->hasVerticalLoad) {
            $parts[] = 'een drinkfles';
        }

        if ($parts === []) {
            return 'We zoeken een tas die voldoende ruimte biedt zonder onnodig groot te zijn.';
        }

        return sprintf(
            'We zoeken een tas met voldoende ruimte voor %s, zonder dat de tas onnodig groot wordt.',
            $this->formatList($parts),
        );
    }

    /**
     * @param list<string> $items
     */
    private function formatList(
        array $items,
    ): string {
        if (count($items) === 1) {
            return $items[0];
        }

        $last = array_pop($items);

        return sprintf(
            '%s en %s',
            implode(', ', $items),
            $last,
        );
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