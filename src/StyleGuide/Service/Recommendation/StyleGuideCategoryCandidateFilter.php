<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Recommendation;

use App\Catalog\Entity\Product;
use App\StyleGuide\Service\Score\CategoryMatcher;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class StyleGuideCategoryCandidateFilter
{
    private const AUDIENCE = ['dames' => ['damestassen'], 'heren' => ['herentassen']];
    private const CARRY = [
        'rugzak' => ['rugzakken', 'laptoprugzakken', 'schoolrugzakken', 'vrijetijdsrugzakken', 'daypacks', 'rugtassen'],
        'crossbody' => ['crossbodys', 'telefoontasje'], 'schoudertas' => ['schoudertassen'],
        'handtas' => ['handtassen'], 'shopper' => ['shopper'],
    ];
    public function __construct(private readonly CategoryMatcher $categoryMatcher) {}
    /** @param list<Product> $products @return list<Product> */
    public function filter(array $products, StyleGuideCriteria $criteria): array
    {
        $audience = self::AUDIENCE[$criteria->targetAudience->getCode()] ?? [];
        $carry = self::CARRY[$criteria->carryMethod->getCode()] ?? [];
        return array_values(array_filter($products, fn (Product $product): bool => ($audience === [] || $this->categoryMatcher->matches($product, $audience)) && ($carry === [] || $this->categoryMatcher->matches($product, $carry))));
    }
}
