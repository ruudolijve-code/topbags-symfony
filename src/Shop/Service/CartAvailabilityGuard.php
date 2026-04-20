<?php

namespace App\Shop\Service;

use App\Catalog\Entity\ProductVariant;
use App\Catalog\Service\AvailabilityService;

class CartAvailabilityGuard
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {
    }

    public function canAdd(ProductVariant $variant, int $requestedQty): bool
    {
        if ($requestedQty <= 0) {
            return false;
        }

        $availability = $this->availabilityService->get($variant);

        if ($availability->isOutOfStock()) {
            return false;
        }

        if ($variant->hasBackorderOption()) {
            return true;
        }

        return $requestedQty <= $variant->getStockAvailable();
    }

    public function getErrorMessage(ProductVariant $variant, int $requestedQty): ?string
    {
        if ($requestedQty <= 0) {
            return 'Ongeldige hoeveelheid.';
        }

        $availability = $this->availabilityService->get($variant);

        if ($availability->isOutOfStock()) {
            return sprintf(
                'Product "%s" is momenteel niet leverbaar.',
                $variant->getProduct()->getName()
            );
        }

        if ($variant->hasBackorderOption()) {
            return null;
        }

        $available = $variant->getStockAvailable();

        if ($requestedQty > $available) {
            if ($available <= 0) {
                return sprintf(
                    'Product "%s" is niet meer op voorraad.',
                    $variant->getProduct()->getName()
                );
            }

            return sprintf(
                'Van "%s" zijn nog maar %d stuk(s) beschikbaar.',
                $variant->getProduct()->getName(),
                $available
            );
        }

        return null;
    }
}