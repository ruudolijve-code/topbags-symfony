<?php

namespace App\Catalog\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Repository\BrandRepository;
use App\Catalog\Repository\ProductRepository;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use App\Shared\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BrandController extends AbstractController
{
    private const PER_PAGE = 12;

    public function __construct(
        private VariantImagePathResolver $variantImagePathResolver
    ) {
    }

    #[Route('/merken', name: 'brand_index', methods: ['GET'])]
    public function index(BrandRepository $brandRepository): Response
    {
        $brands = $brandRepository->findAllOrderedByName();

        $groupedBrands = [];

        foreach ($brands as $brand) {
            $letter = strtoupper(mb_substr($brand->getName(), 0, 1));

            if (!isset($groupedBrands[$letter])) {
                $groupedBrands[$letter] = [];
            }

            $groupedBrands[$letter][] = $brand;
        }

        ksort($groupedBrands);

        return $this->render('brand/index.html.twig', [
            'groupedBrands' => $groupedBrands,
            'letters' => array_keys($groupedBrands),
        ]);
    }

    #[Route('/merk/{slug}', name: 'brand_show', methods: ['GET'])]
    public function show(
        string $slug,
        Request $request,
        BrandRepository $brandRepository,
        ProductRepository $productRepository,
        AvailabilityService $availabilityService,
        PaginationService $paginationService
    ): Response {
        $brand = $brandRepository->findOneBy([
            'slug' => $slug,
            'isActive' => true,
        ]);

        if (!$brand) {
            throw $this->createNotFoundException();
        }

        /*
         * Merkpagina's mogen niet vast op shop staan.
         * Guess zit bijvoorbeeld in bags, Samsonite meestal in shop.
         *
         * Eerst controleren of dit merk actieve producten in bags heeft.
         * Zo ja: toon de merkpagina als bags-context.
         * Zo nee: val terug op shop-context.
         */
        $brandContext = $productRepository->countForContextGridWithFilters(
            context: Product::CONTEXT_BAGS,
            brandSlugs: [$brand->getSlug()],
        ) > 0
            ? Product::CONTEXT_BAGS
            : Product::CONTEXT_SHOP;

        $page = max(1, $request->query->getInt('page', 1));

        $totalItems = $productRepository->countForContextGridWithFilters(
            context: $brandContext,
            brandSlugs: [$brand->getSlug()],
        );

        $pagination = $paginationService->create(
            page: $page,
            limit: self::PER_PAGE,
            totalItems: $totalItems,
        );

        $products = $productRepository->findForContextGridWithFilters(
            context: $brandContext,
            limit: $pagination->getLimit(),
            offset: $pagination->getOffset(),
            brandSlugs: [$brand->getSlug()],
        );

        $items = [];

        foreach ($products as $product) {
            $activeVariants = array_values(array_filter(
                $product->getVariants()->toArray(),
                static fn ($variant): bool => $variant->isActive(),
            ));

            if ($activeVariants === []) {
                continue;
            }

            $master = $product->getMasterVariant();

            if ($master === null || !$master->isActive()) {
                $master = $activeVariants[0];
            }

            $items[] = [
                'product' => $product,
                'variant' => $master,
                'master' => $master,
                'variants' => $activeVariants,
                'mediaPath' => $this->variantImagePathResolver->fromVariant($master),
                'availability' => $availabilityService->get($master),
            ];
        }

        return $this->render('brand/show.html.twig', [
            'brand' => $brand,
            'items' => $items,
            'pagination' => $pagination,
            'categories' => [],
            'context' => $brandContext,
            'currentContext' => $brandContext,
        ]);
    }
}