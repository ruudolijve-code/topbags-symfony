<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Recommendation;

use App\Catalog\Entity\Product;
use App\StyleGuide\Service\Score\CategoryMatcher;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class StyleGuideCategoryCandidateFilter
{
    public function __construct(
        private readonly CategoryMatcher $categoryMatcher,
        private readonly StyleGuideCategorySlugMapper $slugMapper,
    ) {
    }
    /** @param list<Product> $products @return list<Product> */
    public function filter(array $products, StyleGuideCriteria $criteria): array
    {
        $audience = $this->slugMapper->audience($criteria);
        $carry = $this->slugMapper->carryMethod($criteria);
        return array_values(array_filter($products, fn (Product $product): bool => ($audience === [] || $this->categoryMatcher->matches($product, $audience)) && ($carry === [] || $this->categoryMatcher->matches($product, $carry))));
    }
}
