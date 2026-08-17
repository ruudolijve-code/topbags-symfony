<?php

declare(strict_types=1);

namespace App\StyleGuide\Repository;

use App\StyleGuide\Entity\StyleGuideWorld;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StyleGuideWorld>
 */
final class StyleGuideWorldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StyleGuideWorld::class);
    }

    /**
     * @return list<StyleGuideWorld>
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('world')
            ->andWhere('world.isActive = true')
            ->orderBy('world.position', 'ASC')
            ->addOrderBy('world.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveBySlug(
        string $slug,
    ): ?StyleGuideWorld {
        return $this->findOneBy([
            'slug' => $slug,
            'isActive' => true,
        ]);
    }
}