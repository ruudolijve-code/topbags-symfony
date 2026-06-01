<?php

declare(strict_types=1);

namespace App\Guide\Controller;

use App\Guide\Repository\TravelAgencyLandingPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TravelAgencyLandingPageController extends AbstractController
{
    private const CANONICAL_HOST = 'https://topbags.nl';

    #[Route('/bagagegids/reisbureaus-twente', name: 'travel_agency_hub', methods: ['GET'])]
    public function index(TravelAgencyLandingPageRepository $repository): Response
    {
        return $this->render('travel_agency/index.html.twig', [
            'pages' => $repository->findActiveOrdered(),
            'canonical_url' => self::CANONICAL_HOST . $this->generateUrl('travel_agency_hub'),
        ]);
    }

    #[Route('/bagagegids/reisbureau/{slug}', name: 'travel_agency_show', methods: ['GET'])]
    public function show(string $slug, TravelAgencyLandingPageRepository $repository): Response
    {
        $page = $repository->findActiveBySlug($slug);

        if ($page === null) {
            throw $this->createNotFoundException('Reisbureaupagina niet gevonden');
        }

        return $this->render('travel_agency/show.html.twig', [
            'page' => $page,
            'canonical_url' => self::CANONICAL_HOST . $this->generateUrl('travel_agency_show', [
                'slug' => $page->getSlug(),
            ]),
        ]);
    }
}