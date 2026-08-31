<?php

declare(strict_types=1);

namespace App\Catalog\Repository;

use App\Catalog\Entity\MaterialFamily;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MaterialFamilyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaterialFamily::class);
    }
}
