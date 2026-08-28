<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Score;

use App\Catalog\Entity\Product;
use App\StyleGuide\Service\Quality\QualityPreferenceMapper;
use App\StyleGuide\Service\Quality\QualityScoreCalculator;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class QualityMatchCalculator
{
    public function __construct(
        private readonly QualityScoreCalculator $qualityScoreCalculator,
        private readonly QualityPreferenceMapper $qualityPreferenceMapper,
    ) {
    }

    public function calculate(Product $product, StyleGuideCriteria $criteria): int
    {
        $preference = $criteria->budgetPreference;

        if (!$this->qualityPreferenceMapper->hasPreference($preference)) {
            return 0;
        }

        $segment = $this->qualityScoreCalculator->getSegment($product);

        if ($this->qualityPreferenceMapper->prefersSegment($preference, $segment)) {
            return 25;
        }

        return match ($preference->getCode()) {
            'aantrekkelijke-prijs' => $segment === QualityPreferenceMapper::SEGMENT_PREMIUM ? 5 : 0,
            'goede-kwaliteit' => in_array($segment, [QualityPreferenceMapper::SEGMENT_BUDGET, QualityPreferenceMapper::SEGMENT_LUXURY], true) ? 5 : 0,
            'premium' => $segment === QualityPreferenceMapper::SEGMENT_VALUE ? 8 : 0,
            'luxe' => $segment === QualityPreferenceMapper::SEGMENT_PREMIUM ? 10 : 0,
            default => 0,
        };
    }
}
