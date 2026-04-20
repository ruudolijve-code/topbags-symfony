<?php

namespace App\Guide\Controller;

use App\Guide\Entity\Airline;
use App\Guide\Entity\Faq;
use App\Guide\Repository\AirlineRepository;
use App\Guide\Repository\AirlineTicketTypeRepository;
use App\Guide\Service\TicketBaggageSummaryBuilder;
use App\Guide\Repository\FaqRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AirlineController extends AbstractController
{
    public function __construct(
        private AirlineRepository $airlineRepository,
        private FaqRepository $faqRepository,
        private AirlineTicketTypeRepository $ticketTypeRepository,
        private TicketBaggageSummaryBuilder $summaryBuilder
    ) {}

    /**
     * =============================
     * Airline index
     * =============================
     */
    #[Route('/vliegmaatschappijen', name: 'airline_index')]
    public function index(): Response
    {
        return $this->render('airline/index.html.twig', [
            'airlines' => $this->airlineRepository->findActive(),
        ]);
    }

    /**
     * =============================
     * Airline landing
     * =============================
     */
    #[Route('/vliegmaatschappijen/{slug}', name: 'airline_show')]
    public function show(string $slug): Response
    {
        /** @var Airline|null $airline */
        $airline = $this->airlineRepository->findActiveBySlug($slug);

        if (!$airline) {
            throw $this->createNotFoundException('Vliegmaatschappij niet gevonden.');
        }

        // 1️⃣ Actieve tickettypes
        $ticketTypes = $this->ticketTypeRepository
            ->findActiveForAirline($airline);

        // 2️⃣ Per ticket: baggage summary (zoals Guide)
        $ticketSummaries = [];

        foreach ($ticketTypes as $ticket) {
            $ticketSummaries[$ticket->getSlug()] =
                $this->summaryBuilder->buildForTicket($airline, $ticket);
        }

        // 3️⃣ Airline-brede fallback
        $fallbackSummary = $this->summaryBuilder
            ->buildFallbackForAirline($airline);

        // 4️⃣ FAQ’s
        $faqs = $this->faqRepository
            ->findForContext('plane', $airline);

        return $this->render('airline/show.html.twig', [
            'airline'          => $airline,
            'ticketTypes'      => $ticketTypes,
            'ticketSummaries'  => $ticketSummaries,   // ✅ DIT MISSE JE
            'fallbackSummary'  => $fallbackSummary,
            'faqs'             => $faqs,
        ]);
    }
}