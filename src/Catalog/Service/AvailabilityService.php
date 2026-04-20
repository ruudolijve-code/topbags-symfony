<?php

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;
use App\Catalog\ValueObject\Availability;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class AvailabilityService
{
    public function __construct(
        private AvailabilityResolver $resolver,

        #[Autowire(service: 'catalog.availability.cache')]
        private CacheInterface $cache
    ) {
    }

    public function get(ProductVariant $variant): Availability
    {
        $cacheKey = $this->getCacheKey($variant);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($variant): Availability {
            // Korte TTL, omdat voorraad en backorderstatus snel kunnen wijzigen
            $item->expiresAfter(300);

            return $this->resolver->resolve($variant);
        });
    }

    public function invalidate(ProductVariant $variant): void
    {
        $this->cache->delete($this->getCacheKey($variant));
    }

    private function getCacheKey(ProductVariant $variant): string
    {
        return sprintf('availability.variant.%d', $variant->getId());
    }
}