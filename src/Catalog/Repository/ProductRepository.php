<?php

namespace App\Catalog\Repository;

use App\Catalog\Entity\Category;
use App\Catalog\Entity\Color;
use App\Catalog\Entity\Brand;
use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findForContextGridWithFilters(
        string $context,
        int $limit = 24,
        int $offset = 0,
        ?array $brandSlugs = null,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null,
        ?array $scopeSlugs = null,
        ?array $airlineRules = null,
        ?array $volumeRanges = null,
        ?array $colorSlugs = null,
        string $sort = 'recommended',
    ): array {
        $ids = $this->findIdsForContextGridWithFilters(
            context: $context,
            limit: $limit,
            offset: $offset,
            brandSlugs: $brandSlugs,
            categorySlugs: $categorySlugs,
            sizeSlugs: $sizeSlugs,
            scopeSlugs: $scopeSlugs,
            airlineRules: $airlineRules,
            volumeRanges: $volumeRanges,
            colorSlugs: $colorSlugs,
            sort: $sort,
        );

        if ($ids === []) {
            return [];
        }

        $products = $this->createQueryBuilder('p')
            ->select('p', 'b', 'c', 'master', 'masterImage', 'variants', 'variantColor')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'c')
            ->leftJoin(
                'p.variants',
                'master',
                'WITH',
                'master.isMaster = 1 AND master.isActive = 1'
            )
            ->leftJoin('master.images', 'masterImage', 'WITH', 'masterImage.isPrimary = 1')
            ->leftJoin(
                'p.variants',
                'variants',
                'WITH',
                'variants.isActive = 1'
            )
            ->leftJoin('variants.color', 'variantColor')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];

        foreach ($products as $product) {
            $byId[$product->getId()] = $product;
        }

        $ordered = [];

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    private function findIdsForContextGridWithFilters(
        string $context,
        int $limit = 24,
        int $offset = 0,
        ?array $brandSlugs = null,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null,
        ?array $scopeSlugs = null,
        ?array $airlineRules = null,
        ?array $volumeRanges = null,
        ?array $colorSlugs = null,
        string $sort = 'recommended',
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id AS id')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'c')
            ->leftJoin(
                'p.variants',
                'variants',
                'WITH',
                'variants.isActive = 1'
            )
            ->leftJoin('variants.color', 'variantColor')
            ->where('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->setParameter('context', $context)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($brandSlugs) {
            $qb->andWhere('b.slug IN (:brands)')
                ->setParameter('brands', $brandSlugs);
        }

        if ($categorySlugs) {
            $qb->andWhere('c.slug IN (:categories)')
                ->setParameter('categories', $categorySlugs);
        }

        if ($sizeSlugs) {
            $qb->andWhere('c.slug IN (:sizes)')
                ->setParameter('sizes', $sizeSlugs);
        }

        if (!$airlineRules && $scopeSlugs) {
            $this->applyPlainScopeFilter($qb, $scopeSlugs);
        }

        if ($airlineRules) {
            $this->applyAirlineRules($qb, $airlineRules, $scopeSlugs);
        }

        if ($colorSlugs) {
            $qb->andWhere('variantColor.slug IN (:colors)')
                ->setParameter('colors', $colorSlugs);
        }

        if ($volumeRanges) {
            $this->applyVolumeRanges($qb, $volumeRanges);
        }

        $this->applyContextGridSorting($qb, $sort);

        $rows = $qb->getQuery()->getScalarResult();

        return array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        );
    }

    private function applyContextGridSorting(\Doctrine\ORM\QueryBuilder $qb, string $sort): void
    {
        match ($sort) {
            'featured' => $qb
                ->addSelect('p.featuredPosition AS HIDDEN featuredPositionSort')
                ->addSelect('p.id AS HIDDEN idSort')
                ->andWhere('p.featuredPosition > 0')
                ->orderBy('featuredPositionSort', 'ASC')
                ->addOrderBy('idSort', 'DESC'),

            'newest' => $qb
                ->addSelect('p.id AS HIDDEN idSort')
                ->orderBy('idSort', 'DESC'),

            'price_asc' => $qb
                ->addSelect('MIN(variants.price) AS HIDDEN minPriceSort')
                ->addGroupBy('p.id')
                ->orderBy('minPriceSort', 'ASC'),

            'price_desc' => $qb
                ->addSelect('MIN(variants.price) AS HIDDEN minPriceSort')
                ->addGroupBy('p.id')
                ->orderBy('minPriceSort', 'DESC'),

            'name_asc' => $qb
                ->addSelect('p.name AS HIDDEN nameSort')
                ->orderBy('nameSort', 'ASC'),

            'name_desc' => $qb
                ->addSelect('p.name AS HIDDEN nameSort')
                ->orderBy('nameSort', 'DESC'),

            default => $qb
                ->addSelect('CASE WHEN p.featuredPosition > 0 THEN 0 ELSE 1 END AS HIDDEN featuredFirstSort')
                ->addSelect('p.featuredPosition AS HIDDEN featuredPositionSort')
                ->addSelect('p.id AS HIDDEN idSort')
                ->orderBy('featuredFirstSort', 'ASC')
                ->addOrderBy('featuredPositionSort', 'ASC')
                ->addOrderBy('idSort', 'DESC'),
        };
    }

    public function countForContextGridWithFilters(
        string $context,
        ?array $brandSlugs = null,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null,
        ?array $scopeSlugs = null,
        ?array $airlineRules = null,
        ?array $volumeRanges = null,
        ?array $colorSlugs = null
    ): int {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'c')
            ->leftJoin(
                'p.variants',
                'variants',
                'WITH',
                'variants.isActive = 1'
            )
            ->leftJoin('variants.color', 'variantColor')
            ->where('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->setParameter('context', $context);

        if ($brandSlugs) {
            $qb->andWhere('b.slug IN (:brands)')
                ->setParameter('brands', $brandSlugs);
        }

        if ($categorySlugs) {
            $qb->andWhere('c.slug IN (:categories)')
                ->setParameter('categories', $categorySlugs);
        }

        if ($sizeSlugs) {
            $qb->andWhere('c.slug IN (:sizes)')
                ->setParameter('sizes', $sizeSlugs);
        }

        if (!$airlineRules && $scopeSlugs) {
            $this->applyPlainScopeFilter($qb, $scopeSlugs);
        }

        if ($airlineRules) {
            $this->applyAirlineRules($qb, $airlineRules, $scopeSlugs);
        }

        if ($colorSlugs) {
            $qb->andWhere('variantColor.slug IN (:colors)')
                ->setParameter('colors', $colorSlugs);
        }

        if ($volumeRanges) {
            $this->applyVolumeRanges($qb, $volumeRanges);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyPlainScopeFilter(QueryBuilder $qb, array $scopes): void
    {
        $selectedScopes = array_values(array_unique(array_filter($scopes)));

        if ($selectedScopes === []) {
            return;
        }

        $or = $qb->expr()->orX();

        foreach ($selectedScopes as $scope) {
            $condition = $this->buildScopeCondition($qb, $scope);

            if ($condition !== null) {
                $or->add($condition);
            }
        }

        if ($or->count() > 0) {
            $qb->andWhere($or);
        }
    }

    private function applyAirlineRules(
        QueryBuilder $qb,
        array $rules,
        ?array $scopeSlugs
    ): void {
        if ($rules === []) {
            return;
        }

        $rulesByScope = [];

        foreach ($rules as $rule) {
            $ruleScope = $rule->getRuleScope();

            if (!in_array($ruleScope, ['personal', 'cabin', 'hold'], true)) {
                continue;
            }

            $rulesByScope[$ruleScope][] = $rule;
        }

        if ($rulesByScope === []) {
            $qb->andWhere('1 = 0');
            return;
        }

        $selectedScopes = array_values(array_unique(array_filter($scopeSlugs ?? [])));

        if ($selectedScopes === []) {
            $selectedScopes = array_keys($rulesByScope);
        }

        $outerOr = $qb->expr()->orX();
        $i = 0;

        foreach ($selectedScopes as $scope) {
            if (!isset($rulesByScope[$scope])) {
                continue;
            }

            $scopeCondition = $this->buildScopeCondition($qb, $scope);

            if ($scopeCondition === null) {
                continue;
            }

            $rulesOr = $qb->expr()->orX();

            foreach ($rulesByScope[$scope] as $rule) {
                ++$i;

                $ruleAnd = $qb->expr()->andX();

                if ($rule->getDimensionType() === 'box') {
                    if ($rule->getMaxHeightCm()) {
                        $ruleAnd->add("p.heightCm <= :h$i");
                        $qb->setParameter("h$i", $rule->getMaxHeightCm());
                    }

                    if ($rule->getMaxWidthCm()) {
                        $ruleAnd->add("p.widthCm <= :w$i");
                        $qb->setParameter("w$i", $rule->getMaxWidthCm());
                    }

                    if ($rule->getMaxDepthCm()) {
                        $ruleAnd->add($this->effectiveDepthExpr() . " <= :d$i");
                        $qb->setParameter("d$i", $rule->getMaxDepthCm());
                    }
                } elseif ($rule->getDimensionType() === 'linear_sum') {
                    if (!$rule->getMaxLinearCm()) {
                        continue;
                    }

                    $ruleAnd->add($this->effectiveLinearExpr() . " <= :l$i");
                    $qb->setParameter("l$i", $rule->getMaxLinearCm());
                } else {
                    continue;
                }

                if ($ruleAnd->count() > 0) {
                    $rulesOr->add($ruleAnd);
                }
            }

            if ($rulesOr->count() === 0) {
                continue;
            }

            $outerOr->add(
                $qb->expr()->andX(
                    $scopeCondition,
                    $rulesOr
                )
            );
        }

        if ($outerOr->count() === 0) {
            $qb->andWhere('1 = 0');
            return;
        }

        $qb->andWhere($outerOr);
    }

    private function buildScopeCondition(QueryBuilder $qb, string $scope): ?string
    {
        return match ($scope) {
            'personal' => 'p.underseater = 1',
            'cabin' => 'p.cabinSize = 1',
            'hold' => '(p.underseater = 0 AND p.cabinSize = 0)',
            default => null,
        };
    }

    private function applyVolumeRanges(QueryBuilder $qb, array $ranges): void
    {
        $volumeExpr = $this->effectiveVolumeExpr();
        $or = $qb->expr()->orX();
        $i = 0;

        foreach ($ranges as $range) {
            ++$i;

            [$min, $max] = $this->resolveVolumeRange($range);

            $and = $qb->expr()->andX();

            if ($min !== null) {
                $and->add("$volumeExpr >= :vmin$i");
                $qb->setParameter("vmin$i", $min);
            }

            if ($max !== null) {
                $and->add("$volumeExpr <= :vmax$i");
                $qb->setParameter("vmax$i", $max);
            }

            $or->add($and);
        }

        if ($or->count()) {
            $qb->andWhere($or);
        }
    }

    private function resolveVolumeRange(string $range): array
    {
        return match ($range) {
            'lt35' => [null, 35],
            '35-45' => [35, 45],
            '45-65' => [45, 65],
            '65-85' => [65, 85],
            '85-110' => [85, 110],
            '110+' => [110, null],
            default => [null, null],
        };
    }

    private function effectiveDepthExpr(): string
    {
        return '
            CASE
                WHEN p.expandable = 1 AND p.expandableDepthCm IS NOT NULL
                THEN p.depthCm + p.expandableDepthCm
                ELSE p.depthCm
            END
        ';
    }

    private function effectiveLinearExpr(): string
    {
        return '
            p.heightCm +
            p.widthCm +
            CASE
                WHEN p.expandable = 1 AND p.expandableDepthCm IS NOT NULL
                THEN p.depthCm + p.expandableDepthCm
                ELSE p.depthCm
            END
        ';
    }

    private function effectiveVolumeExpr(): string
    {
        return '
            CASE
                WHEN p.expandable = 1 AND p.expandableVolumeL IS NOT NULL
                THEN p.volumeL + p.expandableVolumeL
                ELSE p.volumeL
            END
        ';
    }

    public function findMatchingVariantsForColors(array $products, array $colorSlugs): array
    {
        if ($products === [] || $colorSlugs === []) {
            return [];
        }

        $ids = array_map(
            static fn(Product $product) => $product->getId(),
            $products
        );

        $variants = $this->getEntityManager()->createQueryBuilder()
            ->select('v', 'p', 'c', 'i')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'p')
            ->innerJoin('v.color', 'c')
            ->leftJoin('v.images', 'i')
            ->andWhere('p.id IN (:ids)')
            ->andWhere('v.isActive = 1')
            ->andWhere('c.slug IN (:colors)')
            ->setParameter('ids', $ids)
            ->setParameter('colors', $colorSlugs)
            ->orderBy('v.isMaster', 'DESC')
            ->addOrderBy('v.id', 'ASC')
            ->getQuery()
            ->getResult();

        $map = [];

        foreach ($variants as $variant) {
            $productId = $variant->getProduct()->getId();

            if (!isset($map[$productId])) {
                $map[$productId] = $variant;
            }
        }

        return $map;
    }

    public function findColorsForContextGridWithFilters(
        string $context,
        ?array $brandSlugs = null,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null,
        ?array $scopeSlugs = null,
        ?array $airlineRules = null,
        ?array $volumeRanges = null
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT c')
            ->from(Color::class, 'c')
            ->innerJoin('c.variants', 'v')
            ->innerJoin('v.product', 'p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'pc')
            ->where('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = 1')
            ->setParameter('context', $context)
            ->orderBy('c.name', 'ASC');

        if ($brandSlugs) {
            $qb->andWhere('b.slug IN (:brands)')
                ->setParameter('brands', $brandSlugs);
        }

        if ($categorySlugs) {
            $qb->andWhere('pc.slug IN (:categories)')
                ->setParameter('categories', $categorySlugs);
        }

        if ($sizeSlugs) {
            $qb->andWhere('pc.slug IN (:sizes)')
                ->setParameter('sizes', $sizeSlugs);
        }

        if (!$airlineRules && $scopeSlugs) {
            $this->applyPlainScopeFilter($qb, $scopeSlugs);
        }

        if ($airlineRules) {
            $this->applyAirlineRules($qb, $airlineRules, $scopeSlugs);
        }

        if ($volumeRanges) {
            $this->applyVolumeRanges($qb, $volumeRanges);
        }

        return $qb->getQuery()->getResult();
    }

    public function findBrandsForContextGridWithFilters(
        string $context,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null,
        ?array $scopeSlugs = null,
        ?array $airlineRules = null,
        ?array $volumeRanges = null,
        ?array $colorSlugs = null
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT b')
            ->from(\App\Catalog\Entity\Brand::class, 'b')
            ->innerJoin('b.products', 'p')
            ->leftJoin('p.categories', 'pc')
            ->leftJoin(
                'p.variants',
                'variants',
                'WITH',
                'variants.isActive = 1'
            )
            ->leftJoin('variants.color', 'variantColor')
            ->where('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->andWhere('b.isActive = 1')
            ->setParameter('context', $context)
            ->orderBy('b.name', 'ASC');

        if ($categorySlugs) {
            $qb->andWhere('pc.slug IN (:categories)')
                ->setParameter('categories', $categorySlugs);
        }

        if ($sizeSlugs) {
            $qb->andWhere('pc.slug IN (:sizes)')
                ->setParameter('sizes', $sizeSlugs);
        }

        if (!$airlineRules && $scopeSlugs) {
            $this->applyPlainScopeFilter($qb, $scopeSlugs);
        }

        if ($airlineRules) {
            $this->applyAirlineRules($qb, $airlineRules, $scopeSlugs);
        }

        if ($volumeRanges) {
            $this->applyVolumeRanges($qb, $volumeRanges);
        }

        if ($colorSlugs) {
            $qb->andWhere('variantColor.slug IN (:colors)')
                ->setParameter('colors', $colorSlugs);
        }

        return $qb->getQuery()->getResult();
    }

    public function hasProductsForContextGridWithFilters(
        string $context,
        ?array $brandSlugs = null,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null,
        ?array $scopeSlugs = null,
        ?array $airlineRules = null,
        ?array $volumeRanges = null,
        ?array $colorSlugs = null
    ): bool {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'c')
            ->leftJoin(
                'p.variants',
                'variants',
                'WITH',
                'variants.isActive = 1'
            )
            ->leftJoin('variants.color', 'variantColor')
            ->where('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->setParameter('context', $context)
            ->setMaxResults(1);

        if ($brandSlugs) {
            $qb->andWhere('b.slug IN (:brands)')
                ->setParameter('brands', $brandSlugs);
        }

        if ($categorySlugs) {
            $qb->andWhere('c.slug IN (:categories)')
                ->setParameter('categories', $categorySlugs);
        }

        if ($sizeSlugs) {
            $qb->andWhere('c.slug IN (:sizes)')
                ->setParameter('sizes', $sizeSlugs);
        }

        if (!$airlineRules && $scopeSlugs) {
            $this->applyPlainScopeFilter($qb, $scopeSlugs);
        }

        if ($airlineRules) {
            $this->applyAirlineRules($qb, $airlineRules, $scopeSlugs);
        }

        if ($volumeRanges) {
            $this->applyVolumeRanges($qb, $volumeRanges);
        }

        if ($colorSlugs) {
            $qb->andWhere('variantColor.slug IN (:colors)')
                ->setParameter('colors', $colorSlugs);
        }

        return $qb->getQuery()->getOneOrNullResult() !== null;
    }

    /**
     * @param Product[] $products
     * @return Color[]
     */
    public function findColorsForProducts(array $products): array
    {
        if ($products === []) {
            return [];
        }

        $ids = array_map(
            static fn(Product $product) => $product->getId(),
            $products
        );

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT c')
            ->from(Color::class, 'c')
            ->innerJoin('c.variants', 'v')
            ->innerJoin('v.product', 'p')
            ->andWhere('p.id IN (:ids)')
            ->andWhere('v.isActive = 1')
            ->setParameter('ids', $ids)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForCategoryGrid(Category $category): array
    {
        return $this->findForContextGridWithFilters(
            context: Product::CONTEXT_SHOP,
            categorySlugs: [$category->getSlug()],
            limit: 24,
            offset: 0
        );
    }

    /**
     * @return Product[]
     */
    public function findActive(int $limit = 500): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = 1')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Product[]
     */
    public function findActiveForContext(string $context, int $limit = 500): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->setParameter('context', $context)
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function search(string $query, int $limit = 24): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->select('DISTINCT p', 'b', 'master', 'variants', 'variantColor')
            ->leftJoin('p.brand', 'b')
            ->leftJoin(
                'p.variants',
                'master',
                'WITH',
                'master.isMaster = 1 AND master.isActive = 1'
            )
            ->leftJoin(
                'p.variants',
                'variants',
                'WITH',
                'variants.isActive = 1'
            )
            ->leftJoin('variants.color', 'variantColor')
            ->andWhere('p.isActive = 1')
            ->andWhere('
                p.name LIKE :q
                OR p.series LIKE :q
                OR p.modelSku LIKE :q
                OR b.name LIKE :q
            ')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('p.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchForShop(string $query, int $limit = 24): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $search = '%' . mb_strtolower($query) . '%';

        return $this->createQueryBuilder('p')
            ->select('DISTINCT p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.variants', 'v')
            ->andWhere('p.isActive = true')
            ->andWhere('p.productContext = :context')
            ->andWhere(
                'LOWER(p.name) LIKE :search
                OR LOWER(p.slug) LIKE :search
                OR LOWER(p.series) LIKE :search
                OR LOWER(p.modelSku) LIKE :search
                OR LOWER(b.name) LIKE :search
                OR LOWER(v.variantSku) LIKE :search
                OR LOWER(v.ean) LIKE :search
                OR LOWER(v.supplierColorName) LIKE :search
                OR LOWER(v.supplierColorCode) LIKE :search
                OR LOWER(v.supplierColorSlug) LIKE :search'
            )
            ->setParameter('context', Product::CONTEXT_SHOP)
            ->setParameter('search', $search)
            ->orderBy('p.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchAllActive(string $query, int $limit = 24): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $search = '%' . mb_strtolower($query) . '%';

        return $this->createQueryBuilder('p')
            ->select('DISTINCT p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'categories')
            ->leftJoin('p.variants', 'v')
            ->leftJoin('v.color', 'variantColor')
            ->andWhere('p.isActive = true')
            ->andWhere(
                'LOWER(p.name) LIKE :search
                OR LOWER(p.slug) LIKE :search
                OR LOWER(p.series) LIKE :search
                OR LOWER(p.modelSku) LIKE :search
                OR LOWER(b.name) LIKE :search
                OR LOWER(categories.name) LIKE :search
                OR LOWER(categories.slug) LIKE :search
                OR LOWER(v.variantSku) LIKE :search
                OR LOWER(v.ean) LIKE :search
                OR LOWER(v.supplierColorName) LIKE :search
                OR LOWER(v.supplierColorCode) LIKE :search
                OR LOWER(v.supplierColorSlug) LIKE :search
                OR LOWER(variantColor.name) LIKE :search
                OR LOWER(variantColor.slug) LIKE :search'
            )
            ->setParameter('search', $search)
            ->orderBy('p.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchForBags(string $query, int $limit = 24): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->select('DISTINCT p', 'b', 'master', 'variants', 'variantColor', 'categories')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'categories')
            ->leftJoin(
                'p.variants',
                'master',
                'WITH',
                'master.isMaster = 1 AND master.isActive = 1'
            )
            ->leftJoin(
                'p.variants',
                'variants',
                'WITH',
                'variants.isActive = 1'
            )
            ->leftJoin('variants.color', 'variantColor')
            ->andWhere('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->andWhere('
                p.name LIKE :q
                OR p.series LIKE :q
                OR p.modelSku LIKE :q
                OR b.name LIKE :q
                OR categories.name LIKE :q
                OR variantColor.name LIKE :q
            ')
            ->setParameter('context', Product::CONTEXT_BAGS)
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('p.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countColorsForContextGridWithFilters(
        string $context,
        ?array $brandSlugs = null,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null,
        ?array $scopeSlugs = null,
        ?array $airlineRules = null,
        ?array $volumeRanges = null
    ): int {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(DISTINCT c.id)')
            ->from(Color::class, 'c')
            ->innerJoin('c.variants', 'v')
            ->innerJoin('v.product', 'p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'pc')
            ->where('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = 1')
            ->setParameter('context', $context);

        if ($brandSlugs) {
            $qb->andWhere('b.slug IN (:brands)')
                ->setParameter('brands', $brandSlugs);
        }

        if ($categorySlugs) {
            $qb->andWhere('pc.slug IN (:categories)')
                ->setParameter('categories', $categorySlugs);
        }

        if ($sizeSlugs) {
            $qb->andWhere('pc.slug IN (:sizes)')
                ->setParameter('sizes', $sizeSlugs);
        }

        if (!$airlineRules && $scopeSlugs) {
            $this->applyPlainScopeFilter($qb, $scopeSlugs);
        }

        if ($airlineRules) {
            $this->applyAirlineRules($qb, $airlineRules, $scopeSlugs);
        }

        if ($volumeRanges) {
            $this->applyVolumeRanges($qb, $volumeRanges);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countAvailableVariantsForContextGridWithFilters(
        string $context,
        ?array $brandSlugs = null,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null,
        ?array $scopeSlugs = null,
        ?array $airlineRules = null,
        ?array $volumeRanges = null,
        ?array $colorSlugs = null
    ): int {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(DISTINCT v.id)')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'pc')
            ->leftJoin('v.color', 'c')
            ->leftJoin('v.stock', 's')
            ->where('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = 1')
            ->andWhere('(
                (s.onHand - s.reserved) > 0
                OR v.allowBackorder = 1
            )')
            ->setParameter('context', $context);

        if ($brandSlugs) {
            $qb->andWhere('b.slug IN (:brands)')
                ->setParameter('brands', $brandSlugs);
        }

        if ($categorySlugs) {
            $qb->andWhere('pc.slug IN (:categories)')
                ->setParameter('categories', $categorySlugs);
        }

        if ($sizeSlugs) {
            $qb->andWhere('pc.slug IN (:sizes)')
                ->setParameter('sizes', $sizeSlugs);
        }

        if (!$airlineRules && $scopeSlugs) {
            $this->applyPlainScopeFilter($qb, $scopeSlugs);
        }

        if ($airlineRules) {
            $this->applyAirlineRules($qb, $airlineRules, $scopeSlugs);
        }

        if ($volumeRanges) {
            $this->applyVolumeRanges($qb, $volumeRanges);
        }

        if ($colorSlugs) {
            $qb->andWhere('c.slug IN (:colors)')
                ->setParameter('colors', $colorSlugs);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countForBrandGrid(array $brandSlugs = []): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->innerJoin('p.brand', 'b')
            ->innerJoin('p.variants', 'v')
            ->andWhere('p.isActive = true')
            ->andWhere('b.isActive = true')
            ->andWhere('v.isActive = true');

        if ($brandSlugs !== []) {
            $qb
                ->andWhere('b.slug IN (:brandSlugs)')
                ->setParameter('brandSlugs', $brandSlugs);
        }

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countColorsForBrandGrid(array $brandSlugs = []): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT color.id)')
            ->innerJoin('p.brand', 'b')
            ->innerJoin('p.variants', 'v')
            ->leftJoin('v.color', 'color')
            ->andWhere('p.isActive = true')
            ->andWhere('b.isActive = true')
            ->andWhere('v.isActive = true')
            ->andWhere('color.id IS NOT NULL');

        if ($brandSlugs !== []) {
            $qb
                ->andWhere('b.slug IN (:brandSlugs)')
                ->setParameter('brandSlugs', $brandSlugs);
        }

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAvailableVariantsForBrandGrid(array $brandSlugs = []): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT v.id)')
            ->innerJoin('p.brand', 'b')
            ->innerJoin('p.variants', 'v')
            ->leftJoin('v.stock', 's')
            ->andWhere('p.isActive = true')
            ->andWhere('b.isActive = true')
            ->andWhere('v.isActive = true')
            ->andWhere('((s.onHand - s.reserved) > 0 OR v.allowBackorder = true)');

        if ($brandSlugs !== []) {
            $qb
                ->andWhere('b.slug IN (:brandSlugs)')
                ->setParameter('brandSlugs', $brandSlugs);
        }

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Producten voor merkpagina's, context-overstijgend.
     *
     * Eerst halen we unieke product-ID's op met pagination.
     * Daarna laden we de volledige producten met varianten en afbeeldingen.
     *
     * @return Product[]
     */
    public function findForBrandGrid(
        int $limit,
        int $offset,
        array $brandSlugs = []
    ): array {
       $idQb = $this->createQueryBuilder('p')
            ->select('p.id AS id')
            ->addSelect('MIN(p.name) AS sortName')
            ->innerJoin('p.brand', 'b')
            ->innerJoin('p.variants', 'v')
            ->andWhere('p.isActive = true')
            ->andWhere('b.isActive = true')
            ->andWhere('v.isActive = true')
            ->groupBy('p.id');

        if ($brandSlugs !== []) {
            $idQb
                ->andWhere('b.slug IN (:brandSlugs)')
                ->setParameter('brandSlugs', $brandSlugs);
        }

       $rows = $idQb
            ->orderBy('sortName', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $ids = array_map('intval', array_column($rows, 'id'));

        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->select('DISTINCT p, b, v, color, i, c')
            ->innerJoin('p.brand', 'b')
            ->leftJoin('p.variants', 'v', 'WITH', 'v.isActive = true')
            ->leftJoin('v.color', 'color')
            ->leftJoin('v.images', 'i')
            ->leftJoin('p.categories', 'c')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('v.isMaster', 'DESC')
            ->addOrderBy('v.id', 'ASC')
            ->addOrderBy('i.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByBrandForContext(Brand $brand, string $context, int $limit = 12): array
    {
        $ids = $this->createQueryBuilder('p')
            ->select('p.id')
            ->andWhere('p.brand = :brand')
            ->andWhere('p.productContext = :context')
            ->andWhere('p.isActive = true')
            ->setParameter('brand', $brand)
            ->setParameter('context', $context)
            ->orderBy('p.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->leftJoin('p.brand', 'b')
            ->addSelect('b')
            ->leftJoin('p.variants', 'v', 'WITH', 'v.isActive = true')
            ->addSelect('v')
            ->leftJoin('v.color', 'color')
            ->addSelect('color')
            ->leftJoin('v.images', 'i')
            ->addSelect('i')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('v.isMaster', 'DESC')
            ->addOrderBy('v.id', 'ASC')
            ->addOrderBy('i.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countColorsForSearch(string $query): int
    {
        $query = trim($query);

        if ($query === '') {
            return 0;
        }

        $search = '%' . mb_strtolower($query) . '%';

        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT c.id)')
            ->leftJoin('p.brand', 'b')
            ->innerJoin('p.variants', 'v')
            ->innerJoin('v.color', 'c')
            ->where('p.isActive = true')
            ->andWhere('v.isActive = true')
            ->andWhere(
                'LOWER(p.name) LIKE :search
                OR LOWER(p.modelSku) LIKE :search
                OR LOWER(p.series) LIKE :search
                OR LOWER(b.name) LIKE :search
                OR LOWER(v.variantSku) LIKE :search'
            )
            ->setParameter('search', $search)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAvailableVariantsForSearch(string $query): int
    {
        $query = trim($query);

        if ($query === '') {
            return 0;
        }

        $search = '%' . mb_strtolower($query) . '%';

        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT v.id)')
            ->leftJoin('p.brand', 'b')
            ->innerJoin('p.variants', 'v')
            ->leftJoin('v.stock', 's')
            ->where('p.isActive = true')
            ->andWhere('v.isActive = true')
            ->andWhere(
                'LOWER(p.name) LIKE :search
                OR LOWER(p.modelSku) LIKE :search
                OR LOWER(p.series) LIKE :search
                OR LOWER(b.name) LIKE :search
                OR LOWER(v.variantSku) LIKE :search'
            )
            ->andWhere(
                '(s.onHand - s.reserved) > 0 OR v.allowBackorder = true'
            )
            ->setParameter('search', $search)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Product[]
     */
    public function findFeaturedForContext(string $context, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('b')
            ->addSelect('v')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.variants', 'v')
            ->andWhere('p.productContext = :context')
            ->andWhere('p.isActive = true')
            ->andWhere('p.isFeatured = true')
            ->andWhere('v.isMaster = true')
            ->andWhere('v.isActive = true')
            ->setParameter('context', $context)
            ->orderBy('p.featuredPosition', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Laatste actieve varianten binnen een context en categorie.
     *
     * Eerst halen we alleen variant-ID's op.
     * Daarna laden we de volledige varianten met product, merk, kleur, afbeeldingen en voorraad.
     *
     * @return ProductVariant[]
     */
    public function findLatestVariantsForContextAndCategory(
        string $context,
        string $categorySlug,
        int $limit = 4
    ): array {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT v.id AS id')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'p')
            ->innerJoin('p.categories', 'c')
            ->andWhere('p.isActive = true')
            ->andWhere('p.productContext = :context')
            ->andWhere('c.slug = :categorySlug')
            ->andWhere('v.isActive = true')
            ->setParameter('context', $context)
            ->setParameter('categorySlug', $categorySlug)
            ->orderBy('v.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $ids = array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        );

        if ($ids === []) {
            return [];
        }

        $variants = $this->getEntityManager()->createQueryBuilder()
            ->select('v', 'p', 'b', 'categories', 'color', 'images', 'stock', 'master')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'categories')
            ->leftJoin('v.color', 'color')
            ->leftJoin('v.images', 'images')
            ->leftJoin('v.stock', 'stock')
            ->leftJoin(
                'p.variants',
                'master',
                'WITH',
                'master.isMaster = true AND master.isActive = true'
            )
            ->andWhere('v.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('v.id', 'DESC')
            ->addOrderBy('images.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->orderVariantsByIds($variants, $ids);
    }

    /**
     * Laatste actieve varianten binnen een context.
     *
     * @return ProductVariant[]
     */
    public function findLatestVariantsForContext(
        string $context,
        int $limit = 4
    ): array {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT v.id AS id')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'p')
            ->andWhere('p.isActive = true')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = true')
            ->setParameter('context', $context)
            ->orderBy('v.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $ids = array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        );

        if ($ids === []) {
            return [];
        }

        $variants = $this->getEntityManager()->createQueryBuilder()
            ->select('v', 'p', 'b', 'categories', 'color', 'images', 'stock', 'master')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'categories')
            ->leftJoin('v.color', 'color')
            ->leftJoin('v.images', 'images')
            ->leftJoin('v.stock', 'stock')
            ->leftJoin(
                'p.variants',
                'master',
                'WITH',
                'master.isMaster = true AND master.isActive = true'
            )
            ->andWhere('v.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('v.id', 'DESC')
            ->addOrderBy('images.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->orderVariantsByIds($variants, $ids);
    }

    /**
     * @param ProductVariant[] $variants
     * @param int[] $ids
     *
     * @return ProductVariant[]
     */
    private function orderVariantsByIds(array $variants, array $ids): array
    {
        $byId = [];

        foreach ($variants as $variant) {
            $byId[$variant->getId()] = $variant;
        }

        $ordered = [];

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    public function findFeaturedForCategorySlug(
    string $context,
    string $categorySlug,
    int $limit = 4,
): array {
    return $this->createQueryBuilder('p')
        ->select('DISTINCT p, b, masterVariant')
        ->innerJoin('p.brand', 'b')
        ->innerJoin('p.categories', 'c')
        ->innerJoin('p.variants', 'masterVariant', 'WITH', 'masterVariant.isMaster = true AND masterVariant.isActive = true')
        ->andWhere('p.productContext = :context')
        ->andWhere('p.isActive = true')
        ->andWhere('p.isFeatured = true')
        ->andWhere('c.slug = :categorySlug')
        ->setParameter('context', $context)
        ->setParameter('categorySlug', $categorySlug)
        ->orderBy('p.featuredPosition', 'ASC')
        ->addOrderBy('p.id', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

    public function findLatestForCategorySlug(
        string $context,
        string $categorySlug,
        int $limit = 4,
    ): array {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p, b, masterVariant')
            ->innerJoin('p.brand', 'b')
            ->innerJoin('p.categories', 'category')
            ->innerJoin('p.variants', 'masterVariant', 'WITH', 'masterVariant.isMaster = true')
            ->andWhere('p.productContext = :context')
            ->andWhere('p.isActive = true')
            ->andWhere('masterVariant.isActive = true')
            ->andWhere('category.slug = :categorySlug')
            ->setParameter('context', $context)
            ->setParameter('categorySlug', $categorySlug)
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    public function countSaleVariantsForContext(string $context): int
    {
        $now = new \DateTimeImmutable();

        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(DISTINCT v.id)')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'p')
            ->andWhere('p.isActive = true')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = true')
            ->andWhere('v.salePercentage IS NOT NULL')
            ->andWhere('v.salePercentage > 0')
            ->andWhere('(v.saleStartsAt IS NULL OR v.saleStartsAt <= :now)')
            ->andWhere('(v.saleEndsAt IS NULL OR v.saleEndsAt >= :now)')
            ->setParameter('context', $context)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return ProductVariant[]
     */
    public function findSaleVariantsForContext(
        string $context,
        int $limit,
        int $offset
    ): array {
        $now = new \DateTimeImmutable();

        /**
         * Eerst alleen variant-ID's ophalen.
         * We selecteren salePercentage mee, omdat MySQL 8 dit vereist
         * wanneer DISTINCT gecombineerd wordt met ORDER BY op salePercentage.
         */
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT v.id AS id, v.salePercentage AS salePercentage')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'p')
            ->andWhere('p.isActive = true')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = true')
            ->andWhere('v.salePercentage IS NOT NULL')
            ->andWhere('v.salePercentage > 0')
            ->andWhere('(v.saleStartsAt IS NULL OR v.saleStartsAt <= :now)')
            ->andWhere('(v.saleEndsAt IS NULL OR v.saleEndsAt >= :now)')
            ->setParameter('context', $context)
            ->setParameter('now', $now)
            ->orderBy('v.salePercentage', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $ids = array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        );

        if ($ids === []) {
            return [];
        }

        $variants = $this->getEntityManager()->createQueryBuilder()
            ->select('v', 'p', 'b', 'categories', 'color', 'images', 'stock', 'master')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'categories')
            ->leftJoin('v.color', 'color')
            ->leftJoin('v.images', 'images')
            ->leftJoin('v.stock', 'stock')
            ->leftJoin(
                'p.variants',
                'master',
                'WITH',
                'master.isMaster = true AND master.isActive = true'
            )
            ->andWhere('v.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('v.id', 'DESC')
            ->addOrderBy('images.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->orderVariantsByIds($variants, $ids);
    }

        /**
     * @return Product[]
     */
    public function findActiveForSitemap(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.brand', 'b')
            ->addSelect('b')
            ->leftJoin('p.variants', 'v')
            ->addSelect('v')
            ->andWhere('p.isActive = true')
            ->andWhere('p.slug IS NOT NULL')
            ->andWhere('p.slug != :empty')
            ->setParameter('empty', '')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function applySorting(QueryBuilder $qb, string $sort): void
    {
        match ($sort) {
            'newest' => $qb
                ->addOrderBy('p.createdAt', 'DESC')
                ->addOrderBy('p.id', 'DESC'),

            'price_asc' => $qb
                ->addOrderBy('MIN(v.price)', 'ASC')
                ->addOrderBy('p.name', 'ASC'),

            'price_desc' => $qb
                ->addOrderBy('MIN(v.price)', 'DESC')
                ->addOrderBy('p.name', 'ASC'),

            'name_asc' => $qb
                ->addOrderBy('p.name', 'ASC'),

            'name_desc' => $qb
                ->addOrderBy('p.name', 'DESC'),

            'bestseller' => $qb
                ->addOrderBy('p.position', 'ASC')
                ->addOrderBy('p.name', 'ASC'),

            default => $qb
                ->addOrderBy('p.position', 'ASC')
                ->addOrderBy('p.name', 'ASC'),
        };
    }

    public function findLightestSuitcasesByGramPerLiter(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.categories', 'c')
            ->andWhere('p.isActive = true')
            ->andWhere('p.weightKg IS NOT NULL')
            ->andWhere('p.volumeL IS NOT NULL')
            ->andWhere('p.weightKg > 0')
            ->andWhere('p.volumeL > 0')
            ->andWhere('c.slug IN (:categorySlugs)')
            ->setParameter('categorySlugs', [
                'koffers',
                'harde-koffers',
                'zachte-koffers',
                'handbagage-koffers',
                'middelgrote-koffers',
                'grote-koffers',
            ])
            ->addSelect('((p.weightKg * 1000) / p.volumeL) AS HIDDEN gramPerLiter')
            ->orderBy('gramPerLiter', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}