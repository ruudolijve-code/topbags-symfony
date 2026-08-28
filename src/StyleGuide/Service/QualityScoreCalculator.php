<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\Catalog\Entity\Product;

final class QualityScoreCalculator
{
    public function calculate(
        Product $product,
    ): int {
        /*
         * Handmatige productcorrectie is altijd leidend.
         */
        if ($product->getQualityScoreOverride() !== null) {
            return $product->getQualityScoreOverride();
        }

        $brandScore =
            $product->getBrand()->getQualityPosition();

        $materialModifier =
            $product->getMaterial()?->getQualityModifier()
            ?? 0;

        $priceModifier =
            $this->calculatePriceModifier($product);

        return max(
            0,
            min(
                100,
                $brandScore
                + $materialModifier
                + $priceModifier,
            ),
        );
    }

    private function calculatePriceModifier(
        Product $product,
    ): int {
        $variant = $product->getMasterVariant();

        if ($variant === null) {
            return 0;
        }

        $price = (float) $variant->getDisplayPrice();

        /*
         * Voorlopige, bewust lichte prijsweging.
         *
         * Later vervangen we dit eventueel door
         * prijspositie binnen productcategorie.
         */
        return match (true) {
            $price >= 400 => 15,
            $price >= 300 => 12,
            $price >= 200 => 8,
            $price >= 125 => 4,
            $price < 60 => -5,
            default => 0,
        };
    }

    public function getSegment(
        Product $product,
    ): string {
        $score = $this->calculate($product);

        return match (true) {
            $score < 30 => 'budget',
            $score < 55 => 'value',
            $score < 80 => 'premium',
            default => 'luxury',
        };
    }
}