<?php

declare(strict_types=1);

namespace App\Catalog\Repository;

use App\Catalog\Entity\Size;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Size>
 *
 * @method Size|null find($id, $lockMode = null, $lockVersion = null)
 * @method Size|null findOneBy(array $criteria, array $orderBy = null)
 * @method Size[]    findAll()
 * @method Size[]    findBy(
 *     array $criteria,
 *     array $orderBy = null,
 *     $limit = null,
 *     $offset = null
 * )
 */
final class SizeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Size::class);
    }

    /**
     * @return Size[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('size')
            ->andWhere('size.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('size.sortOrder', 'ASC')
            ->addOrderBy('size.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveBySlug(string $slug): ?Size
    {
        return $this->createQueryBuilder('size')
            ->andWhere('size.slug = :slug')
            ->andWhere('size.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByCode(string $code): ?Size
    {
        return $this->createQueryBuilder('size')
            ->andWhere('size.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Size[]
     */
    public function findActiveBySearchTerm(string $searchTerm): array
    {
        $searchTerm = trim($searchTerm);

        return $this->createQueryBuilder('size')
            ->andWhere('size.isActive = :active')
            ->andWhere(
                'LOWER(size.name) LIKE LOWER(:searchTerm)
                OR LOWER(size.code) LIKE LOWER(:searchTerm)'
            )
            ->setParameter('active', true)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('size.sortOrder', 'ASC')
            ->addOrderBy('size.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}