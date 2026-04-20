<?php

namespace App\Catalog\ValueObject;

final class Availability
{
    public const IN_STOCK = 'in_stock';
    public const BACKORDER = 'backorder';
    public const OUT_OF_STOCK = 'out_of_stock';

    public function __construct(
        public readonly string $status,
        public readonly string $deliveryLabel,
        public readonly bool $canShip,
        public readonly bool $canPickup,
        public readonly ?int $leadTimeMin = null,
        public readonly ?int $leadTimeMax = null,
    ) {
    }

    public function isInStock(): bool
    {
        return $this->status === self::IN_STOCK;
    }

    public function isBackorder(): bool
    {
        return $this->status === self::BACKORDER;
    }

    public function isOutOfStock(): bool
    {
        return $this->status === self::OUT_OF_STOCK;
    }

    public function isPurchasable(): bool
    {
        return $this->canShip || $this->canPickup;
    }
}