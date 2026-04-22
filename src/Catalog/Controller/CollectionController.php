<?php

declare(strict_types=1);

namespace App\Catalog\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Repository\CategoryRepository;
use App\Catalog\Repository\ProductRepository;
use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use App\Guide\Repository\AirlineBaggageRuleRepository;
use App\Guide\Repository\AirlineRepository;
use App\Shared\Pagination\PaginationService;
use App\Shop\Service\CategoryFilterResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CollectionController extends AbstractController
{
    private const PER_PAGE = 16;

    private const ALL_SCOPES = ['personal', 'cabin', 'hold'];

    public function __construct(
        private readonly VariantImagePathResolver $variantImagePathResolver,
        private readonly CategoryFilterResolver $categoryFilterResolver,
    ) {
    }

    #[Route('/shop', name: 'shop_index', methods: ['GET'])]
    public function shop(
        Request $request,
        ProductRepository $productRepository,
        ProductVariantRepository $productVariantRepository,
        CategoryRepository $categoryRepository,
        AirlineRepository $airlineRepository,
        AirlineBaggageRuleRepository $airlineBaggageRuleRepository,
        AvailabilityService $availabilityService,
        PaginationService $paginationService,
    ): Response {
        return $this->renderCollection(
            context: Product::CONTEXT_SHOP,
            template: 'shop/index.html.twig',
            request: $request,
            productRepository: $productRepository,
            productVariantRepository: $productVariantRepository,
            categoryRepository: $categoryRepository,
            airlineRepository: $airlineRepository,
            airlineBaggageRuleRepository: $airlineBaggageRuleRepository,
            availabilityService: $availabilityService,
            paginationService: $paginationService,
        );
    }

    #[Route('/bags', name: 'bags_index', methods: ['GET'])]
    public function bags(
        Request $request,
        ProductRepository $productRepository,
        ProductVariantRepository $productVariantRepository,
        CategoryRepository $categoryRepository,
        AirlineRepository $airlineRepository,
        AirlineBaggageRuleRepository $airlineBaggageRuleRepository,
        AvailabilityService $availabilityService,
        PaginationService $paginationService,
    ): Response {
        return $this->renderCollection(
            context: Product::CONTEXT_BAGS,
            template: 'bags/index.html.twig',
            request: $request,
            productRepository: $productRepository,
            productVariantRepository: $productVariantRepository,
            categoryRepository: $categoryRepository,
            airlineRepository: $airlineRepository,
            airlineBaggageRuleRepository: $airlineBaggageRuleRepository,
            availabilityService: $availabilityService,
            paginationService: $paginationService,
        );
    }

    private function renderCollection(
        string $context,
        string $template,
        Request $request,
        ProductRepository $productRepository,
        ProductVariantRepository $productVariantRepository,
        CategoryRepository $categoryRepository,
        AirlineRepository $airlineRepository,
        AirlineBaggageRuleRepository $airlineBaggageRuleRepository,
        AvailabilityService $availabilityService,
        PaginationService $paginationService,
    ): Response {
        $allowedFilters = $this->categoryFilterResolver->getAllowedFilters($context);

        $brandSlugs = $this->getStringArrayQuery($request, 'brand');
        $categorySlugs = $this->getStringArrayQuery($request, 'category');
        $volumeRanges = $this->getStringArrayQuery($request, 'volume');
        $colorSlugs = $this->getStringArrayQuery($request, 'color');

        $airlineSlugs = $context === Product::CONTEXT_SHOP
            ? $this->getStringArrayQuery($request, 'airline')
            : [];

        $rawScope = trim((string) $request->query->get('scope', ''));
        $selectedScope = in_array($rawScope, self::ALL_SCOPES, true) ? $rawScope : '';

        $scopeSlugs = $this->normalizeScopeSlugs(
            context: $context,
            selectedScope: $selectedScope,
            airlineSlugs: $airlineSlugs,
        );

        $activeScopes = $selectedScope !== '' ? [$selectedScope] : [''];

        $page = max(1, $request->query->getInt('page', 1));

        $airlineRules = $this->resolveSelectedAirlineRules(
            context: $context,
            airlineSlugs: $airlineSlugs,
            airlineRepository: $airlineRepository,
            airlineBaggageRuleRepository: $airlineBaggageRuleRepository,
        );

        $totalItems = $productRepository->countForContextGridWithFilters(
            context: $context,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $categorySlugs ?: null,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
            colorSlugs: $colorSlugs ?: null,
        );

        $pagination = $paginationService->create(
            page: $page,
            limit: self::PER_PAGE,
            totalItems: $totalItems,
        );

        $products = $productRepository->findForContextGridWithFilters(
            context: $context,
            limit: $pagination->getLimit(),
            offset: $pagination->getOffset(),
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $categorySlugs ?: null,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
            colorSlugs: $colorSlugs ?: null,
        );

        $matchingVariants = $colorSlugs !== []
            ? $productRepository->findMatchingVariantsForColors($products, $colorSlugs)
            : [];

        $items = [];

        foreach ($products as $product) {
            $master = $product->getMasterVariant();

            if ($master === null || !$master->isActive()) {
                continue;
            }

            $displayVariant = $matchingVariants[$product->getId()] ?? $master;

            if (!$displayVariant->isActive()) {
                continue;
            }

            $freshDisplayVariant = $productVariantRepository->findOneForGridBySku(
                $displayVariant->getVariantSku()
            );

            if ($freshDisplayVariant === null || !$freshDisplayVariant->isActive()) {
                continue;
            }

            $freshMaster = $productVariantRepository->findOneForGridBySku(
                $master->getVariantSku()
            ) ?? $master;

            $items[] = [
                'product' => $product,
                'variant' => $freshDisplayVariant,
                'master' => $freshMaster,
                'mediaPath' => $this->variantImagePathResolver->fromVariant($freshDisplayVariant),
                'availability' => $availabilityService->get($freshDisplayVariant),
            ];
        }

        $availableAirlines = $context === Product::CONTEXT_SHOP
            ? $airlineRepository->findActiveOrdered()
            : [];

        $availableColors = $productRepository->findColorsForContextGridWithFilters(
            context: $context,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $categorySlugs ?: null,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
        );

        $availableBrands = $productRepository->findBrandsForContextGridWithFilters(
            context: $context,
            categorySlugs: $categorySlugs ?: null,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
            colorSlugs: $colorSlugs ?: null,
        );

        $totalColors = $productRepository->countColorsForContextGridWithFilters(
            context: $context,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $categorySlugs ?: null,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
        );

        $totalAvailableVariants = $productRepository->countAvailableVariantsForContextGridWithFilters(
            context: $context,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $categorySlugs ?: null,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
            colorSlugs: $colorSlugs ?: null,
        );

        return $this->render($template, [
            'activeContext' => $context,
            'context' => $context,
            'currentContext' => $context,
            'allowedFilters' => $allowedFilters,
            'items' => $items,
            'brands' => $availableBrands,
            'categories' => $categoryRepository->findForContext($context),
            'airlines' => $availableAirlines,
            'colors' => $availableColors,
            'activeBrands' => $brandSlugs,
            'activeCategories' => $categorySlugs,
            'activeAirlines' => $airlineSlugs,
            'activeScopes' => $activeScopes,
            'activeVolumes' => $volumeRanges,
            'activeColors' => $colorSlugs,
            'currentAirline' => $airlineSlugs[0] ?? null,
            'currentScope' => $selectedScope !== '' ? $selectedScope : 'all',
            'pagination' => $pagination,
            'totalColors' => $totalColors,
            'totalAvailableVariants' => $totalAvailableVariants,
        ]);
    }

    /**
     * Voor de query:
     * - geen airline => geen scope-filter
     * - airline + "alle bagagetypes" => alle drie scopes
     * - airline + 1 scope => alleen die scope
     *
     * @return string[]
     */
    private function normalizeScopeSlugs(
        string $context,
        string $selectedScope,
        array $airlineSlugs,
    ): array {
        if ($context !== Product::CONTEXT_SHOP) {
            return [];
        }

        if ($airlineSlugs === []) {
            return [];
        }

        if ($selectedScope === '') {
            return self::ALL_SCOPES;
        }

        return [$selectedScope];
    }

    private function resolveSelectedAirlineRules(
        string $context,
        array $airlineSlugs,
        AirlineRepository $airlineRepository,
        AirlineBaggageRuleRepository $airlineBaggageRuleRepository,
    ): array {
        if ($context !== Product::CONTEXT_SHOP || $airlineSlugs === []) {
            return [];
        }

        $selectedAirlines = $airlineRepository->findBy([
            'slug' => $airlineSlugs,
            'isActive' => true,
        ]);

        $airlineRules = [];

        foreach ($selectedAirlines as $airline) {
            $rules = $airlineBaggageRuleRepository->findActiveForAirline($airline);
            $airlineRules = array_merge($airlineRules, $rules);
        }

        return $airlineRules;
    }

    /**
     * @return string[]
     */
    private function getStringArrayQuery(Request $request, string $key): array
    {
        return array_values(array_filter(
            (array) $request->query->all($key),
            static fn (mixed $value): bool => is_string($value) && $value !== ''
        ));
    }
}