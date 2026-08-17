<?php

declare(strict_types=1);

namespace App\Repair\Repository;

use App\Repair\Entity\DamageReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DamageReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamageReport::class);
    }

    public function findHighestSequenceForYear(int $year): int
    {
        $prefix = sprintf('SR-%d-', $year);

        $result = $this->createQueryBuilder('r')
            ->select('r.reportNumber')
            ->andWhere('r.reportNumber LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('r.reportNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result === null) {
            return 0;
        }

        $reportNumber = $result['reportNumber'] ?? null;

        if (!is_string($reportNumber)) {
            return 0;
        }

        $parts = explode('-', $reportNumber);
        $sequence = end($parts);

        return is_numeric($sequence)
            ? (int) $sequence
            : 0;
    }
}