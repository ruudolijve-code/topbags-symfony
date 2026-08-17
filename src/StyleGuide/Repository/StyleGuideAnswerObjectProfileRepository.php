<?php

declare(strict_types=1);

namespace App\StyleGuide\Repository;

use App\StyleGuide\Entity\StyleGuideAnswerObjectProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StyleGuideAnswerObjectProfile>
 */
final class StyleGuideAnswerObjectProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            StyleGuideAnswerObjectProfile::class,
        );
    }

    /**
     * @param list<int> $answerIds
     * @return list<StyleGuideAnswerObjectProfile>
     */
    public function findActiveForAnswerIds(array $answerIds): array
    {
        if ($answerIds === []) {
            return [];
        }

        return $this->createQueryBuilder('mapping')
            ->addSelect('profile')
            ->innerJoin('mapping.objectProfile', 'profile')
            ->andWhere('IDENTITY(mapping.answer) IN (:answerIds)')
            ->andWhere('mapping.isActive = true')
            ->andWhere('profile.isActive = true')
            ->setParameter('answerIds', $answerIds)
            ->getQuery()
            ->getResult();
    }
}