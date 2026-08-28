<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Score;

use App\Catalog\Entity\Product;
use App\StyleGuide\ValueObject\BagFitProfile;

final class DimensionScoreCalculator
{
    public function calculate(Product $product, BagFitProfile $profile): int
    {
        $width = $product->getWidthCm();
        $height = $product->getHeightCm();
        $depth = $product->getDepthCm();

        if ($width === null || $height === null || $depth === null) {
            return 0;
        }

        $ratios = [];

        if ($profile->minimumWidthCm > 0.0) {
            $ratios[] = $width / $profile->minimumWidthCm;
        }

        if ($profile->minimumHeightCm > 0.0) {
            $ratios[] = $height / $profile->minimumHeightCm;
        }

        if ($profile->minimumDepthCm > 0.0) {
            $ratios[] = $depth / $profile->minimumDepthCm;
        }

        if ($ratios === []) {
            return 0;
        }

        $averageRatio = array_sum($ratios) / count($ratios);

        return match (true) {
            $averageRatio <= 1.15 => 45,
            $averageRatio <= 1.30 => 40,
            $averageRatio <= 1.50 => 32,
            $averageRatio <= 1.80 => 24,
            $averageRatio <= 2.20 => 14,
            default => 5,
        };
    }
}
