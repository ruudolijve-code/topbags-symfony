<?php

declare(strict_types=1);

namespace App\Catalog\Controller;

use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
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
    private const PER_PAGE = 15;
    private const ALL_SCOPES = ['personal', 'cabin', 'hold'];
    private const CANONICAL_HOST = 'https://topbags.nl';

    public function __construct(
        private readonly VariantImagePathResolver $variantImagePathResolver,
        private readonly CategoryFilterResolver $categoryFilterResolver,
    ) {
    }

    #[Route('/shop', name: 'shop_index', methods: ['GET'])]
    public function shop(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        ProductVariantRepository $productVariantRepository,
        AvailabilityService $availabilityService,
    ): Response {
        $landingCategory = $categoryRepository->findOneBy(['slug' => 'shop']);

        // Populaire / uitgelichte koffers.
        $featuredProducts = $productRepository->findFeaturedForCategorySlug(
            context: Product::CONTEXT_SHOP,
            categorySlug: 'koffers',
            limit: 4,
        );

        // Nieuwste koffer-varianten.
        // Hierdoor worden ook nieuwe seizoenskleuren van bestaande modellen getoond.
        $latestSuitcaseVariants = $productRepository->findLatestVariantsForContextAndCategory(
            context: Product::CONTEXT_SHOP,
            categorySlug: 'koffers',
            limit: 4,
        );

        // Populaire rugzakken.
        $popularBackpackProducts = $productRepository->findFeaturedForCategorySlug(
            context: Product::CONTEXT_SHOP,
            categorySlug: 'rugzakken',
            limit: 4,
        );

        // Nieuwste reistas-varianten.
        $latestTravelBagVariants = $productRepository->findLatestVariantsForContextAndCategory(
            context: Product::CONTEXT_SHOP,
            categorySlug: 'reistassen',
            limit: 4,
        );

        return $this->render('shop/landing.html.twig', [
            'activeContext' => Product::CONTEXT_SHOP,
            'context' => Product::CONTEXT_SHOP,
            'currentContext' => Product::CONTEXT_SHOP,
            'canonical_url' => self::CANONICAL_HOST . $this->generateUrl('shop_index'),

            'category' => $landingCategory,
            'landingCategory' => $landingCategory,

            'featuredItems' => $this->mapProductsToLandingItems(
                $featuredProducts,
                $productVariantRepository,
                $availabilityService,
            ),

            'latestSuitcaseItems' => $this->mapVariantsToLandingItems(
                $latestSuitcaseVariants,
                $availabilityService,
            ),

            'popularBackpackItems' => $this->mapProductsToLandingItems(
                $popularBackpackProducts,
                $productVariantRepository,
                $availabilityService,
            ),

            'latestTravelBagItems' => $this->mapVariantsToLandingItems(
                $latestTravelBagVariants,
                $availabilityService,
            ),

            'categories' => $categoryRepository->findForContext(
                Product::CONTEXT_SHOP
            ),
        ]);
    }

    #[Route('/shop/alles', name: 'shop_all', methods: ['GET'])]
    public function shopAll(
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
            landingCategorySlug: 'shop',
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
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        ProductVariantRepository $productVariantRepository,
        AvailabilityService $availabilityService,
    ): Response {
        $landingCategory = $categoryRepository->findOneBy(['slug' => 'bags']);

        // Uitgelichte tassen.
        $featuredBagProducts = $productRepository->findFeaturedForCategorySlug(
            context: Product::CONTEXT_BAGS,
            categorySlug: 'damestassen',
            limit: 4,
        );

        // Nieuwste tas-varianten.
        // Hiermee worden ook nieuwe kleuren van bestaande modellen meegenomen.
        $latestBagVariants = $productRepository->findLatestVariantsForContextAndCategory(
            context: Product::CONTEXT_BAGS,
            categorySlug: 'damestassen',
            limit: 4,
        );

        // Nieuwste portemonnee-varianten.
        $latestWalletVariants = $productRepository->findLatestVariantsForContextAndCategory(
            context: Product::CONTEXT_BAGS,
            categorySlug: 'portemonnees',
            limit: 4,
        );

        // Nieuwste accessoire-varianten.
        $latestAccessoryVariants = $productRepository->findLatestVariantsForContextAndCategory(
            context: Product::CONTEXT_BAGS,
            categorySlug: 'accessoires',
            limit: 4,
        );

        // Nieuwste laptoptas-varianten.
        $latestLaptopBagVariants = $productRepository->findLatestVariantsForContextAndCategory(
            context: Product::CONTEXT_BAGS,
            categorySlug: 'laptoptassen',
            limit: 4,
        );

        // Nieuwste rugtas-varianten.
        $latestBackpackVariants = $productRepository->findLatestVariantsForContextAndCategory(
            context: Product::CONTEXT_BAGS,
            categorySlug: 'rugtassen',
            limit: 4,
        );

        return $this->render('bags/landing.html.twig', [
            'activeContext' => Product::CONTEXT_BAGS,
            'context' => Product::CONTEXT_BAGS,
            'currentContext' => Product::CONTEXT_BAGS,
            'canonical_url' => self::CANONICAL_HOST . $this->generateUrl('bags_index'),

            'category' => $landingCategory,
            'landingCategory' => $landingCategory,

            'featuredBagItems' => $this->mapProductsToLandingItems(
                $featuredBagProducts,
                $productVariantRepository,
                $availabilityService,
            ),

            'latestBagItems' => $this->mapVariantsToLandingItems(
                $latestBagVariants,
                $availabilityService,
            ),

            'latestWalletItems' => $this->mapVariantsToLandingItems(
                $latestWalletVariants,
                $availabilityService,
            ),

            'latestAccessoryItems' => $this->mapVariantsToLandingItems(
                $latestAccessoryVariants,
                $availabilityService,
            ),

            'latestLaptopBagItems' => $this->mapVariantsToLandingItems(
                $latestLaptopBagVariants,
                $availabilityService,
            ),

            'latestBackpackItems' => $this->mapVariantsToLandingItems(
                $latestBackpackVariants,
                $availabilityService,
            ),

            'categories' => $categoryRepository->findForContext(
                Product::CONTEXT_BAGS
            ),
        ]);
    }

    #[Route('/bags/alles', name: 'bags_all', methods: ['GET'])]
    public function bagsAll(
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
            landingCategorySlug: 'bags',
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
        string $landingCategorySlug,
        Request $request,
        ProductRepository $productRepository,
        ProductVariantRepository $productVariantRepository,
        CategoryRepository $categoryRepository,
        AirlineRepository $airlineRepository,
        AirlineBaggageRuleRepository $airlineBaggageRuleRepository,
        AvailabilityService $availabilityService,
        PaginationService $paginationService,
    ): Response {
        $landingCategory = $categoryRepository->findOneBy(['slug' => $landingCategorySlug]);

        $allowedFilters = $this->categoryFilterResolver->getAllowedFilters($context);

        $brandSlugs = $this->getStringArrayQuery($request, 'brand');
        $categorySlugs = $this->getStringArrayQuery($request, 'category');
        $volumeRanges = $this->getStringArrayQuery($request, 'volume');
        $colorSlugs = $this->getStringArrayQuery($request, 'color');

        $sort = $this->normalizeSort(
            (string) $request->query->get('sort', 'recommended')
        );

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
            sort: $sort,
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

         $totalVisibleVariants = $productRepository->countVisibleVariantsForContextGridWithFilters(
            context: $context,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $categorySlugs ?: null,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
            colorSlugs: $colorSlugs ?: null,
        );

        $canonicalRoute = $context === Product::CONTEXT_BAGS ? 'bags_index' : 'shop_all';
        

        return $this->render($template, [
            'activeContext' => $context,
            'context' => $context,
            'currentContext' => $context,
            'canonical_url' => self::CANONICAL_HOST . $this->generateUrl($canonicalRoute),

            'category' => $landingCategory,
            'landingCategory' => $landingCategory,

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
            'currentSort' => $sort,
            'pagination' => $pagination,
            'totalColors' => $totalColors,
            'totalAvailableVariants' => $totalAvailableVariants,
            'totalVisibleVariants' => $totalVisibleVariants,
        ]);
    }

    private function normalizeScopeSlugs(
        string $context,
        string $selectedScope,
        array $airlineSlugs,
    ): array {
        if ($context !== Product::CONTEXT_SHOP || $airlineSlugs === []) {
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

    private function normalizeSort(string $sort): string
    {
        $allowedSorts = [
            'recommended',
            'newest',
            'bestseller',
            'price_desc',
            'price_asc',
            'name_asc',
            'name_desc',
        ];

        return in_array($sort, $allowedSorts, true) ? $sort : 'recommended';
    }

    /**
     * @param Product[] $products
     */
    private function mapProductsToLandingItems(
        array $products,
        ProductVariantRepository $productVariantRepository,
        AvailabilityService $availabilityService,
    ): array {
        $items = [];

        foreach ($products as $product) {
            $master = $product->getMasterVariant();

            if ($master === null || !$master->isActive()) {
                continue;
            }

            $freshMaster = $productVariantRepository->findOneForGridBySku(
                $master->getVariantSku()
            );

            if ($freshMaster === null || !$freshMaster->isActive()) {
                continue;
            }

            $items[] = [
                'product' => $product,
                'variant' => $freshMaster,
                'master' => $freshMaster,
                'mediaPath' => $this->variantImagePathResolver->fromVariant($freshMaster),
                'availability' => $availabilityService->get($freshMaster),
            ];
        }

        return $items;
    }

    /**
     * @param ProductVariant[] $variants
     */
    private function mapVariantsToLandingItems(
        array $variants,
        AvailabilityService $availabilityService,
    ): array {
        $items = [];

        foreach ($variants as $variant) {
            $card = $this->createCardFromVariant($variant, $availabilityService);

            if ($card !== null) {
                $items[] = $card;
            }
        }

        return $items;
    }

    private function createCardFromVariant(
        ProductVariant $variant,
        AvailabilityService $availabilityService,
    ): ?array {
        $product = $variant->getProduct();

        if ($product === null || !$product->isActive() || !$variant->isActive()) {
            return null;
        }

        $master = $product->getMasterVariant();

        if ($master === null || !$master->isActive()) {
            $master = $variant;
        }

        return [
            'product' => $product,
            'variant' => $variant,
            'master' => $master,
            'mediaPath' => $this->variantImagePathResolver->fromVariant($variant),
            'availability' => $availabilityService->get($variant),
        ];
    }
}