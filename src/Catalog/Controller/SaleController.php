<?php

namespace App\Catalog\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Repository\CategoryRepository;
use App\Catalog\Repository\ProductRepository;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use App\Shared\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SaleController extends AbstractController
{
    private const PER_PAGE = 16;

    public function __construct(
        private VariantImagePathResolver $variantImagePathResolver
    ) {
    }

    #[Route('/sale', name: 'sale_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        AvailabilityService $availabilityService,
        PaginationService $paginationService
    ): Response {
        $context = Product::CONTEXT_SHOP;
        $page = max(1, $request->query->getInt('page', 1));

        $totalItems = $productRepository->countSaleProductsForContext($context);

        $pagination = $paginationService->create(
            page: $page,
            limit: self::PER_PAGE,
            totalItems: $totalItems
        );

        $products = $productRepository->findSaleProductsForContext(
            context: $context,
            limit: $pagination->getLimit(),
            offset: $pagination->getOffset()
        );

        $items = [];

        foreach ($products as $product) {
            $displayVariant = null;

            foreach ($product->getVariants() as $variant) {
                if (!$variant->isActive() || !$variant->isSaleActive()) {
                    continue;
                }

                $displayVariant = $variant;
                break;
            }

            if ($displayVariant === null) {
                $master = $product->getMasterVariant();

                if ($master !== null && $master->isActive() && $master->isSaleActive()) {
                    $displayVariant = $master;
                }
            }

            if ($displayVariant === null) {
                continue;
            }

            $items[] = [
                'product' => $product,
                'variant' => $displayVariant,
                'master' => $product->getMasterVariant(),
                'mediaPath' => $this->variantImagePathResolver->fromVariant($displayVariant),
                'availability' => $availabilityService->get($displayVariant),
            ];
        }

        return $this->render('sale/index.html.twig', [
            'context' => $context,
            'currentContext' => $context,
            'items' => $items,
            'categories' => $categoryRepository->findForContext($context),
            'pagination' => $pagination,
            'pageTitle' => 'Sale',
            'pageIntro' => 'Ontdek afgeprijsde koffers, reistassen en accessoires.',
        ]);
    }
}