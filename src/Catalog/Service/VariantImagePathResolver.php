<?php

declare(strict_types=1);

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;

final class VariantImagePathResolver
{
    public function fromSku(string $variantSku): ?string
    {
        $parts = array_values(array_filter(explode('-', trim($variantSku))));

        if (count($parts) < 2) {
            return null;
        }

        $color = trim((string) array_pop($parts));
        $model = trim(implode('-', $parts));

        if ($model === '' || $color === '') {
            return null;
        }

        $modelForIndex = preg_replace('/[^A-Za-z0-9]/', '', $model) ?? '';

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

    public function fromVariant(ProductVariant $variant): ?string
    {
        $basePath = $this->fromSku($variant->getVariantSku());

        if ($basePath === null) {
            return null;
        }

        foreach ($variant->getImages() as $image) {
            $filename = trim((string) $image->getFilename());

            if ($filename === '') {
                continue;
            }

            if ($image->isPrimary()) {
                return $basePath . '/' . ltrim($filename, '/');
            }
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