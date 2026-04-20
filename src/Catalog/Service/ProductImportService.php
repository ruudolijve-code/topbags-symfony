<?php

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductImportService
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}

    public function hydrateVariant(
        ProductVariant $variant,
        string $supplierColorName
    ): void {

        $variant->setSupplierColorName('Acid Green');
        $variant->setSupplierColorSlug(
            $slugger->slug($variant->getSupplierColorName())->lower()
        );
    }
}