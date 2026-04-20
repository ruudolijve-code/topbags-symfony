<?php

namespace App\Guide\Repository;

use App\Guide\Entity\TransportCategoryMap;
use App\Catalog\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransportCategoryMapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransportCategoryMap::class);
    }

    /**
     * Alle mappings voor transport (debug / admin / beheer)
     *
     * @return TransportCategoryMap[]
     */
    public function findForTransport(string $transport): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.transport = :transport')
            ->andWhere('m.isActive = 1')
            ->setParameter('transport', $transport)
            ->orderBy('m.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Categories voor transport (+ optioneel bagage scopes)
     *
     * @return Category[]
     */
   
    public function findCategoriesForTransport(
        string $transport,
        array $baggageTypes = []
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from(Category::class, 'c')
            ->innerJoin(
                TransportCategoryMap::class,
                'm',
                'WITH',
                'm.category = c'
            )
            ->where('m.transport = :transport')
            ->andWhere('m.isActive = 1')
            ->setParameter('transport', $transport)
            ->orderBy('m.priority', 'ASC');

        if (!empty($baggageTypes)) {
            $qb
                ->andWhere('m.baggageType IN (:types)')
                ->setParameter('types', $baggageTypes);
        }

        return $qb->getQuery()->getResult(); // Category[]
    }

    /**
     * Alleen category IDs (handig voor snelle product queries)
     *
     * @return int[]
     */
    public function findCategoryIdsForTransport(
        string $transport,
        array $baggageTypes = []
    ): array {
        $categories = $this->findCategoriesForTransport($transport, $baggageTypes);

        return array_map(
            static fn (Category $c) => $c->getId(),
            $categories
        );
    }
}