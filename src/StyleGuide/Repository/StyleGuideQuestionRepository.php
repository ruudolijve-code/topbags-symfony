<?php

declare(strict_types=1);

namespace App\StyleGuide\Repository;

use App\StyleGuide\Entity\StyleGuideQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StyleGuideQuestion>
 */
final class StyleGuideQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StyleGuideQuestion::class);
    }

    /**
     * @return list<StyleGuideQuestion>
     */
    public function findActiveWithAnswers(): array
    {
        return $this->createQueryBuilder('question')
            ->addSelect('answers')
            ->leftJoin(
                'question.answers',
                'answers',
                'WITH',
                'answers.isActive = true',
            )
            ->andWhere('question.isActive = true')
            ->orderBy('question.position', 'ASC')
            ->addOrderBy('answers.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}