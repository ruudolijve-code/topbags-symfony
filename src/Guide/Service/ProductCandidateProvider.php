<?php

namespace App\Guide\Service;

use App\Catalog\Entity\Product;
use App\Catalog\Repository\ProductRepository;
use App\Guide\Dto\GuideInput;

final class ProductCandidateProvider
{
    public function __construct(
        private ProductRepository $productRepository
    ) {
    }

    /**
     * @return Product[]
     */
    public function getCandidates(GuideInput $input): array
    {
        /*
         * Reisgids werkt alleen met reisartikelen.
         * Damestassen moeten hier dus altijd buiten blijven.
         */
        return $this->productRepository->findActiveForContext(
            Product::CONTEXT_SHOP
        );
    }
}