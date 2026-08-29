<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\Catalog\Entity\Product;
use App\StyleGuide\Service\Quality\QualityScoreCalculator;
use App\StyleGuide\Service\Score\CarryMethodScoreCalculator;
use App\StyleGuide\Service\Score\DimensionScoreCalculator;
use App\StyleGuide\Service\Score\LaptopScoreCalculator;
use App\StyleGuide\Service\Score\MaterialScoreCalculator;
use App\StyleGuide\Service\Score\OutfitScoreCalculator;
use App\StyleGuide\Service\Score\QualityMatchCalculator;
use App\StyleGuide\Service\Score\RecommendedCategoryScoreCalculator;
use App\StyleGuide\Service\Score\UseMomentScoreCalculator;
use App\StyleGuide\Service\Score\VolumeScoreCalculator;
use App\StyleGuide\ValueObject\BagFitProfile;
use App\StyleGuide\ValueObject\BagRecommendationProfile;
use App\StyleGuide\ValueObject\ProductMatch;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class StyleGuideProductScorer
{
    public function __construct(
        private readonly DimensionScoreCalculator $dimensionScoreCalculator,
        private readonly VolumeScoreCalculator $volumeScoreCalculator,
        private readonly LaptopScoreCalculator $laptopScoreCalculator,
        private readonly CarryMethodScoreCalculator $carryMethodScoreCalculator,
        private readonly RecommendedCategoryScoreCalculator $recommendedCategoryScoreCalculator,
        private readonly QualityMatchCalculator $qualityMatchCalculator,
        private readonly QualityScoreCalculator $qualityScoreCalculator,
        private readonly MaterialScoreCalculator $materialScoreCalculator,
        private readonly StyleAffinityCalculator $styleAffinityCalculator,
        private readonly UseMomentScoreCalculator $useMomentScoreCalculator,
        private readonly OutfitScoreCalculator $outfitScoreCalculator,
    ) {
    }

    public function score(
        Product $product,
        StyleGuideCriteria $criteria,
        BagFitProfile $fitProfile,
        BagRecommendationProfile $recommendationProfile,
    ): ProductMatch {
        $styleAffinity = $this->styleAffinityCalculator->calculate($product, $criteria);
        $breakdown = [
            'dimensions' => $this->dimensionScoreCalculator->calculate($product, $fitProfile),
            'volume' => $this->volumeScoreCalculator->calculate($product, $fitProfile),
            'laptop' => $this->laptopScoreCalculator->calculate($product, $fitProfile),
            'a4' => $fitProfile->requiresA4Fit ? 5 : 0,
            'carry_method' => $this->carryMethodScoreCalculator->calculate($product, $criteria),
            'recommended_category' => $this->recommendedCategoryScoreCalculator->calculate($product, $recommendationProfile),
            'quality' => $this->qualityMatchCalculator->calculate($product, $criteria),
            'product_quality_score' => $this->qualityScoreCalculator->calculate($product),
            'material' => $this->materialScoreCalculator->calculate($product, $criteria),
            'style_affinity' => $styleAffinity->affinityScore,
            'product_override' => $styleAffinity->overrideScore,
            'use_moment' => $this->useMomentScoreCalculator->calculate($product, $criteria),
            'outfit' => $this->outfitScoreCalculator->calculate($product, $criteria),
        ];

        $score = array_sum($breakdown);

        return new ProductMatch(
            product: $product,
            score: max(0, $score),
            reasons: array_values(array_unique([...$this->buildReasons($breakdown, $fitProfile), ...$styleAffinity->reasons])),
            scoreBreakdown: $breakdown,
        );
    }

    /**
     * @param array<string, int> $breakdown
     *
     * @return list<string>
     */
    private function buildReasons(array $breakdown, BagFitProfile $fitProfile): array
    {
        $reasons = [];

        if ($breakdown['dimensions'] >= 35) {
            $reasons[] = 'Formaat sluit uitstekend aan';
        } elseif ($breakdown['dimensions'] >= 25) {
            $reasons[] = 'Formaat sluit goed aan';
        }

        if ($breakdown['volume'] >= 15) {
            $reasons[] = 'Inhoud sluit goed aan';
        }

        if ($fitProfile->requiresLaptopCompartment && $breakdown['laptop'] > 0) {
            $reasons[] = $fitProfile->requiredLaptopInch !== null
                ? sprintf('Geschikt voor %s inch laptop', $this->formatInch($fitProfile->requiredLaptopInch))
                : 'Met laptopvak';
        }

        if ($breakdown['a4'] > 0) {
            $reasons[] = 'Ruimte voor A4-documenten';
        }

        if ($breakdown['carry_method'] >= 20) {
            $reasons[] = 'Past bij jouw gekozen draagwijze';
        }

        if ($breakdown['recommended_category'] >= 10) {
            $reasons[] = 'Sluit aan op het aanbevolen type tas';
        }

        if ($breakdown['quality'] >= 20) {
            $reasons[] = 'Kwaliteitsniveau sluit uitstekend aan';
        } elseif ($breakdown['quality'] >= 10) {
            $reasons[] = 'Kwaliteitsniveau sluit goed aan';
        }

        return array_values(array_unique($reasons));
    }

    private function formatInch(float $inch): string
    {
        return number_format($inch, floor($inch) === $inch ? 0 : 1, ',', '.');
    }
}
