<?php

namespace App\Guide\Controller;

use App\Guide\Dto\GuideInput;
use App\Guide\Repository\AirlineRepository;
use App\Guide\Repository\AirlineTicketTypeRepository;
use App\Guide\Repository\FaqRepository;
use App\Guide\Service\GuideEngine;
use App\Guide\Service\GuideResultBuilder;
use App\Guide\Service\ProfileFactory;
use App\Guide\Service\TicketBaggageSummaryBuilder;
use App\Guide\Service\TravelProfileResolver;
use App\Guide\Service\TravelVolumeAdvisor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GuideController extends AbstractController
{
    #[Route('/gids', name: 'guide_index', methods: ['GET'])]
    public function index(
        Request $request,
        AirlineRepository $airlineRepository,
        AirlineTicketTypeRepository $ticketTypeRepository,
        TicketBaggageSummaryBuilder $summaryBuilder,
        GuideResultBuilder $resultBuilder,
        TravelProfileResolver $profileResolver,
        ProfileFactory $profileFactory,
        TravelVolumeAdvisor $volumeAdvisor,
        GuideEngine $guideEngine,
        FaqRepository $faqRepository,
    ): Response {
        $query = $request->query->all();

        $transport = $this->getStringQuery($query, 'transport');
        $isFlight = $transport === 'plane';

        $step = $this->determineStep($query, $isFlight);
        $flow = $this->buildFlow($isFlight);

        $airlines = [];
        $airline = null;
        $ticketTypes = [];
        $ticketSummaries = [];

        if ($step === 'airline') {
            $airlines = $airlineRepository->findActive();
        }

        $airlineSlug = $this->getStringQuery($query, 'airline');

        if ($isFlight && $airlineSlug !== null) {
            $airline = $airlineRepository->findOneBy([
                'slug' => $airlineSlug,
                'isActive' => true,
            ]);
        }

        if ($step === 'ticket' && $airline !== null) {
            $ticketTypes = $ticketTypeRepository->findBy(
                [
                    'airline' => $airline,
                    'isActive' => true,
                ],
                [
                    'priorityLevel' => 'ASC',
                ]
            );

            foreach ($ticketTypes as $ticket) {
                $ticketSummaries[$ticket->getSlug()] = $summaryBuilder->buildForTicket($airline, $ticket);
            }
        }

        if ($step === 'result') {
            $ticketSummary = $this->buildTicketSummary(
                isFlight: $isFlight,
                airline: $airline,
                query: $query,
                ticketTypeRepository: $ticketTypeRepository,
                summaryBuilder: $summaryBuilder
            );

            $input = new GuideInput(
                travelTypeSlug: $this->getStringQuery($query, 'travel_type'),
                transportSlug: $transport,
                durationBandSlug: $this->getStringQuery($query, 'duration'),
                airlineSlug: $airlineSlug,
                ticketSlug: $this->getStringQuery($query, 'ticket'),
                travelFrequency: $this->getStringQuery($query, 'frequency'),
                ownership: null,
                needsLaptop: null,
                laptopMinInch: null,
                materialPreference: $this->getStringQuery($query, 'preferences'),
                budgetMin: null,
                budgetMax: null,
            );

            $travelProfile = $profileResolver->resolve($input);

            if ($travelProfile === null) {
                throw new \RuntimeException('Geen TravelProfile gevonden.');
            }

            $volumeAdvice = $volumeAdvisor->advise(
                $input->durationBandSlug,
                $input->travelTypeSlug
            );

            $profile = $profileFactory->make(
                $travelProfile,
                [
                    'transport' => $input->transportSlug,
                    'airline' => $input->airlineSlug,
                    'ticket' => $input->ticketSlug,
                    'volume' => $volumeAdvice,
                    'airRules' => $ticketSummary,
                ],
                $query
            );

            $faqs = $faqRepository->findForContext($input->transportSlug, $airline);

            $output = $guideEngine->run($input);
            $rankedByScope = $output->rankedByScope ?? [];

            $baggageProfile = $resultBuilder->build($query, $ticketSummary);
            $recommendedScope = $volumeAdvice['recommendedScope'] ?? null;
            $scopeOrder = $this->determineScopePriority($baggageProfile, $recommendedScope);

            $bestByScope = [];
            $alternativesByScope = [];

            foreach ($scopeOrder as $scope) {
                $items = $rankedByScope[$scope] ?? [];

                if ($items === [] && $scope === 'main') {
                    $items = $rankedByScope['cabin'] ?? [];
                }

                if ($items !== []) {
                    $bestByScope[$scope] = $items[0];
                    $alternativesByScope[$scope] = array_slice($items, 1, 3);
                } else {
                    $alternativesByScope[$scope] = [];
                }
            }

            $bestProduct = null;
            $alternatives = [];
            $activeScopesForRender = $scopeOrder;

            $bestItem = null;
            $scopeForBest = null;

            if ($isFlight) {
                if (!empty($bestByScope['cabin'])) {
                    $bestItem = $bestByScope['cabin'];
                    $scopeForBest = 'cabin';
                } elseif (!empty($bestByScope['hold'])) {
                    $bestItem = $bestByScope['hold'];
                    $scopeForBest = 'hold';
                } elseif (!empty($bestByScope['personal'])) {
                    $bestItem = $bestByScope['personal'];
                    $scopeForBest = 'personal';
                }
            } else {
                if (!empty($bestByScope['main'])) {
                    $bestItem = $bestByScope['main'];
                    $scopeForBest = 'main';
                } elseif (!empty($bestByScope['personal'])) {
                    $bestItem = $bestByScope['personal'];
                    $scopeForBest = 'personal';
                }
            }

            if ($bestItem !== null && $scopeForBest !== null) {
                $bestProduct = [
                    'product' => $bestItem->product,
                    'variant' => $bestItem->variant,
                    'labels' => ['Beste keuze'],
                    'reasons' => $bestItem->reasons ?? [],
                    'score' => $bestItem->score ?? null,
                    'scope' => $scopeForBest,
                    'mediaPath' => $bestItem->mediaPath ?? null,
                ];

                $alternatives = array_map(
                    static fn($item) => [
                        'product' => $item->product,
                        'variant' => $item->variant,
                        'reasons' => $item->reasons ?? [],
                        'score' => $item->score ?? null,
                        'scope' => $scopeForBest,
                        'mediaPath' => $item->mediaPath ?? null,
                    ],
                    $alternativesByScope[$scopeForBest] ?? []
                );
            }

            $showRentalCta = $input->transportSlug === 'plane'
                && $input->travelFrequency === 'incidental';

            $rentalUrl = '/koffer-huren';

            return $this->render('guide/steps/_result_profile.html.twig', [
                'profile' => $profile,
                'bestProduct' => $bestProduct,
                'alternatives' => $alternatives,
                'bestByScope' => $bestByScope,
                'alternativesByScope' => $alternativesByScope,
                'activeScopes' => $activeScopesForRender,
                'advice' => $output->advice ?? null,
                'transport' => $input->transportSlug,
                'frequency' => $input->travelFrequency,
                'preference' => $input->materialPreference,
                'showRentalCta' => $showRentalCta,
                'rentalUrl' => $rentalUrl,
                'faqs' => $faqs,
            ]);
        }

        return $this->render('guide/index.html.twig', [
            'step' => $step,
            'query' => $query,
            'flow' => $flow,
            'isFlight' => $isFlight,
            'airlines' => $airlines,
            'airline' => $airline,
            'ticketTypes' => $ticketTypes,
            'ticketSummaries' => $ticketSummaries,
        ]);
    }

    private function determineStep(array $query, bool $isFlight): string
    {
        if (!isset($query['travel_type'])) {
            return 'travel_type';
        }

        $step = $query['step'] ?? 'travel_type';
        $flow = $this->buildFlow($isFlight);

        if (!in_array($step, $flow, true)) {
            return 'travel_type';
        }

        return $step;
    }

    private function buildFlow(bool $isFlight): array
    {
        $flow = ['travel_type', 'transport'];

        if ($isFlight) {
            $flow[] = 'airline';
            $flow[] = 'ticket';
        }

        return array_merge($flow, [
            'duration',
            'frequency',
            'preferences',
            'result',
        ]);
    }

    private function getStringQuery(array $query, string $key): ?string
    {
        $value = $query[$key] ?? null;

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }

    private function buildTicketSummary(
        bool $isFlight,
        mixed $airline,
        array $query,
        AirlineTicketTypeRepository $ticketTypeRepository,
        TicketBaggageSummaryBuilder $summaryBuilder
    ): array {
        if (!$isFlight) {
            return [
                'personal' => ['allowed' => true],
                'cabin' => ['allowed' => false],
                'hold' => ['allowed' => false],
            ];
        }

        $ticketSlug = $this->getStringQuery($query, 'ticket');

        if ($airline !== null && $ticketSlug !== null) {
            $ticket = $ticketTypeRepository->findOneBy([
                'slug' => $ticketSlug,
                'airline' => $airline,
                'isActive' => true,
            ]);

            if ($ticket !== null) {
                return $summaryBuilder->buildForTicket($airline, $ticket);
            }
        }

        return $summaryBuilder->buildFallbackForAirline($airline);
    }

    private function determineScopePriority(
        mixed $baggageProfile,
        ?string $recommendedScope
    ): array {
        $scopes = $baggageProfile->allowedScopes ?? [];

        if ($recommendedScope !== null && in_array($recommendedScope, $scopes, true)) {
            usort(
                $scopes,
                static fn(string $a, string $b): int => $a === $recommendedScope ? -1 : 1
            );
        }

        return $scopes;
    }
}