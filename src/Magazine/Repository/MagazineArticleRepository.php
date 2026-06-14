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
}