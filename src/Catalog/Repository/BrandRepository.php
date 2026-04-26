<?php

namespace App\Catalog\Repository;

use App\Catalog\Entity\Brand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Brand>
 */
final class BrandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    /* ==========================================================
     * BASIS
     * ========================================================== */

    /**
     * Alle actieve merken (admin / generiek gebruik)
     */
    public function findAllActiveOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = true')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /* ==========================================================
     * CONTEXT (SHOP / BAGS / WORK / SCHOOL)
     * ========================================================== */

    /**
     * Merken die actieve producten hebben binnen een context
     *
     * Wordt gebruikt voor:
     * - shop filters
     * - menu’s
     * - context-switching
     */
    public function findForContext(string $context): array
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.products', 'p')
            ->innerJoin('p.categories', 'c')
            ->innerJoin('c.contexts', 'cc')
            ->andWhere('cc.context = :context')
            ->setParameter('context', $context)
            ->andWhere('b.isActive = true')
            ->andWhere('p.isActive = true')
            ->groupBy('b.id')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /* ==========================================================
     * DYNAMISCHE FILTERS (SHOP)
     * ========================================================== */

    /**
     * Merken die mogelijk zijn binnen de huidige selectie
     *
     * @param string      $context        shop | bags | …
     * @param string[]|null $categorySlugs
     * @param string[]|null $sizeSlugs
     */
    public function findForContextFilterDynamic(
        string $context,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null
    ): array {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.products', 'p')
            ->innerJoin('p.categories', 'c')
            ->innerJoin('c.contexts', 'cc')
            ->andWhere('cc.context = :context')
            ->setParameter('context', $context)
            ->andWhere('b.isActive = true')
            ->andWhere('p.isActive = true')
            ->groupBy('b.id');

        // Type / categorie filter
        if ($categorySlugs) {
            $qb
                ->andWhere('c.slug IN (:categories)')
                ->setParameter('categories', $categorySlugs);
        }

        // Size (cabin / underseater als semantische categorie)
        if ($sizeSlugs) {
            $qb
                ->andWhere('c.slug IN (:sizes)')
                ->setParameter('sizes', $sizeSlugs);
        }

        return $qb
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveBySearchTerm(string $query): ?Brand
    {
        $query = mb_strtolower(trim($query));

        if ($query === '') {
            return null;
        }

        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = 1')
            ->andWhere('LOWER(b.name) = :query OR LOWER(b.slug) = :query')
            ->setParameter('query', $query)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveForSitemap(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.slug IS NOT NULL')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}