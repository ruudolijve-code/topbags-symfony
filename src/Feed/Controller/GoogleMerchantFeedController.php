<?php

namespace App\Feed\Controller;

use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\VariantImagePathResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleMerchantFeedController extends AbstractController
{
    #[Route('/feed/google-merchant.xml', name: 'feed_google_merchant_xml', methods: ['GET'])]
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

        $response = $this->render('feed/google-merchant.xml.twig', [
            'feedItems' => $feedItems,
        ]);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}