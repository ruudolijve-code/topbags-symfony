<?php

declare(strict_types=1);

namespace App\StyleGuide\Repository;

use App\Catalog\Entity\Product;
use App\StyleGuide\Entity\StyleGuideProductOverride;
use App\StyleGuide\Entity\StyleGuideWorld;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<StyleGuideProductOverride> */
final class StyleGuideProductOverrideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, StyleGuideProductOverride::class); }
    public function findActive(Product $product, StyleGuideWorld $world): ?StyleGuideProductOverride
    {
        return $this->findOneBy(['product' => $product, 'styleWorld' => $world, 'isActive' => true]);
    }
}
