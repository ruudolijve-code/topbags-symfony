<?php

declare(strict_types=1);

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;

final class VariantImagePathResolver
{
    /**
     * Bouwt een afbeeldingsmap op uit de expliciete model-SKU
     * en leverancier-kleurcode.
     *
     * Voorbeelden:
     *
     * model: 336-439
     * kleur: 06
     * resultaat: media/variants/3/3/336-439/06
     *
     * model: AM0AM13014
     * kleur: BDS
     * resultaat: media/variants/a/m/AM0AM13014/BDS
     */
    public static function fromModelAndColor(
        string $modelSku,
        string $colorCode
    ): ?string {
        $modelSku = trim($modelSku);
        $colorCode = trim($colorCode);

        if ($modelSku === '' || $colorCode === '') {
            return null;
        }

        $modelForIndex = preg_replace(
            '/[^A-Za-z0-9]/',
            '',
            $modelSku
        ) ?? '';

        if (strlen($modelForIndex) < 2) {
            return null;
        }

        return sprintf(
            'media/variants/%s/%s/%s/%s',
            strtolower($modelForIndex[0]),
            strtolower($modelForIndex[1]),
            $modelSku,
            $colorCode
        );
    }

    /**
     * Bepaalt de map aan de hand van de gegevens op de variant.
     *
     * Dit is de voorkeursmethode. De maat in de variant-SKU
     * heeft hierdoor geen invloed op het afbeeldingspad.
     */
    public static function directoryFromVariantData(
        ProductVariant $variant
    ): ?string {
        $product = $variant->getProduct();

        $modelSku = trim((string) $product->getModelSku());
        $colorCode = trim((string) $variant->getSupplierColorCode());

        if ($modelSku !== '' && $colorCode !== '') {
            return self::fromModelAndColor(
                $modelSku,
                $colorCode
            );
        }

        /*
         * Alleen als tijdelijke fallback voor oudere data.
         */
        return self::fromSku($variant->getVariantSku());
    }

    /**
     * Legacy resolver voor SKU's zonder aparte maat.
     *
     * Deze methode beschouwt altijd het laatste SKU-deel als kleurcode.
     *
     * 336-439-06:
     * model = 336-439
     * kleur = 06
     *
     * Deze methode kan een SKU zoals AM0AM13014-BDS-105 niet
     * betrouwbaar interpreteren, omdat niet bekend is of 105
     * een maat of kleurcode is.
     */
    public static function fromSku(string $variantSku): ?string
    {
        $parts = array_values(
            array_filter(
                explode('-', trim($variantSku)),
                static fn (string $part): bool => trim($part) !== ''
            )
        );

        if (count($parts) < 2) {
            return null;
        }

        $colorCode = trim((string) array_pop($parts));
        $modelSku = trim(implode('-', $parts));

        return self::fromModelAndColor(
            $modelSku,
            $colorCode
        );
    }

    public function directoryFromVariant(
        ProductVariant $variant
    ): ?string {
        return self::directoryFromVariantData($variant);
    }

    /**
     * Geeft het volledige pad naar de primaire of eerste afbeelding.
     */
    public function fromVariant(ProductVariant $variant): ?string
    {
        $basePath = $this->directoryFromVariant($variant);

        if ($basePath === null) {
            return null;
        }

        foreach ($variant->getImages() as $image) {
            $filename = trim((string) $image->getFilename());

            if ($filename === '' || !$image->isPrimary()) {
                continue;
            }

            return $basePath . '/' . ltrim($filename, '/');
        }

        foreach ($variant->getImages() as $image) {
            $filename = trim((string) $image->getFilename());

            if ($filename === '') {
                continue;
            }

            return $basePath . '/' . ltrim($filename, '/');
        }

        return null;
    }
}