<?php

namespace App\Catalog\Repository;

use App\Catalog\Entity\ProductVariant;
use Doctrine\ORM\Query;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductVariant>
 */
class ProductVariantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductVariant::class);
    }

    //    /**
    //     * @return ProductVariant[] Returns an array of ProductVariant objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ProductVariant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findOneForGridBySku(string $variantSku): ?ProductVariant
    {
        return $this->createQueryBuilder('v')
            ->select('v', 'images', 'color', 'stock', 'product')
            ->leftJoin('v.images', 'images')
            ->leftJoin('v.color', 'color')
            ->leftJoin('v.stock', 'stock')
            ->leftJoin('v.product', 'product')
            ->andWhere('v.variantSku = :sku')
            ->setParameter('sku', $variantSku)
            ->orderBy('images.position', 'ASC')
            ->addOrderBy('images.id', 'ASC')
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    public function findActiveForMetaFeed(): array
    {
        return $this->createQueryBuilder('v')
            ->innerJoin('v.product', 'p')
            ->addSelect('p')
            ->leftJoin('p.brand', 'b')
            ->addSelect('b')
            ->leftJoin('v.images', 'i')
            ->addSelect('i')
            ->andWhere('v.isActive = true')
            ->andWhere('p.isActive = true')
            ->andWhere('p.slug IS NOT NULL')
            ->andWhere('v.variantSku IS NOT NULL')
            ->andWhere('v.supplierColorSlug IS NOT NULL')
            ->orderBy('p.id', 'DESC')
            ->addOrderBy('v.isMaster', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
