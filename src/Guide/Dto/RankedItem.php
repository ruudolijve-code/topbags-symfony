<?php

namespace App\Guide\Dto;

use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;

final class RankedItem
{
    /**
     * @param string[] $reasons
     */
    public function __construct(
        public readonly Product $product,
        public readonly ProductVariant $variant,
        public readonly float $score,
        public readonly array $reasons = [],
        public readonly ?string $mediaPath = null,
    ) {}
}