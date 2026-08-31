<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Quality;

use App\Catalog\Entity\Product;

final class QualityScoreCalculator
{
    public function calculate(Product $product): int
    {
        /*
         * Handmatige productspecifieke override is altijd leidend.
         */
        $override = $product->getQualityScoreOverride();

        if ($override !== null) {
            return $this->clamp($override);
        }

        /*
         * Merkpositionering vormt de basis van het kwaliteitssegment.
         *
         * Wanneer voor een merk nog geen positionering is ingesteld,
         * gebruiken we 50 als neutrale fallback.
         */
        $brandPositioning =
            $product->getBrand()->getBrandPositioning() ?? 50;

        $materialModifier =
            $product->getMaterial()?->getQualityModifier()
            ?? 0;

        $priceModifier =
            $this->calculatePriceModifier($product);

        return $this->clamp(
            $brandPositioning
            + $materialModifier
            + $priceModifier,
        );
    }

    /**
     * Geeft de scorecomponenten terug voor debug en tuning.
     *
     * @return array{
     *     override: int|null,
     *     brand_positioning: int,
     *     material: int,
     *     price: int,
     *     total: int
     * }
     */
    public function breakdown(Product $product): array
    {
        $override = $product->getQualityScoreOverride();

        $brandPositioning =
            $product->getBrand()->getBrandPositioning() ?? 50;

        $materialModifier =
            $product->getMaterial()?->getQualityModifier()
            ?? 0;

        $priceModifier =
            $this->calculatePriceModifier($product);

        $total = $override !== null
            ? $this->clamp($override)
            : $this->clamp(
                $brandPositioning
                + $materialModifier
                + $priceModifier,
            );

        return [
            'override' => $override,
            'brand_positioning' => $brandPositioning,
            'material' => $materialModifier,
            'price' => $priceModifier,
            'total' => $total,
        ];
    }

    public function getSegment(Product $product): string
    {
        return $this->segmentForScore(
            $this->calculate($product),
        );
    }

    public function segmentForScore(int $score): string
    {
        return match (true) {
            $score < 30 => 'budget',
            $score < 55 => 'value',
            $score < 80 => 'premium',
            default => 'luxury',
        };
    }

    private function calculatePriceModifier(Product $product): int
    {
        $variant = $product->getMasterVariant();

        if ($variant === null) {
            return 0;
        }

        /*
         * Prijs weegt bewust licht mee.
         *
         * Dezelfde prijs kan per productcategorie immers
         * een heel andere marktpositie betekenen.
         *
         * Later kunnen we dit vervangen door een relatieve
         * prijspositie binnen categorie.
         */
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

    private function clamp(int $score): int
    {
        return max(
            0,
            min(100, $score),
        );
    }
}