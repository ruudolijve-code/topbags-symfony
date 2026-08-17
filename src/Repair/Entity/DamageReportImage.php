<?php

declare(strict_types=1);

namespace App\Repair\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'damage_report_image')]
class DamageReportImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(
        targetEntity: DamageReport::class,
        inversedBy: 'images'
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DamageReport $damageReport = null;

    #[ORM\Column(length: 255)]
    private string $filename = '';

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDamageReport(): ?DamageReport
    {
        return $this->damageReport;
    }

    public function setDamageReport(?DamageReport $damageReport): self
    {
        $this->damageReport = $damageReport;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = max(0, $position);

        return $this;
    }

    private ?UploadedFile $uploadedFile = null;

    public function getUploadedFile(): ?UploadedFile
    {
        return $this->uploadedFile;
    }

    public function setUploadedFile(?UploadedFile $uploadedFile): self
    {
        $this->uploadedFile = $uploadedFile;

        return $this;
    }

    public function __toString(): string
    {
        return $this->filename !== ''
            ? $this->filename
            : 'Schadefoto';
    }
}