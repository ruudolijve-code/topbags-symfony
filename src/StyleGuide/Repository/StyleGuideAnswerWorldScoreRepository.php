<?php

declare(strict_types=1);

namespace App\StyleGuide\Repository;

use App\StyleGuide\Entity\StyleGuideAnswerWorldScore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StyleGuideAnswerWorldScore>
 */
final class StyleGuideAnswerWorldScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            StyleGuideAnswerWorldScore::class,
        );
    }
}