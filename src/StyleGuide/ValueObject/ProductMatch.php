<?php

declare(strict_types=1);

namespace App\StyleGuide\ValueObject;

use App\Catalog\Entity\Product;

final readonly class ProductMatch
{
    /**
     * @param list<string> $reasons
     * @param array<string, int> $scoreBreakdown
     */
    public function __construct(
        public Product $product,
        public int $score,
        public array $reasons = [],
        public array $scoreBreakdown = [],
    ) {
    }
}