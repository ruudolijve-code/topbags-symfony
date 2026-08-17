<?php

declare(strict_types=1);

namespace App\Catalog\Repository;

use App\Catalog\Entity\ProductContext;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductContext>
 */
final class ProductContextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductContext::class);
    }
}