<?php

namespace App\Shop\Service;

use App\Shop\Entity\Order;

final class ShippingCalculator
{
    public function __construct(
        private float $freeFrom,
        private float $defaultCost
    ) {
    }

    public function calculate(
        float $shippableSubtotal,
        string $shippingMethod = Order::SHIPPING_METHOD_HOME
    ): float {
        if ($shippingMethod === Order::SHIPPING_METHOD_STORE_PICKUP) {
            return 0.0;
        }

        // Geen fysieke/verzendbare producten in de winkelwagen.
        if ($shippableSubtotal <= 0.0) {
            return 0.0;
        }

        if ($shippableSubtotal >= $this->freeFrom) {
            return 0.0;
        }

        return $this->defaultCost;
    }

    public function getFreeFrom(): float
    {
        return $this->freeFrom;
    }

    public function getDefaultCost(): float
    {
        return $this->defaultCost;
    }
}