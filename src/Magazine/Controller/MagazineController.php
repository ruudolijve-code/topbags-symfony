<?php

declare(strict_types=1);

namespace App\Magazine\Controller;

use App\Catalog\Service\VariantImagePathResolver;
use App\Magazine\Entity\MagazineArticle;
use App\Magazine\Repository\MagazineArticleRepository;
use App\Magazine\Service\LightweightSuitcaseProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MagazineController extends AbstractController
{
    #[Route('/magazine', name: 'magazine_index', methods: ['GET'])]
    public function shopIndex(
        MagazineArticleRepository $articles,
    ): Response {
        return $this->renderMagazineIndex(
            MagazineArticle::CONTEXT_SHOP,
            $articles
        );
    }

    #[Route('/bags/magazine', name: 'bags_magazine_index', methods: ['GET'])]
    public function bagsIndex(
        MagazineArticleRepository $articles,
    ): Response {
        return $this->renderMagazineIndex(
            MagazineArticle::CONTEXT_BAGS,
            $articles
        );
    }

    #[Route('/magazine/{slug}', name: 'magazine_show', methods: ['GET'])]
    public function shopShow(
        string $slug,
        MagazineArticleRepository $articles,
        VariantImagePathResolver $imagePathResolver,
        LightweightSuitcaseProvider $lightweightSuitcaseProvider,
    ): Response {
        return $this->renderMagazineArticle(
            $slug,
            MagazineArticle::CONTEXT_SHOP,
            $articles,
            $imagePathResolver,
            $lightweightSuitcaseProvider
        );
    }

    #[Route(
        '/bags/magazine/{slug}',
        name: 'bags_magazine_show',
        methods: ['GET']
    )]
    public function bagsShow(
        string $slug,
        MagazineArticleRepository $articles,
        VariantImagePathResolver $imagePathResolver,
        LightweightSuitcaseProvider $lightweightSuitcaseProvider,
    ): Response {
        return $this->renderMagazineArticle(
            $slug,
            MagazineArticle::CONTEXT_BAGS,
            $articles,
            $imagePathResolver,
            $lightweightSuitcaseProvider
        );
    }

    private function renderMagazineIndex(
        string $context,
        MagazineArticleRepository $articles,
    ): Response {
        $featured = $articles->findFeaturedByContext($context);

        $magazine = match ($context) {
            MagazineArticle::CONTEXT_BAGS => [
                'pageTitle' => 'Topbags Fashionmagazine | Tassenadvies & inspiratie',
                'label' => 'Topbags Fashionmagazine',
                'heading' => 'Tassenadvies, trends en inspiratie',
                'intro' => 'Ontdek alles over damestassen, rugzakken, laptoptassen, portemonnees, accessoires, materialen en onderhoud. Praktisch advies en inspiratie van de tassenspecialist.',
                'showRoute' => 'bags_magazine_show',
                'emptyMessage' => 'Er zijn nog geen artikelen over tassen en accessoires gepubliceerd.',
            ],

            default => [
                'pageTitle' => 'Topbags Reismagazine | Kofferadvies & reistips',
                'label' => 'Topbags Reismagazine',
                'heading' => 'Kofferadvies, handbagageregels en reistips',
                'intro' => 'Praktische uitleg van de kofferspecialist van Twente. Ontdek alles over koffers, handbagage, vliegmaatschappijen, reparaties, verhuur en slimme reistips.',
                'showRoute' => 'magazine_show',
                'emptyMessage' => 'Er zijn nog geen reisartikelen gepubliceerd.',
            ],
        };

        return $this->render('magazine/index.html.twig', [
            'context' => $context,
            'magazine' => $magazine,
            'featured' => $featured,
            'articles' => $articles->findPublishedByContextExceptFeatured(
                $context,
                $featured
            ),
        ]);
    }

    private function renderMagazineArticle(
        string $slug,
        string $context,
        MagazineArticleRepository $articles,
        VariantImagePathResolver $imagePathResolver,
        LightweightSuitcaseProvider $lightweightSuitcaseProvider,
    ): Response {
        $article = $articles->findPublishedBySlugWithRelations(
            $slug,
            $context
        );

        if ($article === null) {
            throw $this->createNotFoundException(
                'Magazineartikel niet gevonden.'
            );
        }

        $relatedProductItems = [];

        foreach ($article->getRelatedProducts() as $product) {
            $variant = $product->getMasterVariant();

            if ($variant === null) {
                foreach ($product->getVariants() as $candidate) {
                    if ($candidate->isActive()) {
                        $variant = $candidate;
                        break;
                    }
                }
            }

            if ($variant === null) {
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
            'context' => $context,
            'relatedProductItems' => $relatedProductItems,

            /*
             * Alleen relevant voor het koffermagazine.
             * De Twig-template controleert daarnaast nog op de slug.
             */
            'lightweightSuitcaseItems' => $context === MagazineArticle::CONTEXT_SHOP
                ? $lightweightSuitcaseProvider->getItems(8)
                : [],
        ]);
    }
}