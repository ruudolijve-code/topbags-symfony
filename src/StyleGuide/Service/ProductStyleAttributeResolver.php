<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\Catalog\Entity\Color;
use App\Catalog\Entity\Product;

final class ProductStyleAttributeResolver
{
    /** @return list<Color> */
    public function colors(Product $product): array
    {
        $colors = [];
        foreach ($product->getVariants() as $variant) {
            if (!$variant->isActive()) { continue; }
            $color = $variant->getNormalizedColor() ?? $variant->getColor();
            if ($color !== null && $color->getId() !== null) { $colors[$color->getId()] = $color; }
        }
        return array_values($colors);
    }
}
