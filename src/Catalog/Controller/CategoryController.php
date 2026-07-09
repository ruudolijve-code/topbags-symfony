<?php

declare(strict_types=1);

namespace App\Catalog\Controller;

use App\Catalog\Entity\Category;
use App\Catalog\Entity\Product;
use App\Catalog\Repository\BrandRepository;
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

final class CategoryController extends AbstractController
{
    private const PER_PAGE = 15;
    private const CANONICAL_HOST = 'https://topbags.nl';
    private const ALL_SCOPES = ['personal', 'cabin', 'hold'];

    public function __construct(
        private readonly VariantImagePathResolver $variantImagePathResolver,
        private readonly CategoryFilterResolver $categoryFilterResolver,
    ) {
    }

    #[Route('/categorie/{slug}', name: 'category_show', methods: ['GET'])]
    public function show(
        string $slug,
        Request $request,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        ProductVariantRepository $productVariantRepository,
        BrandRepository $brandRepository,
        AirlineRepository $airlineRepository,
        AirlineBaggageRuleRepository $airlineBaggageRuleRepository,
        AvailabilityService $availabilityService,
        PaginationService $paginationService,
    ): Response {
        $category = $categoryRepository->findOneBy([
            'slug' => $slug,
            'isActive' => true,
        ]);

        if (!$category instanceof Category) {
            throw $this->createNotFoundException('Categorie niet gevonden');
        }

       $activeContext = $this->resolveActiveContext($category, $request);
        $allowedFilters = $this->categoryFilterResolver->getAllowedFilters($activeContext);
        $fixedCategorySlugs = [$category->getSlug()];

        $brandSlugs = $this->getQueryArray($request, 'brand');
        $volumeRanges = $this->getQueryArray($request, 'volume');
        $colorSlugs = $this->getQueryArray($request, 'color');

        $sort = $this->normalizeSort(
            (string) $request->query->get('sort', 'recommended')
        );

        $airlineSlugs = $activeContext === Product::CONTEXT_SHOP
            ? $this->getQueryArray($request, 'airline')
            : [];

        $rawScope = $activeContext === Product::CONTEXT_SHOP
            ? trim((string) $request->query->get('scope', ''))
            : '';

        $selectedScope = in_array($rawScope, self::ALL_SCOPES, true) ? $rawScope : '';
        $scopeSlugs = $selectedScope !== '' ? [$selectedScope] : [];

        $page = max(1, $request->query->getInt('page', 1));

        $airlineRules = $this->resolveSelectedAirlineRules(
            context: $activeContext,
            airlineSlugs: $airlineSlugs,
            airlineRepository: $airlineRepository,
            airlineBaggageRuleRepository: $airlineBaggageRuleRepository,
        );

        $totalItems = $productRepository->countForContextGridWithFilters(
            context: $activeContext,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $fixedCategorySlugs,
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
            context: $activeContext,
            limit: $pagination->getLimit(),
            offset: $pagination->getOffset(),
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $fixedCategorySlugs,
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

        $availableAirlines = $activeContext === Product::CONTEXT_SHOP
            ? $airlineRepository->findActiveOrdered()
            : [];

        $availableColors = $productRepository->findColorsForContextGridWithFilters(
            context: $activeContext,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $fixedCategorySlugs,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
        );

        $availableBrands = $productRepository->findBrandsForContextGridWithFilters(
            context: $activeContext,
            categorySlugs: $fixedCategorySlugs,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
            colorSlugs: $colorSlugs ?: null,
        );

        $totalColors = $productRepository->countColorsForContextGridWithFilters(
            context: $activeContext,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $fixedCategorySlugs,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
        );

        $totalAvailableVariants = $productRepository->countAvailableVariantsForContextGridWithFilters(
            context: $activeContext,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $fixedCategorySlugs,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
            colorSlugs: $colorSlugs ?: null,
        );

        $totalVisibleVariants = $productRepository->countVisibleVariantsForContextGridWithFilters(
            context: $activeContext,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $fixedCategorySlugs,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
            colorSlugs: $colorSlugs ?: null,
        );

        return $this->render('category/index.html.twig', [
            'category' => $category,
            'canonical_url' => self::CANONICAL_HOST . $this->generateUrl('category_show', [
                'slug' => $category->getSlug(),
            ]),

            'activeContext' => $activeContext,
            'context' => $activeContext,
            'currentContext' => $activeContext,

            'allowedFilters' => $allowedFilters,
            'items' => $items,
            'brands' => $availableBrands,
            'categories' => $categoryRepository->findForContext($activeContext),
            'airlines' => $availableAirlines,
            'colors' => $availableColors,

            'activeBrands' => $brandSlugs,
            'activeCategories' => $fixedCategorySlugs,
            'activeAirlines' => $airlineSlugs,
            'activeScopes' => $scopeSlugs,
            'activeVolumes' => $volumeRanges,
            'activeColors' => $colorSlugs,

            'currentAirline' => $airlineSlugs[0] ?? null,
            'currentScope' => $scopeSlugs[0] ?? null,
            'currentSort' => $sort,

            'pagination' => $pagination,
            'totalColors' => $totalColors,
            'totalAvailableVariants' => $totalAvailableVariants,
        ]);
    }

    /**
     * @return string[]
     */
    private function getQueryArray(Request $request, string $key): array
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

    private function resolveActiveContext(Category $category, Request $request): string
    {
        $requestedContext = $request->query->get('context');

        if (in_array($requestedContext, [Product::CONTEXT_SHOP, Product::CONTEXT_BAGS], true)) {
            return $requestedContext;
        }

        $resolvedContext = $this->resolveContextFromCategoryTree($category);

        return $resolvedContext ?? Product::CONTEXT_SHOP;
    }

    private function resolveContextFromCategoryTree(Category $category): ?string
    {
        foreach ($category->getContexts() as $contextRelation) {
            $context = $contextRelation->getContext();

            if (in_array($context, [Product::CONTEXT_SHOP, Product::CONTEXT_BAGS], true)) {
                return $context;
            }
        }

        $parent = $category->getParent();

        if ($parent instanceof Category) {
            return $this->resolveContextFromCategoryTree($parent);
        }

        return null;
    }
}