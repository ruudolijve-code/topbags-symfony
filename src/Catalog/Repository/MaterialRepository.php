<?php

namespace App\Catalog\Repository;

use App\Catalog\Entity\Material;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MaterialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Material::class);
    }

    /* ==========================================================
     * 🎒 Lichtgewicht materialen
     * - flexibel
     * - lage dichtheid (optioneel)
     * ========================================================== */

    /**
     * @return string[]  Slugs
     */
    public function findLightMaterialSlugs(float $maxDensity = 1.2): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.slug')
            ->andWhere('m.isFlexible = 1')
            ->andWhere('m.density IS NULL OR m.density <= :maxDensity')
            ->setParameter('maxDensity', $maxDensity)
            ->orderBy('m.density', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /* ==========================================================
     * 🛡️ Robuuste materialen
     * - rigid flag
     * ========================================================== */

    /**
     * @return string[]  Slugs
     */
    public function findRigidMaterialSlugs(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.slug')
            ->andWhere('m.isRigid = 1')
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /* ==========================================================
     * 🚀 Optioneel: direct Material entities (beter!)
     * ========================================================== */

    /**
     * @return Material[]
     */
    public function findLightMaterials(float $maxDensity = 1.2): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.isFlexible = 1')
            ->andWhere('m.density IS NULL OR m.density <= :maxDensity')
            ->setParameter('maxDensity', $maxDensity)
            ->orderBy('m.density', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Material[]
     */
    public function findRigidMaterials(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.isRigid = 1')
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}