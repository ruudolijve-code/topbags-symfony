<?php

namespace App\Guide\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TravelProfileTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'traits')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TravelProfile $profile;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(length: 100)]
    private string $label;

    #[ORM\Column(length: 255)]
    private string $value;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    // Getters only (no setters needed for now)

    public function getIcon(): ?string { return $this->icon; }
    public function getLabel(): string { return $this->label; }
    public function getValue(): string { return $this->value; }
    public function getPosition(): int { return $this->position; }
}