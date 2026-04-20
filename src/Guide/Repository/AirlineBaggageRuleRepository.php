<?php

namespace App\Guide\Repository;

use App\Guide\Entity\AirlineBaggageRule;
use App\Guide\Entity\Airline;
use App\Guide\Entity\AirlineTicketType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AirlineBaggageRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AirlineBaggageRule::class);
    }

    /**
     * @return AirlineBaggageRule[]
     */
    public function findActiveByTicket(int $ticketTypeId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.ticketType = :ticket')
            ->andWhere('r.isActive = true')
            ->setParameter('ticket', $ticketTypeId)
            ->orderBy('r.ruleScope', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Actieve baggage rules voor airline (+ optioneel ticket)
     *
     * @return AirlineBaggageRule[]
     */
    public function findActiveForAirlineAndTicket(
        Airline $airline,
        ?AirlineTicketType $ticket
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.airline = :airline')
            ->andWhere('r.isActive = true')
            ->setParameter('airline', $airline);

        if ($ticket !== null) {
            $qb
                ->andWhere('r.ticketType = :ticket')
                ->setParameter('ticket', $ticket);
        } else {
            $qb->andWhere('r.ticketType IS NULL');
        }

        return $qb
            ->orderBy('r.ruleScope', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AirlineBaggageRule[]
     */
    public function findActiveForAirline(Airline $airline): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.airline = :airline')
            ->andWhere('r.isActive = 1')
            ->setParameter('airline', $airline)
            ->getQuery()
            ->getResult();
    }
}