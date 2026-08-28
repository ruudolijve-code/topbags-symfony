<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Score;

use App\Catalog\Entity\Product;

final class CategoryMatcher
{
    /**
     * @param list<string> $wantedSlugs
     */
    public function matches(Product $product, array $wantedSlugs): bool
    {
        $productSlugs = [];

        foreach ($product->getCategories() as $category) {
            $productSlugs[] = $category->getSlug();
        }

        return array_intersect(array_unique($productSlugs), $wantedSlugs) !== [];
    }
}
