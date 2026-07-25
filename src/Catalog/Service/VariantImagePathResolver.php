<?php

declare(strict_types=1);

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;

final class VariantImagePathResolver
{
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

        /*
         * SKU met maat:
         *
         * AM0AM13014-BDS-105
         *
         * laatste deel = maat
         * voorlaatste deel = kleur
         * overige delen = model
         */
        if (
            count($parts) >= 3
            && self::looksLikeSize((string) end($parts))
        ) {
            array_pop($parts);
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

    public function directoryFromVariant(ProductVariant $variant): ?string
    {
        return self::fromSku($variant->getVariantSku());
    }

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

    private static function looksLikeSize(string $value): bool
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return false;
        }

        /*
         * Riemmaten, schoenmaten, lengtematen:
         *
         * 85
         * 95
         * 105
         * 7.5
         * 7,5
         * 105cm
         */
        if (
            preg_match(
                '/^\d+(?:[.,]\d+)?(?:cm)?$/',
                $value
            ) === 1
        ) {
            return true;
        }

        return in_array(
            $value,
            [
                'xxs',
                'xs',
                's',
                'm',
                'l',
                'xl',
                'xxl',
                'xxxl',
                'one-size',
                'onesize',
            ],
            true
        );
    }
}