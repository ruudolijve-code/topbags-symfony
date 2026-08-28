<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Score;

use App\Catalog\Entity\Product;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class CarryMethodScoreCalculator
{
    private const CATEGORY_SLUGS = [
        'rugzak' => ['rugzakken', 'laptoprugzakken', 'schoolrugzakken', 'vrijetijdsrugzakken', 'daypacks', 'rugtassen'],
        'crossbody' => ['crossbodys', 'telefoontasje'],
        'schoudertas' => ['schoudertassen'],
        'handtas' => ['handtassen'],
        'shopper' => ['shopper'],
    ];

    public function __construct(private readonly CategoryMatcher $categoryMatcher)
    {
    }

    public function calculate(Product $product, StyleGuideCriteria $criteria): int
    {
        $wantedSlugs = self::CATEGORY_SLUGS[$criteria->carryMethod->getCode()] ?? [];

        return $wantedSlugs !== [] && $this->categoryMatcher->matches($product, $wantedSlugs)
            ? 25
            : 0;
    }
}
