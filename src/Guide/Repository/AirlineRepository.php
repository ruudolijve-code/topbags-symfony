<?php

namespace App\Guide\Repository;

use App\Guide\Entity\Airline;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Airline>
 */
class AirlineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Airline::class);
    }

    /**
     * Actieve airlines voor de gids
     *
     * @return Airline[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isActive = true')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Airline ophalen via slug (bijv. "klm")
     */
    public function findActiveBySlug(string $slug): ?Airline
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.isActive = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

        /**
     * Airline ophalen voor filter
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isActive = true')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}