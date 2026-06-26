<?php

declare(strict_types=1);

namespace App\Magazine\Controller;

use App\Catalog\Service\VariantImagePathResolver;
use App\Magazine\Repository\MagazineArticleRepository;
use App\Magazine\Service\LightweightSuitcaseProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MagazineController extends AbstractController
{
    #[Route('/magazine', name: 'magazine_index', methods: ['GET'])]
    public function index(
        MagazineArticleRepository $articles
    ): Response {
        $featured = $articles->findFeatured();

        return $this->render('magazine/index.html.twig', [
            'featured' => $featured,
            'articles' => $articles->findPublishedExceptFeatured($featured),
        ]);
    }

    #[Route('/magazine/{slug}', name: 'magazine_show', methods: ['GET'])]
    public function show(
        string $slug,
        MagazineArticleRepository $articles,
        VariantImagePathResolver $imagePathResolver,
        LightweightSuitcaseProvider $lightweightSuitcaseProvider,
    ): Response {
        $article = $articles->findPublishedBySlugWithRelations($slug);

        if (!$article) {
            throw $this->createNotFoundException('Magazineartikel niet gevonden.');
        }

        $relatedProductItems = [];

        foreach ($article->getRelatedProducts() as $product) {
            $variant = $product->getMasterVariant();

            if (!$variant) {
                foreach ($product->getVariants() as $candidate) {
                    if ($candidate->isActive()) {
                        $variant = $candidate;
                        break;
                    }
                }
            }

            if (!$variant) {
                continue;
            }

            $relatedProductItems[] = [
                'product' => $product,
                'variant' => $variant,
                'mediaPath' => $imagePathResolver->fromVariant($variant),
            ];
        }

        return $this->render('magazine/show.html.twig', [
            'article' => $article,
            'relatedProductItems' => $relatedProductItems,
            'lightweightSuitcaseItems' => $lightweightSuitcaseProvider->getItems(8),
        ]);
    }
}
