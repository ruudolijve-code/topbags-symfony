<?php

declare(strict_types=1);

namespace App\Catalog\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Repository\ProductRepository;
use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use App\Seo\Service\ProductSchemaBuilder;
use App\Seo\Service\ProductVariantSeoResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    public function __construct(
        private readonly VariantImagePathResolver $variantImagePathResolver,
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
        AvailabilityService $availabilityService,
        ProductVariantSeoResolver $seoResolver,
        ProductSchemaBuilder $productSchemaBuilder,
        ProductRepository $productRepository,
    ): Response {
        $variant = $variantRepository->findOneForGridBySku($variantSku);

        if ($variant === null || !$variant->isActive()) {
            throw $this->createNotFoundException();
        }

        $product = $variant->getProduct();

        if ($product === null || !$product->isActive()) {
            throw $this->createNotFoundException();
        }

        /*
         * Context bepalen.
         *
         * De queryparameter ?context=bags of ?context=shop kan worden gebruikt
         * voor de navigatie/header. Als deze ontbreekt of ongeldig is,
         * gebruiken we de daadwerkelijke productcontext.
         */
        $requestedContext = $request->query->get('context');

        if (!in_array($requestedContext, [
            Product::CONTEXT_SHOP,
            Product::CONTEXT_BAGS,
        ], true)) {
            $requestedContext = $product->getProductContext() ?: Product::CONTEXT_SHOP;
        }

        /*
         * Canonieke product-URL afdwingen.
         */
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

            return $this->redirectToRoute(
                'product_show',
                $routeParams,
                Response::HTTP_MOVED_PERMANENTLY
            );
        }

        /*
         * Serie- en maatvarianten zijn alleen relevant binnen de travelshop.
         */
        $sizeSiblings = [];

        if ($product->getProductContext() === Product::CONTEXT_SHOP) {
            $sizeSiblings = $productRepository->findSizeSiblings($product);
        }

        /*
         * Beschikbaarheid.
         */
        $availability = $availabilityService->get($variant);

        /*
         * SEO.
         */
        $seoTitle = $seoResolver->resolveTitle($variant);
        $seoDescription = $seoResolver->resolveDescription($variant);
        $productSchema = $productSchemaBuilder->build(
            $variant,
            $seoDescription
        );

        /*
         * USP-blok op basis van de daadwerkelijke productcontext.
         *
         * Shop:
         * nadruk op koffers, reizen en reparatieservice.
         *
         * Bags:
         * nadruk op tassen, accessoires en persoonlijk advies.
         */
        $purchaseBenefits = match ($product->getProductContext()) {
            Product::CONTEXT_BAGS => [
                'title' => 'Waarom kopen bij Topbags?',
                'items' => [
                    'Spaar direct Travelmiles voor extra voordeel',
                    'Persoonlijk advies in onze winkel in Hengelo',
                    'Specialist in tassen, portemonnees en accessoires',
                ],
                'footer' => 'Online bestellen of afhalen in onze winkel aan de Wemenstraat in Hengelo.',
            ],

            default => [
                'title' => 'Waarom kopen bij Topbags?',
                'items' => [
                    'Spaar direct Travelmiles voor extra voordeel',
                    'Eigen reparatieservice in Hengelo',
                    'Al generaties lang kofferspecialist van Twente',
                ],
                'footer' => 'Online bestellen of afhalen in onze winkel aan de Wemenstraat in Hengelo.',
            ],
        };

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'variant' => $variant,
            'master' => $product->getMasterVariant(),

            'mediaPath' => $this->variantImagePathResolver->fromVariant($variant),
            'imageBasePath' => $this->variantImagePathResolver->fromSku(
                $variant->getVariantSku()
            ),

            'availability' => $availability,
            'sizeSiblings' => $sizeSiblings,

            /*
             * USP / serviceblok.
             */
            'purchaseBenefits' => $purchaseBenefits,

            /*
             * SEO.
             */
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'productSchema' => $productSchema,

            /*
             * Header / menu / context switcher.
             */
            'currentContext' => $requestedContext,
            'activeContext' => $requestedContext,
        ]);
    }
}