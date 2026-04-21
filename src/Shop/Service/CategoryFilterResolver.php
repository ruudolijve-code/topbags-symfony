<?php

namespace App\Shop\Service;

use App\Catalog\Entity\Product;

final class CategoryFilterResolver
{
    public const FILTER_BRANDS = 'brands';
    public const FILTER_SIZE = 'size';
    public const FILTER_WEIGHT = 'weight';
    public const FILTER_VOLUME = 'volume';
    public const FILTER_COLOR = 'color';
    public const FILTER_AIRLINES = 'airlines';
    public const FILTER_FLYING_SCOPE = 'flying_scope';
    public const FILTER_CTA = 'cta';

    public function getAllowedFilters(string $context): array
    {
        return match ($context) {
            Product::CONTEXT_BAGS => [
                self::FILTER_BRANDS,
                self::FILTER_COLOR,
                self::FILTER_CTA,
            ],
            Product::CONTEXT_SHOP => [
                self::FILTER_BRANDS,
                self::FILTER_SIZE,
                self::FILTER_WEIGHT,
                self::FILTER_VOLUME,
                self::FILTER_COLOR,
                self::FILTER_AIRLINES,
                self::FILTER_FLYING_SCOPE,
                self::FILTER_CTA,
            ],
            default => [
                self::FILTER_BRANDS,
                self::FILTER_COLOR,
                self::FILTER_CTA,
            ],
        };
    }

    public function hasFilter(array $allowedFilters, string $filter): bool
    {
        return in_array($filter, $allowedFilters, true);
    }
}