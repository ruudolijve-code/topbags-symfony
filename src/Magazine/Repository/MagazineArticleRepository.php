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

    public function findPublishedBySlugWithRelations(
        string $slug,
        string $context
    ): ?MagazineArticle {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.faqs', 'f')
            ->addSelect('f')
            ->leftJoin('a.relatedProducts', 'p')
            ->addSelect('p')
            ->leftJoin('a.relatedBrands', 'b')
            ->addSelect('b')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.context = :context')
            ->andWhere('a.isPublished = true')
            ->setParameter('slug', $slug)
            ->setParameter('context', $context)
            ->orderBy('f.position', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->addOrderBy('b.name', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findFeaturedByContext(
        string $context
    ): ?MagazineArticle {
        return $this->findOneBy(
            [
                'context' => $context,
                'isPublished' => true,
                'isFeatured' => true,
            ],
            [
                'publishedAt' => 'DESC',
                'id' => 'DESC',
            ]
        );
    }

    /**
     * @return list<MagazineArticle>
     */
    public function findPublishedByContextExceptFeatured(
        string $context,
        ?MagazineArticle $featured = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.context = :context')
            ->andWhere('a.isPublished = true')
            ->setParameter('context', $context)
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.id', 'DESC');

        if ($featured !== null && $featured->getId() !== null) {
            $qb
                ->andWhere('a.id != :featuredId')
                ->setParameter('featuredId', $featured->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<MagazineArticle>
     */
    public function findPublishedForSitemap(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isPublished = true')
            ->orderBy('a.context', 'ASC')
            ->addOrderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<MagazineArticle>
     */
    public function findPublishedForSitemapByContext(
        string $context
    ): array {
        return $this->createQueryBuilder('a')
            ->andWhere('a.context = :context')
            ->andWhere('a.isPublished = true')
            ->setParameter('context', $context)
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

}