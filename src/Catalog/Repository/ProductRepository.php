<?php

namespace App\Catalog\Repository;

use App\Catalog\Entity\Category;
use App\Catalog\Entity\Color;
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
        ?array $colorSlugs = null
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
        ?array $colorSlugs = null
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
            ->orderBy('p.id', 'DESC')
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

        $rows = $qb->getQuery()->getScalarResult();

        return array_map(
            static fn(array $row): int => (int) $row['id'],
            $rows
        );
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
        $or = $qb->expr()->orX();

        foreach ($scopes as $scope) {
            match ($scope) {
                'personal' => $or->add('p.underseater = 1'),
                'cabin' => $or->add('p.cabinSize = 1'),
                'hold' => $or->add('p.underseater = 0 AND p.cabinSize = 0'),
                default => null,
            };
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
        $scope = $scopeSlugs[0] ?? null;

        if (!$scope) {
            $qb->andWhere('1 = 0');
            return;
        }

        $rulesByScope = [];
        foreach ($rules as $rule) {
            $rulesByScope[$rule->getRuleScope()][] = $rule;
        }

        if (!isset($rulesByScope[$scope])) {
            $qb->andWhere('1 = 0');
            return;
        }

        match ($scope) {
            'personal' => $qb->andWhere('p.underseater = 1'),
            'cabin' => $qb->andWhere('p.cabinSize = 1'),
            'hold' => $qb->andWhere('p.underseater = 0'),
            default => $qb->andWhere('1 = 0'),
        };

        $or = $qb->expr()->orX();
        $i = 0;

        foreach ($rulesByScope[$scope] as $rule) {
            ++$i;
            $and = $qb->expr()->andX();

            if ($rule->getDimensionType() === 'box') {
                if ($rule->getMaxHeightCm()) {
                    $and->add("p.heightCm <= :h$i");
                    $qb->setParameter("h$i", $rule->getMaxHeightCm());
                }

                if ($rule->getMaxWidthCm()) {
                    $and->add("p.widthCm <= :w$i");
                    $qb->setParameter("w$i", $rule->getMaxWidthCm());
                }

                if ($rule->getMaxDepthCm()) {
                    $and->add($this->effectiveDepthExpr() . " <= :d$i");
                    $qb->setParameter("d$i", $rule->getMaxDepthCm());
                }
            } elseif ($rule->getDimensionType() === 'linear_sum') {
                if (!$rule->getMaxLinearCm()) {
                    continue;
                }

                $and->add($this->effectiveLinearExpr() . " <= :l$i");
                $qb->setParameter("l$i", $rule->getMaxLinearCm());
            }

            $or->add($and);
        }

        $qb->andWhere($or->count() ? $or : '1 = 0');
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
            ->setParameter('context', Product::CONTEXT_SHOP)
            ->setParameter('q', '%' . $query . '%')
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

    public function countSaleProductsForContext(string $context): int
    {
        $now = new \DateTimeImmutable();

        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->innerJoin('p.variants', 'v')
            ->andWhere('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = 1')
            ->andWhere('v.salePercentage IS NOT NULL')
            ->andWhere('v.salePercentage > 0')
            ->andWhere('(v.saleStartsAt IS NULL OR v.saleStartsAt <= :now)')
            ->andWhere('(v.saleEndsAt IS NULL OR v.saleEndsAt >= :now)')
            ->setParameter('context', $context)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findSaleProductsForContext(
        string $context,
        int $limit,
        int $offset
    ): array {
        $now = new \DateTimeImmutable();

        $ids = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id AS id')
            ->innerJoin('p.variants', 'v')
            ->andWhere('p.isActive = 1')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = 1')
            ->andWhere('v.salePercentage IS NOT NULL')
            ->andWhere('v.salePercentage > 0')
            ->andWhere('(v.saleStartsAt IS NULL OR v.saleStartsAt <= :now)')
            ->andWhere('(v.saleEndsAt IS NULL OR v.saleEndsAt >= :now)')
            ->orderBy('p.id', 'DESC')
            ->setParameter('context', $context)
            ->setParameter('now', $now)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $productIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            $ids
        );

        if ($productIds === []) {
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
            ->setParameter('ids', $productIds)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($products as $product) {
            $byId[$product->getId()] = $product;
        }

        $ordered = [];
        foreach ($productIds as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    public function countForBrandGrid(array $brandSlugs = []): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->innerJoin('p.brand', 'b')
            ->innerJoin('p.variants', 'mv', 'WITH', 'mv.isMaster = true AND mv.isActive = true')
            ->andWhere('p.isActive = true');

        if ($brandSlugs !== []) {
            $qb
                ->andWhere('b.slug IN (:brandSlugs)')
                ->setParameter('brandSlugs', $brandSlugs);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findForBrandGrid(
        int $limit,
        int $offset,
        array $brandSlugs = []
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p', 'b', 'mv', 'pi')
            ->innerJoin('p.brand', 'b')
            ->innerJoin('p.variants', 'mv', 'WITH', 'mv.isMaster = true AND mv.isActive = true')
            ->leftJoin('mv.images', 'pi', 'WITH', 'pi.isPrimary = true')
            ->andWhere('p.isActive = true')
            ->orderBy('p.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($brandSlugs !== []) {
            $qb
                ->andWhere('b.slug IN (:brandSlugs)')
                ->setParameter('brandSlugs', $brandSlugs);
        }

        return $qb->getQuery()->getResult();
    }
}