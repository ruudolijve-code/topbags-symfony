<?php

declare(strict_types=1);

namespace App\Repair\Service;

use App\Repair\Repository\DamageReportRepository;

final class DamageReportNumberGenerator
{
    private const PREFIX = 'SR';

    public function __construct(
        private readonly DamageReportRepository $repository,
    ) {
    }

    public function generate(
        ?\DateTimeImmutable $date = null,
    ): string {
        $date ??= new \DateTimeImmutable();

        $year = (int) $date->format('Y');

        $nextSequence = $this->repository
            ->findHighestSequenceForYear($year) + 1;

        return sprintf(
            '%s-%d-%06d',
            self::PREFIX,
            $year,
            $nextSequence,
        );
    }
}