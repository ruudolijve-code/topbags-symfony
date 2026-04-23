<?php

namespace App\Seo\Repository;

use App\Seo\Entity\Redirect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RedirectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Redirect::class);
    }

    public function findActiveByPath(string $path): ?Redirect
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.oldPath = :path')
            ->andWhere('r.isActive = :active')
            ->setParameter('path', $path)
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}