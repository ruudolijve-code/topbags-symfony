<?php

declare(strict_types=1);

namespace App\Catalog\Enum;

enum ProductType: string
{
    case PHYSICAL = 'physical';
    case SERVICE = 'service';

    public function label(): string
    {
        return match ($this) {
            self::PHYSICAL => 'Fysiek product',
            self::SERVICE => 'Dienst',
        };
    }

    public function requiresShipping(): bool
    {
        return $this === self::PHYSICAL;
    }

    public function tracksStock(): bool
    {
        return $this === self::PHYSICAL;
    }

    public function couponEligible(): bool
    {
        return $this === self::PHYSICAL;
    }
}