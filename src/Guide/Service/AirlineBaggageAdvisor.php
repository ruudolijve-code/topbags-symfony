<?php

namespace App\Guide\Service;

use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
use App\Guide\Entity\AirlineBaggageRule;

final class AirlineBaggageAdvisor
{
    /**
     * Praktische airline-tolerantie (gate reality).
     * Expandables, zachte koffers en meetmarges worden hiermee opgevangen.
     */
    private const DIMENSION_TOLERANCE_CM = 3.0;

    /**
     * ✈️ Airline check op VARIANT-niveau
     * Variant is leidend bij vliegen (kleur/uitvoering kan verschillen).
     */
    public function fitsVariant(
        ProductVariant $variant,
        AirlineBaggageRule $rule
    ): bool {
        $product = $variant->getProduct();

        return $this->fitsDimensions(
            height: $variant->getHeightCm() ?? $product->getHeightCm(),
            width:  $variant->getWidthCm()  ?? $product->getWidthCm(),
            depth:  $variant->getDepthCm()  ?? $product->getDepthCm(),
            weight: $variant->getWeightKg() ?? $product->getWeightKg(),
            rule:   $rule
        );
    }

    /**
     * 🚗 🚆 🚌 Airline-/transportcheck op PRODUCT-niveau
     * Wordt gebruikt bij niet-vliegen of globale checks.
     */
    public function fitsProduct(
        Product $product,
        AirlineBaggageRule $rule
    ): bool {
        return $this->fitsDimensions(
            height: $product->getHeightCm(),
            width:  $product->getWidthCm(),
            depth:  $product->getDepthCm(),
            weight: $product->getWeightKg(),
            rule:   $rule
        );
    }

    /**
     * 🔐 Centrale fit-logica
     * Enige plek waar airline-regels worden geïnterpreteerd.
     */
    private function fitsDimensions(
        ?float $height,
        ?float $width,
        ?float $depth,
        ?float $weight,
        AirlineBaggageRule $rule
    ): bool {
        /* =====================
         * ⚖️ Gewicht (hard limit)
         * ===================== */
        if (
            $rule->getMaxWeightKg() !== null &&
            $weight !== null &&
            $weight > $rule->getMaxWeightKg()
        ) {
            return false;
        }

        /* =====================
         * 📏 Linear sum (ruimbagage)
         * ===================== */
        if ($rule->isLinearSum()) {
            if (
                $height === null ||
                $width  === null ||
                $depth  === null ||
                $rule->getMaxLinearCm() === null
            ) {
                return false;
            }

            $linear = $height + $width + $depth;

            return $linear
                <= ($rule->getMaxLinearCm() + self::DIMENSION_TOLERANCE_CM);
        }

        /* =====================
         * 📦 Box (personal / cabin)
         * ===================== */
        if ($rule->isBox()) {
            return $this->fitsBox(
                height: $height,
                width:  $width,
                depth:  $depth,
                rule:   $rule
            );
        }

        return false;
    }

    /**
     * 📦 Box-check met tolerantie
     * Wordt gebruikt voor personal item en cabin baggage.
     */
    private function fitsBox(
        ?float $height,
        ?float $width,
        ?float $depth,
        AirlineBaggageRule $rule
    ): bool {
        if ($height === null || $width === null || $depth === null) {
            return false;
        }

        if (
            $rule->getMaxHeightCm() !== null &&
            $height > ($rule->getMaxHeightCm() + self::DIMENSION_TOLERANCE_CM)
        ) {
            return false;
        }

        if (
            $rule->getMaxWidthCm() !== null &&
            $width > ($rule->getMaxWidthCm() + self::DIMENSION_TOLERANCE_CM)
        ) {
            return false;
        }

        if (
            $rule->getMaxDepthCm() !== null &&
            $depth > ($rule->getMaxDepthCm() + self::DIMENSION_TOLERANCE_CM)
        ) {
            return false;
        }

        return true;
    }
}