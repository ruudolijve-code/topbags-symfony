<?php

declare(strict_types=1);

namespace App\Catalog\Controller;

use App\Catalog\Entity\Category;
use App\Catalog\Entity\Product;
use App\Catalog\Repository\BrandRepository;
use App\Catalog\Repository\CategoryRepository;
use App\Catalog\Repository\ColorRepository;
use App\Catalog\Repository\ProductRepository;
use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\AvailabilityService;
use App\Catalog\Service\VariantImagePathResolver;
use App\Guide\Repository\AirlineBaggageRuleRepository;
use App\Guide\Repository\AirlineRepository;
use App\Shared\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    private const PER_PAGE = 15;

    public function __construct(
        private VariantImagePathResolver $variantImagePathResolver
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
        ColorRepository $colorRepository,
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

        $context = $this->resolveContext($category);
        $fixedCategorySlugs = [$category->getSlug()];

        $brandSlugs = array_values(array_filter(
            (array) $request->query->all('brand'),
            static fn (mixed $value): bool => is_string($value) && $value !== ''
        ));

        $airlineSlugs = array_values(array_filter(
            (array) $request->query->all('airline'),
            static fn (mixed $value): bool => is_string($value) && $value !== ''
        ));

        $volumeRanges = array_values(array_filter(
            (array) $request->query->all('volume'),
            static fn (mixed $value): bool => is_string($value) && $value !== ''
        ));

        $colorSlugs = array_values(array_filter(
            (array) $request->query->all('color'),
            static fn (mixed $value): bool => is_string($value) && $value !== ''
        ));

        $scope = $request->query->get('scope');
        $scopeSlugs = is_string($scope) && $scope !== '' ? [$scope] : [];

        $page = max(1, $request->query->getInt('page', 1));

        $airlineRules = [];

        if ($airlineSlugs !== []) {
            $selectedAirlines = $airlineRepository->findBy([
                'slug' => $airlineSlugs,
                'isActive' => true,
            ]);

            foreach ($selectedAirlines as $airline) {
                $rules = $airlineBaggageRuleRepository->findActiveForAirline($airline);
                $airlineRules = array_merge($airlineRules, $rules);
            }
        }

        $totalItems = $productRepository->countForContextGridWithFilters(
            context: $context,
            brandSlugs: $brandSlugs !== [] ? $brandSlugs : null,
            categorySlugs: $fixedCategorySlugs,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs !== [] ? $scopeSlugs : null,
            airlineRules: $airlineRules !== [] ? $airlineRules : null,
            volumeRanges: $volumeRanges !== [] ? $volumeRanges : null,
            colorSlugs: $colorSlugs !== [] ? $colorSlugs : null,
        );

        $pagination = $paginationService->create(
            page: $page,
            limit: self::PER_PAGE,
            totalItems: $totalItems
        );

        $products = $productRepository->findForContextGridWithFilters(
            context: $context,
            limit: $pagination->getLimit(),
            offset: $pagination->getOffset(),
            brandSlugs: $brandSlugs !== [] ? $brandSlugs : null,
            categorySlugs: $fixedCategorySlugs,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs !== [] ? $scopeSlugs : null,
            airlineRules: $airlineRules !== [] ? $airlineRules : null,
            volumeRanges: $volumeRanges !== [] ? $volumeRanges : null,
            colorSlugs: $colorSlugs !== [] ? $colorSlugs : null,
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

            /**
             * Belangrijk:
             * haal de variant vers en volledig op, inclusief images.
             * Daarmee voorkom je dat grid-kaarten oude image-data gebruiken.
             */
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
            context: $context,
            allAirlines: $allAirlines,
            productRepository: $productRepository,
            airlineBaggageRuleRepository: $airlineBaggageRuleRepository,
            brandSlugs: $brandSlugs,
            categorySlugs: $fixedCategorySlugs,
            scopeSlugs: $scopeSlugs,
            volumeRanges: $volumeRanges,
            colorSlugs: $colorSlugs
        );

        $currentAirline = $airlineSlugs[0] ?? null;
        $currentScope = $scopeSlugs[0] ?? null;

        $availableColors = $productRepository->findColorsForContextGridWithFilters(
            context: $context,
            brandSlugs: $brandSlugs !== [] ? $brandSlugs : null,
            categorySlugs: $fixedCategorySlugs,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs !== [] ? $scopeSlugs : null,
            airlineRules: $airlineRules !== [] ? $airlineRules : null,
            volumeRanges: $volumeRanges !== [] ? $volumeRanges : null,
        );

        $availableBrands = $productRepository->findBrandsForContextGridWithFilters(
            context: $context,
            categorySlugs: $fixedCategorySlugs,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs !== [] ? $scopeSlugs : null,
            airlineRules: $airlineRules !== [] ? $airlineRules : null,
            volumeRanges: $volumeRanges !== [] ? $volumeRanges : null,
            colorSlugs: $colorSlugs !== [] ? $colorSlugs : null,
        );

        $totalColors = $productRepository->countColorsForContextGridWithFilters(
            context: $context,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $fixedCategorySlugs ?: null,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
        );

        $totalAvailableVariants = $productRepository->countAvailableVariantsForContextGridWithFilters(
            context: $context,
            brandSlugs: $brandSlugs ?: null,
            categorySlugs: $fixedCategorySlugs ?: null,
            sizeSlugs: null,
            scopeSlugs: $scopeSlugs ?: null,
            airlineRules: $airlineRules ?: null,
            volumeRanges: $volumeRanges ?: null,
            colorSlugs: $colorSlugs ?: null,
        );

        return $this->render('category/index.html.twig', [
            'category' => $category,
            'context' => $context,
            'items' => $items,
            'brands' => $availableBrands,
            'categories' => $categoryRepository->findForContext($context),
            'airlines' => $availableAirlines,
            'colors' => $availableColors,
            'activeBrands' => $brandSlugs,
            'activeCategories' => $fixedCategorySlugs,
            'activeAirlines' => $airlineSlugs,
            'activeScopes' => $scopeSlugs,
            'activeVolumes' => $volumeRanges,
            'activeColors' => $colorSlugs,
            'currentAirline' => $currentAirline,
            'currentScope' => $currentScope,
            'pagination' => $pagination,
            'totalColors' => $totalColors,
            'totalAvailableVariants' => $totalAvailableVariants,
        ]);
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
                brandSlugs: $brandSlugs !== [] ? $brandSlugs : null,
                categorySlugs: $categorySlugs !== [] ? $categorySlugs : null,
                sizeSlugs: null,
                scopeSlugs: $scopeSlugs !== [] ? $scopeSlugs : null,
                airlineRules: $rules,
                volumeRanges: $volumeRanges !== [] ? $volumeRanges : null,
                colorSlugs: $colorSlugs !== [] ? $colorSlugs : null,
            );

            if ($hasProducts) {
                $available[] = $airline;
            }
        }

        return $available;
    }

    private function resolveContext(Category $category): string
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