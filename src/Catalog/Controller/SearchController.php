<?php

namespace App\Catalog\Controller;

use App\Catalog\Repository\BrandRepository;
use App\Catalog\Repository\ProductRepository;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    public function __construct(
        private readonly VariantImagePathResolver $variantImagePathResolver,
    ) {
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        BrandRepository $brandRepository,
        AvailabilityService $availabilityService
    ): Response {
        $query = trim((string) $request->query->get('q', ''));

        if ($query === '') {
            return $this->render('search/index.html.twig', [
                'query' => '',
                'items' => [],
            ]);
        }

        /*
         * Als de zoekterm exact of sterk overeenkomt met een merk,
         * stuur dan door naar de shop met merkfilter.
         */
        $matchedBrand = $brandRepository->findActiveBySearchTerm($query);

        if ($matchedBrand !== null) {
            return $this->redirectToRoute('shop_index', [
                'brand' => [$matchedBrand->getSlug()],
            ]);
        }

        /*
         * Zoek alleen binnen reisartikelen / shop-context.
         * Damestassen horen niet in deze zoekflow thuis.
         */
        $products = $productRepository->searchForShop($query, 24);

        $items = [];

       foreach ($products as $product) {
            $activeVariants = array_values(array_filter(
                $product->getVariants()->toArray(),
                static fn ($variant): bool => $variant->isActive(),
            ));

            if ($activeVariants === []) {
                continue;
            }

            $masterVariant = $product->getMasterVariant();

            if ($masterVariant === null || !$masterVariant->isActive()) {
                $masterVariant = $activeVariants[0];
            }

            $items[] = [
                'product' => $product,
                'variant' => $masterVariant,
                'master' => $masterVariant,
                'variants' => $activeVariants,
                'mediaPath' => $this->variantImagePathResolver->fromVariant($masterVariant),
                'availability' => $availabilityService->get($masterVariant),
            ];
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'items' => $items,
        ]);
    }
}