<?php

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;
use App\Catalog\ValueObject\Availability;

final class AvailabilityResolver
{
    public function resolve(ProductVariant $variant): Availability
    {
        if (!$variant->isActive()) {
            return new Availability(
                status: Availability::OUT_OF_STOCK,
                deliveryLabel: 'Niet beschikbaar',
                canShip: false,
                canPickup: false
            );
        }

        if ($variant->getAvailableStock() > 0) {
            return new Availability(
                status: Availability::IN_STOCK,
                deliveryLabel: 'Vandaag verzonden',
                canShip: true,
                canPickup: true
            );
        }

        if ($variant->allowsBackorder()) {
            $min = $variant->getBackorderLeadTimeMinDays();
            $max = $variant->getBackorderLeadTimeMaxDays();

            $label = 'Leverbaar via leverancier';

            if ($min !== null && $max !== null) {
                $label = sprintf('%d-%d werkdagen', $min, $max);
            } elseif ($min !== null) {
                $label = sprintf('ca. %d werkdagen', $min);
            }

            return new Availability(
                status: Availability::BACKORDER,
                deliveryLabel: $label,
                canShip: true,
                canPickup: false,
                leadTimeMin: $min,
                leadTimeMax: $max
            );
        }

        return new Availability(
            status: Availability::OUT_OF_STOCK,
            deliveryLabel: 'Niet op voorraad',
            canShip: false,
            canPickup: false
        );
    }
}