<?php

namespace App\Guide\Repository;

use App\Guide\Entity\Airline;
use App\Guide\Entity\AirlineTicketType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AirlineTicketTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AirlineTicketType::class);
    }

    /**
     * @return AirlineTicketType[]
     */
    public function findActiveForAirline(Airline $airline): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.airline = :airline')
            ->andWhere('t.isActive = true')
            ->setParameter('airline', $airline)
            ->orderBy('t.priorityLevel', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
