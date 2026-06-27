<?php

declare(strict_types=1);

namespace App\Seo\Service;

use App\Catalog\Entity\ProductVariant;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class ProductSchemaBuilder
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly AvailabilityService $availabilityService,
        private readonly VariantImagePathResolver $variantImagePathResolver,
    ) {
    }

    public function build(ProductVariant $variant, string $description): array
    {
        $product = $variant->getProduct();
        $brand = $product->getBrand();
        $availability = $this->availabilityService->get($variant);

        $canonicalUrl = $this->router->generate(
            'product_show',
            [
                'slug' => $product->getSlug(),
                'colorSlug' => $variant->getSupplierColorSlug(),
                'variantSku' => $variant->getVariantSku(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $productName = trim(implode(' ', array_filter([
            $brand?->getName(),
            $product->getName(),
            $variant->getSupplierColorName(),
        ])));

        $groupName = trim(implode(' ', array_filter([
            $brand?->getName(),
            $product->getName(),
        ])));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $canonicalUrl . '#product',
            'name' => $productName,
            'description' => $description,
            'url' => $canonicalUrl,
            'sku' => $variant->getVariantSku(),
            'color' => $variant->getSupplierColorName(),
            'image' => $this->buildImages($variant),
            'brand' => $brand ? [
                '@type' => 'Brand',
                'name' => $brand->getName(),
            ] : null,
            'isVariantOf' => [
                '@type' => 'ProductGroup',
                '@id' => $canonicalUrl . '#product-group',
                'name' => $groupName,
                'productGroupID' => $product->getModelSku(),
                'variesBy' => [
                    'https://schema.org/color',
                ],
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonicalUrl,
                'priceCurrency' => 'EUR',
                'price' => number_format((float) $variant->getDisplayPrice(), 2, '.', ''),
                'availability' => $this->resolveSchemaAvailability($availability->status ?? null),
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'Topbags',
                    'url' => $this->router->generate(
                        'home',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ],
            ],
        ];

        if ($variant->getEan() !== '') {
            $schema['gtin13'] = $variant->getEan();
        }

        return $this->removeNullValues($schema);
    }

    private function buildImages(ProductVariant $variant): array
    {
        $images = [];
        $basePath = $this->variantImagePathResolver->fromSku($variant->getVariantSku());

        foreach ($variant->getImages() as $image) {
            $filename = $image->getFilename();

            if (!$filename) {
                continue;
            }

            $images[] = $this->router->generate(
                'home',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ) . ltrim($basePath . '/' . $filename, '/');
        }

        return array_values(array_unique($images));
    }

    private function resolveSchemaAvailability(?string $status): string
    {
        return match ($status) {
            'in_stock' => 'https://schema.org/InStock',

            // Bewuste Topbags-keuze: op de site amber "Op voorraad",
            // technisch voor Google: bestelbaar met levertijd.
            'backorder' => 'https://schema.org/BackOrder',

            default => 'https://schema.org/OutOfStock',
        };
    }

    private function removeNullValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === null || $value === []) {
                unset($data[$key]);
                continue;
            }

            if (is_array($value)) {
                $cleaned = $this->removeNullValues($value);

                if ($cleaned === []) {
                    unset($data[$key]);
                    continue;
                }

                $data[$key] = $cleaned;
            }
        }

        return $data;
    }
}