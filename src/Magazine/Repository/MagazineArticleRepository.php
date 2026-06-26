<?php

declare(strict_types=1);

namespace App\Magazine\Repository;

use App\Magazine\Entity\MagazineArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MagazineArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MagazineArticle::class);
    }

    public function findPublishedBySlugWithRelations(string $slug): ?MagazineArticle
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.faqs', 'f')
            ->addSelect('f')
            ->leftJoin('a.relatedProducts', 'p')
            ->addSelect('p')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.isPublished = true')
            ->setParameter('slug', $slug)
            ->orderBy('f.position', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findFeatured(): ?MagazineArticle
    {
        return $this->findOneBy(
            ['isPublished' => true, 'isFeatured' => true],
            ['publishedAt' => 'DESC', 'id' => 'DESC']
        );
    }

    public function findPublishedExceptFeatured(?MagazineArticle $featured = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.isPublished = true')
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.id', 'DESC');

        if ($featured && $featured->getId()) {
            $qb
                ->andWhere('a.id != :featuredId')
                ->setParameter('featuredId', $featured->getId());
        }

        return $qb->getQuery()->getResult();
    }
}