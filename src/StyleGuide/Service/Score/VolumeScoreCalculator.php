<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Score;

use App\Catalog\Entity\Product;
use App\StyleGuide\ValueObject\BagFitProfile;

final class VolumeScoreCalculator
{
    public function calculate(Product $product, BagFitProfile $profile): int
    {
        $productVolume = $product->getVolumeL();

        if ($productVolume === null || $productVolume <= 0.0 || $profile->minimumVolumeL <= 0.0) {
            return 0;
        }

        $ratio = $productVolume / $profile->minimumVolumeL;

        if ($ratio < 1.0) {
            return 0;
        }

        return match (true) {
            $ratio <= 1.40 => 20,
            $ratio <= 1.80 => 17,
            $ratio <= 2.50 => 12,
            $ratio <= 3.50 => 7,
            default => 2,
        };
    }
}
