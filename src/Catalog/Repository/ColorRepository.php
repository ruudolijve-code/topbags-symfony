<?php

namespace App\Catalog\Repository;

use App\Catalog\Entity\Color;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Color>
 */
final class ColorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Color::class);
    }

    /**
     * Haal alle actieve kleuren op, alfabetisch gesorteerd
     *
     * @return Color[]
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('c')
            
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
