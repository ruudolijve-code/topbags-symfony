<?php

declare(strict_types=1);

namespace App\Repair\Doctrine;

use App\Repair\Entity\DamageReportImage;
use App\Repair\Service\DamageReportImageUploader;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(
    event: Events::preRemove,
    method: 'preRemove',
    entity: DamageReportImage::class,
)]
final class DamageReportImageListener
{
    public function __construct(
        private readonly DamageReportImageUploader $imageUploader,
    ) {
    }

    public function preRemove(DamageReportImage $image): void
    {
        $report = $image->getDamageReport();
        $filename = $image->getFilename();

        if ($report === null || $filename === '') {
            return;
        }

        $this->imageUploader->delete(
            $report,
            $filename,
        );
    }
}