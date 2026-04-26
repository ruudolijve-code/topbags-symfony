<?php

namespace App\Feed\Controller;

use App\Catalog\Repository\ProductVariantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MetaProductFeedController extends AbstractController
{
    #[Route('/feed/meta-products.xml', name: 'feed_meta_products_xml', methods: ['GET'])]
    public function index(
        ProductVariantRepository $variantRepository,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $variants = $variantRepository->findActiveForMetaFeed();

        $response = $this->render('feed/meta-products.xml.twig', [
            'variants' => $variants,
            'urlGenerator' => $urlGenerator,
        ]);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}