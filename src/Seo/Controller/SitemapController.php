<?php

namespace App\Seo\Controller;

use App\Catalog\Repository\BrandRepository;
use App\Catalog\Repository\CategoryRepository;
use App\Catalog\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapController extends AbstractController
{
    private const LOCAL_BRAND_LANDINGS = [
        [
            'brandSlug' => 'samsonite',
            'type' => 'koffers',
            'cities' => [
                'hengelo',
                'almelo',
                'enschede',
                'haaksbergen',
                'borne',
                'oldenzaal',
                'delden',
            ],
        ],
        [
            'brandSlug' => 'american-tourister',
            'type' => 'koffers',
            'cities' => [
                'hengelo',
                'almelo',
                'enschede',
                'haaksbergen',
                'borne',
                'oldenzaal',
                'delden',
            ],
        ],
        [
            'brandSlug' => 'eastpak',
            'type' => 'rugzakken',
            'cities' => [
                'hengelo',
                'almelo',
                'enschede',
                'haaksbergen',
                'borne',
                'oldenzaal',
                'delden',
            ],
        ],
        [
            'brandSlug' => 'tommy-hilfiger',
            'type' => 'tassen',
            'cities' => [
                'hengelo',
                'almelo',
                'enschede',
                'haaksbergen',
                'borne',
                'oldenzaal',
                'delden',
            ],
        ],
        [
            'brandSlug' => 'smaak-amsterdam',
            'type' => 'tassen',
            'cities' => [
                'hengelo',
                'almelo',
                'enschede',
                'haaksbergen',
                'borne',
                'oldenzaal',
                'delden',
            ],
        ],
    ];

    #[Route('/sitemap.xml', name: 'sitemap_xml', defaults: ['_format' => 'xml'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        BrandRepository $brandRepository,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $urls = [];

        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $baseUrl = rtrim((string) ($_ENV['APP_URL'] ?? 'https://www.topbags.nl'), '/');

        // Vaste pagina’s
        $urls[] = [
            'loc' => $this->absoluteUrl($urlGenerator, 'home', [], $baseUrl),
            'priority' => '1.0',
            'changefreq' => 'daily',
            'lastmod' => $today,
        ];

        $urls[] = [
            'loc' => $this->absoluteUrl($urlGenerator, 'shop_index', [], $baseUrl),
            'priority' => '0.9',
            'changefreq' => 'daily',
            'lastmod' => $today,
        ];

        $urls[] = [
            'loc' => $this->absoluteUrl($urlGenerator, 'bags_index', [], $baseUrl),
            'priority' => '0.8',
            'changefreq' => 'weekly',
            'lastmod' => $today,
        ];

        $urls[] = [
            'loc' => $this->absoluteUrl($urlGenerator, 'brand_index', [], $baseUrl),
            'priority' => '0.7',
            'changefreq' => 'weekly',
            'lastmod' => $today,
        ];

        $urls[] = [
            'loc' => $this->absoluteUrl($urlGenerator, 'service_store', [], $baseUrl),
            'priority' => '0.7',
            'changefreq' => 'monthly',
            'lastmod' => $today,
        ];

        // Lokale merk/plaats landingspagina’s
        foreach (self::LOCAL_BRAND_LANDINGS as $landing) {
            foreach ($landing['cities'] as $citySlug) {
                $urls[] = [
                    'loc' => $this->absoluteUrl($urlGenerator, 'local_brand_landing', [
                        'brandSlug' => $landing['brandSlug'],
                        'type' => $landing['type'],
                        'citySlug' => $citySlug,
                    ], $baseUrl),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                    'lastmod' => $today,
                ];
            }
        }

        // Categorieën
        foreach ($categoryRepository->findActiveForSitemap() as $category) {
            $urls[] = [
                'loc' => $this->absoluteUrl($urlGenerator, 'category_show', [
                    'slug' => $category->getSlug(),
                ], $baseUrl),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $today,
            ];
        }

        // Merken
        foreach ($brandRepository->findActiveForSitemap() as $brand) {
            $urls[] = [
                'loc' => $this->absoluteUrl($urlGenerator, 'brand_show', [
                    'slug' => $brand->getSlug(),
                ], $baseUrl),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $today,
            ];
        }

        // Producten
        foreach ($productRepository->findActiveForSitemap() as $product) {
            $masterVariant = null;

            foreach ($product->getVariants() as $variant) {
                if ($variant->isActive() && $variant->isMaster()) {
                    $masterVariant = $variant;
                    break;
                }
            }

            if (!$masterVariant) {
                continue;
            }

            if (!$masterVariant->getSupplierColorSlug() || !$masterVariant->getVariantSku()) {
                continue;
            }

            $urls[] = [
                'loc' => $this->absoluteUrl($urlGenerator, 'product_show', [
                    'slug' => $product->getSlug(),
                    'colorSlug' => $masterVariant->getSupplierColorSlug(),
                    'variantSku' => $masterVariant->getVariantSku(),
                ], $baseUrl),
                'priority' => '0.9',
                'changefreq' => 'weekly',
                'lastmod' => $today,
            ];
        }

        $response = $this->render('seo/sitemap.xml.twig', [
            'urls' => $urls,
        ]);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }

    private function absoluteUrl(
        UrlGeneratorInterface $urlGenerator,
        string $route,
        array $parameters,
        string $baseUrl
    ): string {
        return $baseUrl . $urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}