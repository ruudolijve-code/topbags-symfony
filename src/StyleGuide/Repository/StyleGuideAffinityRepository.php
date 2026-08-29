<?php

declare(strict_types=1);

namespace App\StyleGuide\Repository;

use App\StyleGuide\Entity\StyleGuideAffinity;
use App\StyleGuide\Entity\StyleGuideWorld;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<StyleGuideAffinity> */
final class StyleGuideAffinityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, StyleGuideAffinity::class); }

    /** @return list<StyleGuideAffinity> */
    public function findActiveForWorld(StyleGuideWorld $world): array
    {
        return $this->createQueryBuilder('a')->addSelect('brand', 'material', 'color', 'category')
            ->leftJoin('a.brand', 'brand')->leftJoin('a.material', 'material')->leftJoin('a.color', 'color')->leftJoin('a.category', 'category')
            ->andWhere('a.styleWorld = :world')->andWhere('a.isActive = true')->setParameter('world', $world)
            ->addOrderBy('a.position', 'ASC')->addOrderBy('a.id', 'ASC')->getQuery()->getResult();
    }
}
