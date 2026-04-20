<?php

namespace App\Guide\Repository;

use App\Guide\Entity\Faq;
use App\Guide\Entity\Airline;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FaqRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faq::class);
    }

    /**
     * Haal FAQ’s op voor context
     */
    public function findForContext(
        string $transport,
        ?Airline $airline = null
    ): array {

        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.transportType = :transport')
            ->andWhere('f.isActive = true')
            ->setParameter('transport', $transport)
            ->orderBy('f.position', 'ASC');

        if ($airline) {
            // Airline-specifieke FAQ + algemene FAQ
            $qb->andWhere('(f.airline = :airline OR f.airline IS NULL)')
               ->setParameter('airline', $airline);
        } else {
            $qb->andWhere('f.airline IS NULL');
        }

        return $qb->getQuery()->getResult();
    }
}