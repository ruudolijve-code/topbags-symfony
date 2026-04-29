<?php

namespace App\Catalog\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Service\VariantImagePathResolver;
use App\Catalog\Repository\BrandRepository;
use App\Catalog\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocalBrandLandingController extends AbstractController
{
    private const ALLOWED_TYPES = [
        'koffers' => [
            'label' => 'koffers',
            'singular' => 'koffer',
            'context' => Product::CONTEXT_SHOP,
        ],
        'handbagage' => [
            'label' => 'handbagage',
            'singular' => 'handbagage',
            'context' => Product::CONTEXT_SHOP,
        ],
        'tassen' => [
            'label' => 'tassen',
            'singular' => 'tas',
            'context' => Product::CONTEXT_BAGS,
        ],
        'rugzakken' => [
            'label' => 'rugzakken',
            'singular' => 'rugzak',
            'context' => Product::CONTEXT_BAGS,
        ],
    ];

    private const ALLOWED_CITIES = [
        'hengelo' => [
            'name' => 'Hengelo',
            'intro' => 'Kom langs bij Holtkamp Tassen & Koffers in het centrum van Hengelo.',
            'distance' => null,
            'is_store_city' => true,
        ],
        'almelo' => [
            'name' => 'Almelo',
            'intro' => 'Woon je in Almelo? Dan ben je snel bij onze winkel in Hengelo voor persoonlijk advies.',
            'distance' => 'ongeveer 20 minuten rijden',
            'is_store_city' => false,
        ],
        'enschede' => [
            'name' => 'Enschede',
            'intro' => 'Vanuit Enschede ben je snel in Hengelo om onze collectie in de winkel te bekijken.',
            'distance' => 'ongeveer 15 tot 20 minuten rijden',
            'is_store_city' => false,
        ],
        'haaksbergen' => [
            'name' => 'Haaksbergen',
            'intro' => 'Vanuit Haaksbergen is onze winkel in Hengelo goed bereikbaar voor advies en service.',
            'distance' => 'ongeveer 25 minuten rijden',
            'is_store_city' => false,
        ],
        'borne' => [
            'name' => 'Borne',
            'intro' => 'Woon je in Borne? Onze winkel in Hengelo ligt dichtbij voor deskundig koffer- en tassenadvies.',
            'distance' => 'ongeveer 10 minuten rijden',
            'is_store_city' => false,
        ],
        'oldenzaal' => [
            'name' => 'Oldenzaal',
            'intro' => 'Ook vanuit Oldenzaal ben je snel bij Holtkamp Tassen & Koffers in Hengelo.',
            'distance' => 'ongeveer 20 minuten rijden',
            'is_store_city' => false,
        ],
        'delden' => [
            'name' => 'Delden',
            'intro' => 'Vanuit Delden is Holtkamp Tassen & Koffers in Hengelo dichtbij.',
            'distance' => 'ongeveer 10 tot 15 minuten rijden',
            'is_store_city' => false,
        ],
        'goor' => [
            'name' => 'Goor',
            'intro' => 'Vanuit Goor kun je gemakkelijk naar onze winkel in Hengelo komen voor advies.',
            'distance' => 'ongeveer 20 minuten rijden',
            'is_store_city' => false,
        ],
        'rijssen' => [
            'name' => 'Rijssen',
            'intro' => 'Woon je in Rijssen? Bekijk de collectie online of kom langs in onze winkel in Hengelo.',
            'distance' => 'ongeveer 30 minuten rijden',
            'is_store_city' => false,
        ],
        'wierden' => [
            'name' => 'Wierden',
            'intro' => 'Vanuit Wierden is onze winkel in Hengelo goed bereikbaar voor persoonlijk advies.',
            'distance' => 'ongeveer 20 minuten rijden',
            'is_store_city' => false,
        ],
    ];

    #[Route(
        '/{brandSlug}-{type}-{citySlug}',
        name: 'local_brand_landing',
        requirements: [
            'brandSlug' => '[a-z0-9-]+',
            'type' => 'koffers|tassen|rugzakken|handbagage',
            'citySlug' => 'hengelo|almelo|enschede|haaksbergen|borne|oldenzaal|delden|goor|rijssen|wierden',
        ]
    )]

    public function show(
        string $brandSlug,
        string $type,
        string $citySlug,
        BrandRepository $brandRepository,
        ProductRepository $productRepository,
        VariantImagePathResolver $imagePathResolver,
    ): Response {
        $brand = $brandRepository->findOneBy([
            'slug' => $brandSlug,
        ]);

        if (!$brand) {
            throw $this->createNotFoundException('Merk niet gevonden.');
        }

        $typeConfig = self::ALLOWED_TYPES[$type] ?? null;
        $cityConfig = self::ALLOWED_CITIES[$citySlug] ?? null;

        if (!$typeConfig || !$cityConfig) {
            throw $this->createNotFoundException('Pagina niet gevonden.');
        }

        $context = $typeConfig['context'];

        $products = $productRepository->findActiveByBrandForContext(
            $brand,
            $context,
            12
        );

        $items = [];

        foreach ($products as $product) {
            $displayVariant = null;

            foreach ($product->getVariants() as $variant) {
                $displayVariant = $variant;
                break;
            }

            if (!$displayVariant) {
                continue;
            }

            $items[] = [
                'product' => $product,
                'displayVariant' => $displayVariant,
                'masterVariant' => $displayVariant,
                'mediaPath' => $imagePathResolver->fromVariant($displayVariant),
                'availability' => null,
            ];
        }

        return $this->render('shop/local_brand_landing/show.html.twig', [
            'brand' => $brand,
            'type' => $type,
            'typeConfig' => $typeConfig,
            'citySlug' => $citySlug,
            'city' => $cityConfig,
            'context' => $context,
            'products' => $products,
            'items' => $items,
            'allowedCities' => self::ALLOWED_CITIES,
            'allowedTypes' => self::ALLOWED_TYPES,
        ]);
    }
}