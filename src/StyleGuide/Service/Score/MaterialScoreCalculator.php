<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Score;

use App\Catalog\Entity\Product;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class MaterialScoreCalculator
{
    public function calculate(Product $product, StyleGuideCriteria $criteria): int
    {
        return 0;
    }
}
