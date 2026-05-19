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
     * Alle actieve merken, ook als er nog geen producten online staan.
     *
     * Gebruik voor:
     * - /merken
     * - admin
     * - algemene merkoverzichten
     *
     * @return Brand[]
     */
    public function findAllActiveOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = true')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Alle actieve merken alfabetisch.
     *
     * Gebruik voor /merken.
     *
     * @return Brand[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = true')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /* ==========================================================
     * CONTEXT-FILTERS
     * ========================================================== */

    /**
     * Merken die actieve producten hebben binnen een context.
     *
     * Gebruik voor:
     * - shop filters
     * - bags filters
     * - contextmenu’s waar lege merken ongewenst zijn
     *
     * @return Brand[]
     */
    public function findForContext(string $context): array
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.products', 'p')
            ->innerJoin('p.variants', 'v')
            ->andWhere('b.isActive = true')
            ->andWhere('p.isActive = true')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = true')
            ->setParameter('context', $context)
            ->groupBy('b.id')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Dynamische merkfilters binnen een selectie.
     *
     * Hier wil je juist geen lege merken tonen,
     * omdat een filter anders naar 0 resultaten leidt.
     *
     * @param string        $context
     * @param string[]|null $categorySlugs
     * @param string[]|null $sizeSlugs
     *
     * @return Brand[]
     */
    public function findForContextFilterDynamic(
        string $context,
        ?array $categorySlugs = null,
        ?array $sizeSlugs = null
    ): array {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.products', 'p')
            ->innerJoin('p.variants', 'v')
            ->leftJoin('p.categories', 'c')
            ->andWhere('b.isActive = true')
            ->andWhere('p.isActive = true')
            ->andWhere('p.productContext = :context')
            ->andWhere('v.isActive = true')
            ->setParameter('context', $context)
            ->groupBy('b.id');

        if ($categorySlugs !== null && $categorySlugs !== []) {
            $qb
                ->andWhere('c.slug IN (:categorySlugs)')
                ->setParameter('categorySlugs', $categorySlugs);
        }

        if ($sizeSlugs !== null && $sizeSlugs !== []) {
            $qb
                ->andWhere('c.slug IN (:sizeSlugs)')
                ->setParameter('sizeSlugs', $sizeSlugs);
        }

        return $qb
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Exacte merk-match voor zoekfunctie.
     */
    public function findActiveBySearchTerm(string $query): ?Brand
    {
        $query = mb_strtolower(trim($query));

        if ($query === '') {
            return null;
        }

        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = true')
            ->andWhere('LOWER(b.name) = :query OR LOWER(b.slug) = :query')
            ->setParameter('query', $query)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Actieve merken voor sitemap.
     *
     * Ook merken zonder online producten mogen hierin,
     * omdat de merkpagina dan als winkel-/informatiepagina kan dienen.
     *
     * @return Brand[]
     */
    public function findActiveForSitemap(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = true')
            ->andWhere('b.slug IS NOT NULL')
            ->andWhere('b.slug != :empty')
            ->setParameter('empty', '')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}