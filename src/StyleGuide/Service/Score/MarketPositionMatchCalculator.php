<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Score;

use App\Catalog\Entity\Product;
use App\StyleGuide\Service\MarketPosition\MarketPositionCalculator;
use App\StyleGuide\Service\MarketPosition\MarketPositionPreferenceMapper;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class MarketPositionMatchCalculator
{
    public function __construct(
        private readonly MarketPositionCalculator $marketPositionCalculator,
        private readonly MarketPositionPreferenceMapper $marketPositionPreferenceMapper,
    ) {
    }

    public function calculate(Product $product, StyleGuideCriteria $criteria): int
    {
        $preference = $criteria->budgetPreference;

        if (!$this->marketPositionPreferenceMapper->hasPreference($preference)) {
            return 0;
        }

        $segment = $this->marketPositionCalculator->getSegment($product);

        if ($this->marketPositionPreferenceMapper->prefersSegment($preference, $segment)) {
            return 25;
        }

        return match ($preference->getCode()) {
            'aantrekkelijke-prijs' => $segment === MarketPositionPreferenceMapper::SEGMENT_PREMIUM ? 5 : 0,
            'goede-kwaliteit' => in_array($segment, [MarketPositionPreferenceMapper::SEGMENT_BUDGET, MarketPositionPreferenceMapper::SEGMENT_LUXURY], true) ? 5 : 0,
            'premium' => $segment === MarketPositionPreferenceMapper::SEGMENT_VALUE ? 8 : 0,
            'luxe' => $segment === MarketPositionPreferenceMapper::SEGMENT_PREMIUM ? 10 : 0,
            default => 0,
        };
    }
}
