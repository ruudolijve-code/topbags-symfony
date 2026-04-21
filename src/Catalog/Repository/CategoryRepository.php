<?php

declare(strict_types=1);

namespace App\Catalog\Repository;

use App\Catalog\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findForContext(string $context): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.contexts', 'cc')
            ->andWhere('cc.context = :context')
            ->andWhere('c.isActive = true')
            ->andWhere('c.showInMenu = true')
            ->setParameter('context', $context)
            ->orderBy('cc.position', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMenuRoots(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isActive = true')
            ->andWhere('c.showInMenu = true')
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMenuTree(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.children', 'children')
            ->addSelect('children')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isActive = true')
            ->andWhere('c.showInMenu = true')
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('children.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findIdsBySlugs(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $result = $this->createQueryBuilder('c')
            ->select('c.id')
            ->andWhere('c.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->getQuery()
            ->getScalarResult();

        return array_map('intval', array_column($result, 'id'));
    }

    /**
     * Alleen categorieën met actieve producten.
     */
    public function findForShopFilter(): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT c')
            ->innerJoin('c.products', 'p')
            ->andWhere('c.isActive = true')
            ->andWhere('p.isActive = true')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findSizeCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.slug IN (:slugs)')
            ->andWhere('c.isActive = true')
            ->setParameter('slugs', [
                'cabin-koffers',
                'handbagage',
                'underseaters',
                'beautycases',
                'ruimbagage',
            ])
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTypeCategoriesDynamic(
        ?array $brandSlugs = null,
        ?array $sizeSlugs = null,
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->select('DISTINCT c')
            ->innerJoin('c.products', 'p')
            ->leftJoin('p.brand', 'b')
            ->andWhere('c.isActive = true')
            ->andWhere('p.isActive = true');

        if ($brandSlugs !== null && $brandSlugs !== []) {
            $qb
                ->andWhere('b.slug IN (:brands)')
                ->setParameter('brands', $brandSlugs);
        }

        if ($sizeSlugs !== null && $sizeSlugs !== []) {
            $qb
                ->innerJoin('p.categories', 'sizeCat')
                ->andWhere('sizeCat.slug IN (:sizes)')
                ->setParameter('sizes', $sizeSlugs);
        }

        return $qb
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findSizeCategoriesDynamic(
        ?array $brandSlugs = null,
        ?array $typeSlugs = null,
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->select('DISTINCT c')
            ->innerJoin('c.products', 'p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.categories', 'typeCat')
            ->innerJoin('c.parent', 'parent')
            ->andWhere('c.isActive = true')
            ->andWhere('p.isActive = true')
            ->andWhere('parent.slug = :parent')
            ->setParameter('parent', 'geschikt-voor');

        if ($brandSlugs !== null && $brandSlugs !== []) {
            $qb
                ->andWhere('b.slug IN (:brands)')
                ->setParameter('brands', $brandSlugs);
        }

        if ($typeSlugs !== null && $typeSlugs !== []) {
            $qb
                ->andWhere('typeCat.slug IN (:types)')
                ->setParameter('types', $typeSlugs);
        }

        return $qb
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}