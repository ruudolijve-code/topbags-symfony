<?php

namespace App\Feed\Service;

use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\VariantImagePathResolver;

final class ProductFeedItemService
{
    public function __construct(
        private readonly ProductVariantRepository $variantRepository,
        private readonly VariantImagePathResolver $imagePathResolver,
    ) {
    }

    /**
     * @return array<int, array{
     *     variant: object,
     *     imagePath: string
     * }>
     */
    public function getActiveFeedItems(): array
    {
        $variants = $this->variantRepository->findActiveForMetaFeed();

        $feedItems = [];

        foreach ($variants as $variant) {
            $imagePath = $this->imagePathResolver->fromVariant($variant);

            if ($imagePath === null || trim($imagePath) === '') {
                continue;
            }

            $feedItems[] = [
                'variant' => $variant,
                'imagePath' => $imagePath,
            ];
        }

        return $feedItems;
    }
}