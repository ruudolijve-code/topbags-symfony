<?php

declare(strict_types=1);

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;

final class VariantImagePathResolver
{
    /**
     * Bepaalt de afbeeldingsmap op basis van model-SKU en kleurcode.
     *
     * Voorbeeld:
     * modelSku: AW0AW18856
     * colorCode: BDS
     *
     * Resultaat:
     * media/variants/a/w/AW0AW18856/BDS
     */
    public function fromModelAndColor(
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
     * Oude fallback voor bestaande SKU-opbouw:
     *
     * MODEL-KLEUR
     *
     * Gebruik deze methode niet voor nieuwe maatvarianten zoals:
     * MODEL-KLEUR-MAAT
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

        $color = trim((string) array_pop($parts));
        $model = trim(implode('-', $parts));

        if ($model === '' || $color === '') {
            return null;
        }

        $modelForIndex = preg_replace(
            '/[^A-Za-z0-9]/',
            '',
            $model
        ) ?? '';

        if (strlen($modelForIndex) < 2) {
            return null;
        }

        return sprintf(
            'media/variants/%s/%s/%s/%s',
            strtolower($modelForIndex[0]),
            strtolower($modelForIndex[1]),
            $model,
            $color
        );
    }

    /**
     * Bepaalt alleen de map voor een variant.
     *
     * De maat wordt bewust niet in het afbeeldingspad opgenomen.
     */
    public function directoryFromVariant(ProductVariant $variant): ?string
    {
        $product = $variant->getProduct();

        if ($product !== null) {
            $modelSku = trim((string) $product->getModelSku());
            $colorCode = trim((string) $variant->getSupplierColorCode());

            $directory = $this->fromModelAndColor(
                $modelSku,
                $colorCode
            );

            if ($directory !== null) {
                return $directory;
            }
        }

        /*
         * Fallback voor oude of onvolledige data.
         *
         * Let op: dit werkt alleen betrouwbaar bij oude SKU's
         * zonder maat als laatste onderdeel.
         */
        return self::fromSku($variant->getVariantSku());
    }

    /**
     * Geeft het volledige pad van de primaire of eerste afbeelding.
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