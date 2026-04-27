<?php

namespace App\Feed\Controller;

use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\VariantImagePathResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MetaProductFeedController extends AbstractController
{
    #[Route('/feed/meta-products.xml', name: 'feed_meta_products_xml', methods: ['GET'])]
    public function index(
        ProductVariantRepository $variantRepository,
        VariantImagePathResolver $imagePathResolver,
    ): Response {
        $variants = $variantRepository->findActiveForMetaFeed();

        $feedItems = [];

        foreach ($variants as $variant) {
            $imagePath = $imagePathResolver->fromVariant($variant);

            if ($imagePath === null || trim($imagePath) === '') {
                continue;
            }

            $feedItems[] = [
                'variant' => $variant,
                'imagePath' => $imagePath,
            ];
        }

        $response = $this->render('feed/meta-products.xml.twig', [
            'feedItems' => $feedItems,
        ]);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}