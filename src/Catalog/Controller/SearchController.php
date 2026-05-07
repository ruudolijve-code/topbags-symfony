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
                'totalColors' => 0,
                'totalAvailableVariants' => 0,
            ]);
        }

        /*
         * Als de zoekterm exact of sterk overeenkomt met een merk,
         * stuur dan door naar de merkpagina.
         */
        $matchedBrand = $brandRepository->findActiveBySearchTerm($query);

        if ($matchedBrand !== null) {
            return $this->redirectToRoute('brand_show', [
                'slug' => $matchedBrand->getSlug(),
            ]);
        }

        /*
         * Zoek sitebreed: shop + bags.
         * Geen contextfilter, omdat merken zoals Guess zowel bags als koffers kunnen bevatten.
         */
        $products = $productRepository->searchAllActive($query, 24);

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

        $totalColors = $productRepository->countColorsForSearch($query);
        $totalAvailableVariants = $productRepository->countAvailableVariantsForSearch($query);

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'items' => $items,
            'totalColors' => $totalColors,
            'totalAvailableVariants' => $totalAvailableVariants,
        ]);
    }
}