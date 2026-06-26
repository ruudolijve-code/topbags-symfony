<?php

declare(strict_types=1);

namespace App\Magazine\Controller;

use App\Magazine\Repository\MagazineArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Catalog\Service\VariantImagePathResolver;
use App\Magazine\Service\LightweightSuitcaseProvider;

final class MagazineController extends AbstractController
{
    #[Route('/magazine', name: 'magazine_index', methods: ['GET'])]
    public function index(MagazineArticleRepository $articles): Response
    {
        return $this->render('magazine/index.html.twig', [
            'articles' => $articles->findBy(
                ['isPublished' => true],
                ['publishedAt' => 'DESC', 'id' => 'DESC']
            ),
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