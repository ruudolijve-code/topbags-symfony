<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Score;

use App\Catalog\Entity\Product;
use App\StyleGuide\ValueObject\BagFitProfile;

final class LaptopScoreCalculator
{
    public function calculate(Product $product, BagFitProfile $profile): int
    {
        if (!$profile->requiresLaptopCompartment || !$product->isLaptopCompartment()) {
            return 0;
        }

        if ($profile->requiredLaptopInch === null) {
            return 20;
        }

        $maximumInch = $product->getLaptopMaxInch();

        if ($maximumInch === null) {
            return 0;
        }

        $difference = $maximumInch - $profile->requiredLaptopInch;

        if ($difference < 0.0) {
            return 0;
        }

        return match (true) {
            $difference <= 0.5 => 25,
            $difference <= 1.5 => 22,
            $difference <= 3.0 => 16,
            default => 10,
        };
    }
}
