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
    #[Route('/sitemap.xml', name: 'sitemap_xml', defaults: ['_format' => 'xml'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        BrandRepository $brandRepository,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $urls = [];

        // Vaste pagina’s
        $urls[] = [
            'loc' => $urlGenerator->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'priority' => '1.0',
            'changefreq' => 'daily',
        ];

        $urls[] = [
            'loc' => $urlGenerator->generate('shop_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'priority' => '0.9',
            'changefreq' => 'daily',
        ];

        $urls[] = [
            'loc' => $urlGenerator->generate('bags_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'priority' => '0.8',
            'changefreq' => 'weekly',
        ];

        $urls[] = [
            'loc' => $urlGenerator->generate('brand_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'priority' => '0.7',
            'changefreq' => 'weekly',
        ];

        $urls[] = [
            'loc' => $urlGenerator->generate('service_store', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'priority' => '0.7',
            'changefreq' => 'monthly',
        ];

        // Categorieën
        foreach ($categoryRepository->findActiveForSitemap() as $category) {
            $urls[] = [
                'loc' => $urlGenerator->generate('category_show', [
                    'slug' => $category->getSlug(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ];
        }

        // Merken
        foreach ($brandRepository->findActiveForSitemap() as $brand) {
            $urls[] = [
                'loc' => $urlGenerator->generate('brand_show', [
                    'slug' => $brand->getSlug(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => '0.7',
                'changefreq' => 'weekly',
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
                'loc' => $urlGenerator->generate('product_show', [
                    'slug' => $product->getSlug(),
                    'colorSlug' => $masterVariant->getSupplierColorSlug(),
                    'variantSku' => $masterVariant->getVariantSku(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => '0.9',
                'changefreq' => 'weekly',
            ];
        }

        $response = $this->render('seo/sitemap.xml.twig', [
            'urls' => $urls,
        ]);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}