<?php

namespace App\Shared\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Repository\CategoryRepository;
use App\Catalog\Repository\ProductRepository;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private VariantImagePathResolver $variantImagePathResolver
    ) {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        AvailabilityService $availabilityService
    ): Response {
        $featuredSuitcases = [];
        $featuredTravelBags = [];

        $koffersCategory = $categoryRepository->findOneBy([
            'slug' => 'koffers',
            'isActive' => true,
        ]);

        if ($koffersCategory !== null) {
            $products = $productRepository->findForContextGridWithFilters(
                context: Product::CONTEXT_SHOP,
                limit: 4,
                offset: 0,
                categorySlugs: [$koffersCategory->getSlug()]
            );

            foreach ($products as $product) {
                $variant = $product->getMasterVariant();

                if ($variant === null || !$variant->isActive()) {
                    continue;
                }

                $featuredSuitcases[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'master' => $variant,
                    'mediaPath' => $this->variantImagePathResolver->fromVariant($variant),
                    'availability' => $availabilityService->get($variant),
                ];
            }
        }

        $reistassenCategory = $categoryRepository->findOneBy([
            'slug' => 'reistassen',
            'isActive' => true,
        ]);

        if ($reistassenCategory !== null) {
            $products = $productRepository->findForContextGridWithFilters(
                context: Product::CONTEXT_SHOP,
                limit: 4,
                offset: 0,
                categorySlugs: [$reistassenCategory->getSlug()]
            );

            foreach ($products as $product) {
                $variant = $product->getMasterVariant();

                if ($variant === null || !$variant->isActive()) {
                    continue;
                }

                $featuredTravelBags[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'master' => $variant,
                    'mediaPath' => $this->variantImagePathResolver->fromVariant($variant),
                    'availability' => $availabilityService->get($variant),
                ];
            }
        }

        return $this->render('home/index.html.twig', [
            'featuredSuitcases' => $featuredSuitcases,
            'featuredTravelBags' => $featuredTravelBags,
        ]);
    }
}