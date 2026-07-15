<?php

namespace App\Shared\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
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
        private readonly VariantImagePathResolver $variantImagePathResolver,
    ) {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        AvailabilityService $availabilityService,
    ): Response {
        $featuredSuitcases = [];
        $newSuitcaseVariants = [];
        $featuredBags = [];
        $newBagVariants = [];

        $koffersCategory = $categoryRepository->findOneBy([
            'slug' => 'koffers',
            'isActive' => true,
        ]);

        /*
         * Populaire koffers
         * Belangrijk: gebruikt isFeatured + featuredPosition.
         */
       $featuredSuitcaseProducts = $productRepository->findFeaturedForCategorySlug(
            context: Product::CONTEXT_SHOP,
            categorySlug: 'koffers',
            limit: 4,
        );

        foreach ($featuredSuitcaseProducts as $product) {
            $card = $this->createCardFromProduct($product, $availabilityService);

            if ($card !== null) {
                $featuredSuitcases[] = $card;
            }
        }

        /*
         * Fallback: als er nog geen featured koffers zijn ingesteld.
         */
        if ($featuredSuitcases === [] && $koffersCategory !== null) {
            $products = $productRepository->findForContextGridWithFilters(
                context: Product::CONTEXT_SHOP,
                limit: 4,
                offset: 0,
                categorySlugs: [$koffersCategory->getSlug()],
            );

            foreach ($products as $product) {
                $card = $this->createCardFromProduct($product, $availabilityService);

                if ($card !== null) {
                    $featuredSuitcases[] = $card;
                }
            }
        }

        /*
         * Nieuw binnen bij koffers
         * Variant-gebaseerd, zodat nieuwe kleuren van bestaande modellen zichtbaar worden.
         */
        if ($koffersCategory !== null) {
            $variants = $productRepository->findLatestVariantsForContextAndCategory(
                context: Product::CONTEXT_SHOP,
                categorySlug: $koffersCategory->getSlug(),
                limit: 4,
            );

            foreach ($variants as $variant) {
                $card = $this->createCardFromVariant($variant, $availabilityService);

                if ($card !== null) {
                    $newSuitcaseVariants[] = $card;
                }
            }
        }

        /*
         * Populaire damestassen
         * Ook op basis van featured producten binnen context bags.
         */
        $featuredBagProducts = $productRepository->findFeaturedForCategorySlug(
            context: Product::CONTEXT_BAGS,
            categorySlug: 'damestassen',
            limit: 4,
        );

        foreach ($featuredBagProducts as $product) {
            $card = $this->createCardFromProduct($product, $availabilityService);

            if ($card !== null) {
                $featuredBags[] = $card;
            }
        }

        /*
         * Fallback: als er nog geen featured damestassen zijn ingesteld.
         */
        if ($featuredBags === []) {
            $bagProducts = $productRepository->findForContextGridWithFilters(
                context: Product::CONTEXT_BAGS,
                limit: 4,
                offset: 0,
                categorySlugs: [],
            );

            foreach ($bagProducts as $product) {
                $card = $this->createCardFromProduct($product, $availabilityService);

                if ($card !== null) {
                    $featuredBags[] = $card;
                }
            }
        }

        /*
        * Nieuw binnen bij damestassen
        */
        $variants = $productRepository->findLatestVariantsForContextAndCategory(
            context: Product::CONTEXT_BAGS,
            categorySlug: 'damestassen',
            limit: 4,
        );

        foreach ($variants as $variant) {
            $card = $this->createCardFromVariant($variant, $availabilityService);

            if ($card !== null) {
                $newBagVariants[] = $card;
            }
        }

        $landingCategory = $koffersCategory;

        return $this->render('home/index.html.twig', [
            'featuredSuitcases' => $featuredSuitcases,
            'newSuitcaseVariants' => $newSuitcaseVariants,
            'featuredBags' => $featuredBags,
            'newBagVariants' => $newBagVariants,
            'landingCategory' => $landingCategory,
        ]);
    }

    private function createCardFromProduct(
        Product $product,
        AvailabilityService $availabilityService,
    ): ?array {
        $variant = $product->getMasterVariant();

        if ($variant === null || !$variant->isActive()) {
            return null;
        }

        return [
            'product' => $product,
            'variant' => $variant,
            'master' => $variant,
            'mediaPath' => $this->variantImagePathResolver->fromVariant($variant),
            'availability' => $availabilityService->get($variant),
        ];
    }

    private function createCardFromVariant(
        ProductVariant $variant,
        AvailabilityService $availabilityService,
    ): ?array {
        if (!$variant->isActive()) {
            return null;
        }

        $product = $variant->getProduct();

        if (!$product instanceof Product || !$product->isActive()) {
            return null;
        }

        $master = $product->getMasterVariant();

        if ($master === null || !$master->isActive()) {
            $master = $variant;
        }

        return [
            'product' => $product,
            'variant' => $variant,
            'master' => $master,
            'mediaPath' => $this->variantImagePathResolver->fromVariant($variant),
            'availability' => $availabilityService->get($variant),
        ];
    }
}