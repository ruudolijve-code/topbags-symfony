<?php

namespace App\Guide\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TravelDuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $label; // "1–4 dagen"

    #[ORM\Column]
    private int $minDays;

    #[ORM\Column]
    private int $maxDays;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    public function getId(): ?int { return $this->id; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): self { $this->label = $label; return $this; }

    public function getMinDays(): int { return $this->minDays; }
    public function setMinDays(int $minDays): self { $this->minDays = $minDays; return $this; }

    public function getMaxDays(): int { return $this->maxDays; }
    public function setMaxDays(int $maxDays): self { $this->maxDays = $maxDays; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
}