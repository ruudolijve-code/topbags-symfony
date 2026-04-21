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

        $activeContext = $this->resolveActiveContext($category);
        $allowedFilters = $this->categoryFilterResolver->getAllowedFilters($activeContext);
        $fixedCategorySlugs = [$category->getSlug()];

        $brandSlugs = $this->getQueryArray($request, 'brand');
        $airlineSlugs = $this->getQueryArray($request, 'airline');
        $volumeRanges = $this->getQueryArray($request, 'volume');
        $colorSlugs = $this->getQueryArray($request, 'color');

        $scope = $request->query->get('scope');
        $scopeSlugs = is_string($scope) && $scope !== '' ? [$scope] : [];

        $page = max(1, $request->query->getInt('page', 1));

        $airlineRules = $this->resolveSelectedAirlineRules(
            airlineSlugs: $airlineSlugs,
            airlineRepository: $airlineRepository,
            airlineBaggageRuleRepository: $airlineBaggageRuleRepository
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
            totalItems: $totalItems
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

        $allAirlines = $airlineRepository->findActiveOrdered();

        $availableAirlines = $this->resolveAvailableAirlinesForFacet(
            context: $activeContext,
            allAirlines: $allAirlines,
            productRepository: $productRepository,
            airlineBaggageRuleRepository: $airlineBaggageRuleRepository,
            brandSlugs: $brandSlugs,
            categorySlugs: $fixedCategorySlugs,
            scopeSlugs: $scopeSlugs,
            volumeRanges: $volumeRanges,
            colorSlugs: $colorSlugs
        );

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

        return $this->render('category/index.html.twig', [
            'category' => $category,
            'activeContext' => $activeContext,
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

    private function resolveSelectedAirlineRules(
        array $airlineSlugs,
        AirlineRepository $airlineRepository,
        AirlineBaggageRuleRepository $airlineBaggageRuleRepository,
    ): array {
        if ($airlineSlugs === []) {
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

    private function resolveAvailableAirlinesForFacet(
        string $context,
        array $allAirlines,
        ProductRepository $productRepository,
        AirlineBaggageRuleRepository $airlineBaggageRuleRepository,
        array $brandSlugs,
        array $categorySlugs,
        array $scopeSlugs,
        array $volumeRanges,
        array $colorSlugs
    ): array {
        $available = [];

        foreach ($allAirlines as $airline) {
            $rules = $airlineBaggageRuleRepository->findActiveForAirline($airline);

            if ($rules === []) {
                continue;
            }

            $hasProducts = $productRepository->hasProductsForContextGridWithFilters(
                context: $context,
                brandSlugs: $brandSlugs ?: null,
                categorySlugs: $categorySlugs ?: null,
                sizeSlugs: null,
                scopeSlugs: $scopeSlugs ?: null,
                airlineRules: $rules,
                volumeRanges: $volumeRanges ?: null,
                colorSlugs: $colorSlugs ?: null,
            );

            if ($hasProducts) {
                $available[] = $airline;
            }
        }

        return $available;
    }

    private function resolveActiveContext(Category $category): string
    {
        foreach ($category->getContexts() as $contextRelation) {
            $context = $contextRelation->getContext();

            if (in_array($context, [Product::CONTEXT_SHOP, Product::CONTEXT_BAGS], true)) {
                return $context;
            }
        }

        return Product::CONTEXT_SHOP;
    }
}