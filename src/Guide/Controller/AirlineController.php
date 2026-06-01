<?php

declare(strict_types=1);

namespace App\Guide\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
use App\Catalog\Repository\ProductRepository;
use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use App\Guide\Entity\Airline;
use App\Guide\Repository\AirlineBaggageRuleRepository;
use App\Guide\Repository\AirlineRepository;
use App\Guide\Repository\AirlineTicketTypeRepository;
use App\Guide\Repository\FaqRepository;
use App\Guide\Service\TicketBaggageSummaryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AirlineController extends AbstractController
{
    private const CANONICAL_HOST = 'https://topbags.nl';
    private const PRODUCT_RAIL_LIMIT = 4;

    public function __construct(
        private readonly AirlineRepository $airlineRepository,
        private readonly FaqRepository $faqRepository,
        private readonly AirlineTicketTypeRepository $ticketTypeRepository,
        private readonly AirlineBaggageRuleRepository $airlineBaggageRuleRepository,
        private readonly TicketBaggageSummaryBuilder $summaryBuilder,
        private readonly ProductRepository $productRepository,
        private readonly ProductVariantRepository $productVariantRepository,
        private readonly AvailabilityService $availabilityService,
        private readonly VariantImagePathResolver $variantImagePathResolver,
    ) {
    }

    /**
     * =============================
     * Airline index
     * =============================
     */
    #[Route('/vliegmaatschappijen', name: 'airline_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('airline/index.html.twig', [
            'airlines' => $this->airlineRepository->findActive(),
            'activeContext' => Product::CONTEXT_SHOP,
            'context' => Product::CONTEXT_SHOP,
            'currentContext' => Product::CONTEXT_SHOP,
            'canonical_url' => self::CANONICAL_HOST . $this->generateUrl('airline_index'),
        ]);
    }

    /**
     * =============================
     * Airline landing
     * =============================
     */
    #[Route('/vliegmaatschappijen/{slug}', name: 'airline_show', methods: ['GET'])]
    public function show(string $slug): Response
    {
        /** @var Airline|null $airline */
        $airline = $this->airlineRepository->findActiveBySlug($slug);

        if (!$airline instanceof Airline) {
            throw $this->createNotFoundException('Vliegmaatschappij niet gevonden.');
        }

        // 1. Actieve tickettypes
        $ticketTypes = $this->ticketTypeRepository->findActiveForAirline($airline);

        // 2. Per ticket: baggage summary zoals in de gids
        $ticketSummaries = [];

        foreach ($ticketTypes as $ticket) {
            $ticketSummaries[$ticket->getSlug()] = $this->summaryBuilder->buildForTicket($airline, $ticket);
        }

        // 3. Airline-brede fallback
        $fallbackSummary = $this->summaryBuilder->buildFallbackForAirline($airline);

        // 4. Actieve airline regels voor productselecties
        $airlineRules = $this->airlineBaggageRuleRepository->findActiveForAirline($airline);

        // 5. Productrails per bagagetype
        $personalItems = $this->createItemsForAirlineScope(
            scope: 'personal',
            airlineRules: $airlineRules,
        );

        $cabinItems = $this->createItemsForAirlineScope(
            scope: 'cabin',
            airlineRules: $airlineRules,
        );

        $holdItems = $this->createItemsForAirlineScope(
            scope: 'hold',
            airlineRules: $airlineRules,
        );

        // 6. FAQ’s
        $faqs = $this->faqRepository->findForContext('plane', $airline);

        return $this->render('airline/show.html.twig', [
            'airline' => $airline,
            'ticketTypes' => $ticketTypes,
            'ticketSummaries' => $ticketSummaries,
            'fallbackSummary' => $fallbackSummary,
            'airlineRules' => $airlineRules,

            'personalItems' => $personalItems,
            'cabinItems' => $cabinItems,
            'holdItems' => $holdItems,

            'faqs' => $faqs,

            'activeContext' => Product::CONTEXT_SHOP,
            'context' => Product::CONTEXT_SHOP,
            'currentContext' => Product::CONTEXT_SHOP,

            'canonical_url' => self::CANONICAL_HOST . $this->generateUrl('airline_show', [
                'slug' => $airline->getSlug(),
            ]),
        ]);
    }

    /**
     * @param array<int, mixed> $airlineRules
     *
     * @return array<int, array{
     *     product: Product,
     *     variant: ProductVariant,
     *     master: ProductVariant,
     *     mediaPath: ?string,
     *     availability: mixed
     * }>
     */
    private function createItemsForAirlineScope(
        string $scope,
        array $airlineRules,
    ): array {
        if ($airlineRules === []) {
            return [];
        }

        $products = $this->productRepository->findForContextGridWithFilters(
            context: Product::CONTEXT_SHOP,
            limit: self::PRODUCT_RAIL_LIMIT,
            offset: 0,
            brandSlugs: null,
            categorySlugs: null,
            sizeSlugs: null,
            scopeSlugs: [$scope],
            airlineRules: $airlineRules,
            volumeRanges: null,
            colorSlugs: null,
            sort: 'recommended',
        );

        $items = [];

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            $master = $product->getMasterVariant();

            if (!$master instanceof ProductVariant || !$master->isActive()) {
                continue;
            }

            $freshMaster = $this->productVariantRepository->findOneForGridBySku(
                $master->getVariantSku()
            );

            if (!$freshMaster instanceof ProductVariant || !$freshMaster->isActive()) {
                continue;
            }

            $items[] = [
                'product' => $product,
                'variant' => $freshMaster,
                'master' => $freshMaster,
                'mediaPath' => $this->variantImagePathResolver->fromVariant($freshMaster),
                'availability' => $this->availabilityService->get($freshMaster),
            ];
        }

        return $items;
    }
}