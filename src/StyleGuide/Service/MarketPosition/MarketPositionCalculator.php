<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\MarketPosition;

use App\Catalog\Entity\Product;

final class MarketPositionCalculator
{
    public function calculate(Product $product): int
    {
        $override = $product->getMarketPositionOverride();

        if ($override !== null) {
            return $this->clamp($override);
        }

        return $this->clamp(
            $product->getBrand()->getMarketPosition()
            + ($product->getMaterial()?->getMarketPositionModifier() ?? 0)
            + $this->calculatePriceModifier($product),
        );
    }

    /**
     * @return array{
     *     override: int|null,
     *     brand_market_position: int,
     *     material_modifier: int,
     *     price_modifier: int,
     *     total: int
     * }
     */
    public function breakdown(Product $product): array
    {
        $override = $product->getMarketPositionOverride();
        $brandMarketPosition = $product->getBrand()->getMarketPosition();
        $materialModifier = $product->getMaterial()?->getMarketPositionModifier() ?? 0;
        $priceModifier = $this->calculatePriceModifier($product);

        return [
            'override' => $override,
            'brand_market_position' => $brandMarketPosition,
            'material_modifier' => $materialModifier,
            'price_modifier' => $priceModifier,
            'total' => $override !== null
                ? $this->clamp($override)
                : $this->clamp($brandMarketPosition + $materialModifier + $priceModifier),
        ];
    }

    public function getSegment(Product $product): string
    {
        return $this->segmentForMarketPosition($this->calculate($product));
    }

    public function segmentForMarketPosition(int $marketPosition): string
    {
        return match (true) {
            $marketPosition < 30 => 'budget',
            $marketPosition < 55 => 'value',
            $marketPosition < 80 => 'premium',
            default => 'luxury',
        };
    }

    private function calculatePriceModifier(Product $product): int
    {
        $variant = $product->getMasterVariant();

        if ($variant === null) {
            return 0;
        }

        $price = (float) $variant->getDisplayPrice();

        return match (true) {
            $price >= 400.0 => 15,
            $price >= 300.0 => 12,
            $price >= 200.0 => 8,
            $price >= 125.0 => 4,
            $price < 60.0 => -5,
            default => 0,
        };
    }

    private function clamp(int $marketPosition): int
    {
        return max(0, min(100, $marketPosition));
    }
}
