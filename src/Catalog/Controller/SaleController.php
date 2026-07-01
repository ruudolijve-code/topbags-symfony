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

    private const ALLOWED_CONTEXTS = [
        Product::CONTEXT_SHOP,
        Product::CONTEXT_BAGS,
    ];

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
        $requestedContext = $request->query->get('context');

        $context = \in_array($requestedContext, self::ALLOWED_CONTEXTS, true)
            ? $requestedContext
            : null;

        $page = max(1, $request->query->getInt('page', 1));

        $totalItems = $productRepository->countSaleVariantsForContext($context);

        $pagination = $paginationService->create(
            page: $page,
            limit: self::PER_PAGE,
            totalItems: $totalItems
        );

        $variants = $productRepository->findSaleVariantsForContext(
            context: $context,
            limit: $pagination->getLimit(),
            offset: $pagination->getOffset()
        );

        $items = [];

        foreach ($variants as $variant) {
            $product = $variant->getProduct();

            if ($product === null) {
                continue;
            }

            $items[] = [
                'product' => $product,
                'variant' => $variant,
                'master' => $product->getMasterVariant(),
                'mediaPath' => $this->variantImagePathResolver->fromVariant($variant),
                'availability' => $availabilityService->get($variant),
            ];
        }

        [$pageHeading, $pageIntro] = $this->resolveSaleCopy($context);

        return $this->render('sale/index.html.twig', [
            'context' => $context,
            'currentContext' => $context,
            'activeContext' => $context,
            'items' => $items,
            'categories' => $context ? $categoryRepository->findForContext($context) : [],
            'pagination' => $pagination,
            'pageTitle' => 'Sale',
            'pageHeading' => $pageHeading,
            'pageIntro' => $pageIntro,
            'activeSaleContext' => $context,
            'saleContexts' => [
                [
                    'label' => 'Alles',
                    'context' => null,
                    'url' => $this->generateUrl('sale_index'),
                ],
                [
                    'label' => 'Koffers & reistassen',
                    'context' => Product::CONTEXT_SHOP,
                    'url' => $this->generateUrl('sale_index', ['context' => Product::CONTEXT_SHOP]),
                ],
                [
                    'label' => 'Tassen & accessoires',
                    'context' => Product::CONTEXT_BAGS,
                    'url' => $this->generateUrl('sale_index', ['context' => Product::CONTEXT_BAGS]),
                ],
            ],
        ]);
    }

    private function resolveSaleCopy(?string $context): array
    {
        return match ($context) {
            Product::CONTEXT_SHOP => [
                'Afgeprijsde koffers, reistassen en reisaccessoires',
                'Ontdek onze sale met geselecteerde koffers, reistassen, handbagage, rugzakken en reisaccessoires.',
            ],
            Product::CONTEXT_BAGS => [
                'Afgeprijsde tassen, rugtassen, laptoptassen en accessoires',
                'Ontdek onze sale met geselecteerde damestassen, shoppers, crossbody’s, rugtassen, laptoptassen en accessoires.',
            ],
            default => [
                'Sale: afgeprijsde koffers, tassen en reisaccessoires',
                'Ontdek alle geselecteerde sale-artikelen van Topbags: van koffers en reistassen tot damestassen, rugtassen, laptoptassen en accessoires.',
            ],
        };
    }
}