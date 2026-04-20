<?php
// src/Service/Product/ProductAdvice.php

namespace App\Catalog\Service\Product;

use App\Entity\Product;

final class ProductAdvice
{
    public function __construct(
        public Product $product,
        public array $labels = [],
        public array $reasons = []
    ) {}
}