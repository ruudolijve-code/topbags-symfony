<?php

namespace App\Catalog\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    public function __construct(
        private VariantImagePathResolver $variantImagePathResolver
    ) {
    }

    #[Route(
        '/product/{slug}/{colorSlug}/{variantSku}',
        name: 'product_show',
        requirements: [
            'slug' => '[a-z0-9\-]+',
            'colorSlug' => '[a-z0-9\-]+',
            'variantSku' => '[A-Za-z0-9\-]+',
        ],
        methods: ['GET']
    )]
    public function show(
        Request $request,
        string $slug,
        string $colorSlug,
        string $variantSku,
        ProductVariantRepository $variantRepository,
        AvailabilityService $availabilityService
    ): Response {
        $variant = $variantRepository->findOneForGridBySku($variantSku);

        if ($variant === null || !$variant->isActive() || $variant->getProduct() === null) {
            throw $this->createNotFoundException();
        }

        $product = $variant->getProduct();

        $requestedContext = $request->query->get('context');

        if (!in_array($requestedContext, [Product::CONTEXT_SHOP, Product::CONTEXT_BAGS], true)) {
            $requestedContext = $product->getProductContext() ?: Product::CONTEXT_SHOP;
        }

        if (
            $slug !== $product->getSlug()
            || $colorSlug !== $variant->getSupplierColorSlug()
        ) {
            $routeParams = [
                'slug' => $product->getSlug(),
                'colorSlug' => $variant->getSupplierColorSlug(),
                'variantSku' => $variant->getVariantSku(),
            ];

            if ($requestedContext === Product::CONTEXT_BAGS) {
                $routeParams['context'] = Product::CONTEXT_BAGS;
            }

            return $this->redirectToRoute('product_show', $routeParams, 301);
        }

        $availability = $availabilityService->get($variant);
        $mediaPath = $this->variantImagePathResolver->fromVariant($variant);
        $imageBasePath = $this->variantImagePathResolver->fromSku($variant->getVariantSku());

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'variant' => $variant,
            'master' => $product->getMasterVariant(),
            'mediaPath' => $mediaPath,
            'imageBasePath' => $imageBasePath,
            'availability' => $availability,

            // Belangrijk voor header/menu/context switcher
            'currentContext' => $requestedContext,
            'activeContext' => $requestedContext,
        ]);
    }
}