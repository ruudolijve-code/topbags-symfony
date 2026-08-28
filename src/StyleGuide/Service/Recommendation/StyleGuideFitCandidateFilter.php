<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Recommendation;

use App\Catalog\Entity\Product;
use App\StyleGuide\ValueObject\BagFitProfile;

final class StyleGuideFitCandidateFilter
{
    /**
     * Filtert de commerciële voorselectie op fysieke geschiktheid.
     *
     * @param list<Product> $products
     *
     * @return list<Product>
     */
    public function filter(
        array $products,
        BagFitProfile $fitProfile,
    ): array {
        return array_values(
            array_filter(
                $products,
                fn (Product $product): bool => $this->matches(
                    $product,
                    $fitProfile,
                ),
            ),
        );
    }

    private function matches(
        Product $product,
        BagFitProfile $fitProfile,
    ): bool {
        if (
            $product->getWidthCm() === null
            || $product->getHeightCm() === null
            || $product->getDepthCm() === null
        ) {
            return false;
        }

        $fitsUpright =
            $product->getWidthCm() >= $fitProfile->minimumWidthCm
            && $product->getHeightCm() >= $fitProfile->minimumHeightCm;

        /*
         * Een telefoon of portemonnee kan ook liggend in een tas.
         * Alleen profielen met een expliciete verticale, A4- of
         * laptopbelasting moeten de berekende oriëntatie behouden.
         */
        $mayRotateContents =
            !$fitProfile->hasVerticalLoad
            && !$fitProfile->requiresA4Fit
            && !$fitProfile->requiresLaptopCompartment;

        $fitsRotated =
            $mayRotateContents
            && $product->getWidthCm() >= $fitProfile->minimumHeightCm
            && $product->getHeightCm() >= $fitProfile->minimumWidthCm;

        if (!$fitsUpright && !$fitsRotated) {
            return false;
        }

        if ($product->getDepthCm() < $fitProfile->minimumDepthCm) {
            return false;
        }

        if (
            $fitProfile->requiresLaptopCompartment
            && !$product->isLaptopCompartment()
        ) {
            return false;
        }

        if (
            $fitProfile->requiredLaptopInch !== null
            && (
                $product->getLaptopMaxInch() === null
                || $product->getLaptopMaxInch() < $fitProfile->requiredLaptopInch
            )
        ) {
            return false;
        }

        return true;
    }
}
