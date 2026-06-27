<?php

declare(strict_types=1);

namespace App\Seo\Service;

use App\Catalog\Entity\ProductVariant;

final class ProductVariantSeoResolver
{
    public function resolveTitle(ProductVariant $variant): string
    {
        if ($variant->getSeoTitle()) {
            return $variant->getSeoTitle();
        }

        $product = $variant->getProduct();

        $parts = array_filter([
            $product->getBrand()?->getName(),
            $product->getName(),
            $variant->getSupplierColorName(),
        ]);

        return trim(implode(' ', $parts)) . ' kopen | Topbags';
    }

    public function resolveDescription(ProductVariant $variant): string
    {
        if ($variant->getSeoDescription()) {
            return $variant->getSeoDescription();
        }

        $product = $variant->getProduct();

        $productName = trim(implode(' ', array_filter([
            $product->getBrand()?->getName(),
            $product->getName(),
        ])));

        $colorName = $variant->getSupplierColorName();

        $description = sprintf(
            'Ontdek de %s%s bij Topbags.',
            $productName,
            $colorName ? ' in ' . $colorName : ''
        );

        $description .= ' Op voorraad, snel leverbaar en gratis verzending vanaf € 49.';

        $description .= ' Bestel online of bekijk het model in Hengelo.';

        return $this->limitLength($description, 160);
    }

    private function limitLength(string $text, int $maxLength): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxLength - 1), " \t\n\r\0\x0B.,;:") . '…';
    }
}