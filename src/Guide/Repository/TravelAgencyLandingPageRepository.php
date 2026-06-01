<?php

declare(strict_types=1);

namespace App\Guide\Repository;

use App\Guide\Entity\TravelAgencyLandingPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TravelAgencyLandingPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TravelAgencyLandingPage::class);
    }

    public function findActiveBySlug(string $slug): ?TravelAgencyLandingPage
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.slug = :slug')
            ->andWhere('p.isActive = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return TravelAgencyLandingPage[]
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = true')
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.city', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}