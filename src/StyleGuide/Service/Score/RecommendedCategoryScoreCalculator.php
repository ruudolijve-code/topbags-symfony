<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Score;

use App\Catalog\Entity\Product;
use App\StyleGuide\Enum\RecommendedBagCategory;
use App\StyleGuide\ValueObject\BagRecommendationProfile;

final class RecommendedCategoryScoreCalculator
{
    private const CATEGORY_SLUGS = [
        'backpack' => ['rugzakken', 'laptoprugzakken', 'schoolrugzakken', 'vrijetijdsrugzakken', 'daypacks', 'rugtassen'],
        'crossbody' => ['crossbodys', 'telefoontasje'],
        'shoulder_bag' => ['schoudertassen'],
        'handbag' => ['handtassen'],
        'shopper' => ['shopper'],
    ];

    public function __construct(private readonly CategoryMatcher $categoryMatcher)
    {
    }

    public function calculate(Product $product, BagRecommendationProfile $profile): int
    {
        $key = match ($profile->recommendedCategory) {
            RecommendedBagCategory::BACKPACK => 'backpack',
            RecommendedBagCategory::CROSSBODY => 'crossbody',
            RecommendedBagCategory::SHOULDER_BAG => 'shoulder_bag',
            RecommendedBagCategory::HANDBAG => 'handbag',
            RecommendedBagCategory::SHOPPER => 'shopper',
        };

        return $this->categoryMatcher->matches($product, self::CATEGORY_SLUGS[$key]) ? 15 : 0;
    }
}
