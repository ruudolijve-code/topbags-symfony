<?php

declare(strict_types=1);

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;
use App\Catalog\Repository\ProductVariantRepository;
use DomainException;

final class VariantSkuGenerator
{
    public function __construct(
        private readonly ProductVariantRepository $productVariantRepository,
    ) {
    }

    public function assignSku(ProductVariant $variant): void
    {
        $sku = $this->generateSku($variant);

        $existingVariant = $this->productVariantRepository->findOneBy([
            'variantSku' => $sku,
        ]);

        if (
            $existingVariant !== null
            && $existingVariant->getId() !== $variant->getId()
        ) {
            throw new DomainException(
                sprintf(
                    'De automatisch samengestelde variant-SKU "%s" bestaat al.',
                    $sku
                )
            );
        }

        $variant->setVariantSku($sku);
    }

    public function generateSku(ProductVariant $variant): string
    {
        $product = $variant->getProduct();

        if ($product === null) {
            throw new DomainException(
                'Er moet eerst een product worden geselecteerd.'
            );
        }

        $modelSku = $this->normalizeSegment($product->getModelSku());

        if ($modelSku === '') {
            throw new DomainException(
                'Het geselecteerde product heeft geen model-SKU.'
            );
        }

        $supplierColorCode = $this->normalizeSegment(
            $variant->getSupplierColorCode()
        );

        if ($supplierColorCode === '') {
            throw new DomainException(
                'De supplier kleurcode is verplicht.'
            );
        }

        $segments = [
            $modelSku,
            $supplierColorCode,
        ];

        $size = $variant->getSize();

        if ($size !== null) {
            $sizeCode = $this->normalizeSegment($size->getCode());

            if ($sizeCode === '') {
                throw new DomainException(
                    sprintf(
                        'De geselecteerde maat "%s" heeft geen maatcode.',
                        $size->getName()
                    )
                );
            }

            $segments[] = $sizeCode;
        }

        return implode('-', $segments);
    }

    private function normalizeSegment(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim($value);
    }
}