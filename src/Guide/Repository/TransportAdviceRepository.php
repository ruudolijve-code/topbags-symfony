<?php

namespace App\Guide\Repository;

use App\Entity\Guide\TransportAdvice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransportAdviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransportAdvice::class);
    }

    public function findActiveByTransport(string $transport): ?TransportAdvice
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.transport = :transport')
            ->andWhere('a.isActive = true')
            ->setParameter('transport', $transport)
            ->getQuery()
            ->getOneOrNullResult();
    }
}