<?php

namespace App\Guide\Entity;

use App\Guide\Repository\TravelProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: TravelProfileRepository::class)]
#[ORM\Table(name: 'travel_profile')]
class TravelProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private string $code;

    #[ORM\Column]
    private string $title;

    #[ORM\Column(nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(nullable: true)]
    private ?string $microcopy = null;

    #[ORM\Column(nullable: true)]
    private ?string $heroImage = null;

    #[ORM\Column(nullable: true)]
    private ?string $heroAlt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $storyIntro = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $storyBlock = null;

    #[ORM\Column(nullable: true)]
    private ?string $toneType = null;

    #[ORM\Column(nullable: true)]
    private ?string $priorityType = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /*
    |--------------------------------------------------------------------------
    | Traits relatie
    |--------------------------------------------------------------------------
    */

    #[ORM\OneToMany(
        mappedBy: 'profile',
        targetEntity: TravelProfileTrait::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $traits;

    public function __construct()
    {
        $this->traits = new ArrayCollection();
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getTitle(): string { return $this->title; }
    public function getSubtitle(): ?string { return $this->subtitle; }
    public function getMicrocopy(): ?string { return $this->microcopy; }
    public function getHeroImage(): ?string { return $this->heroImage; }
    public function getHeroAlt(): ?string { return $this->heroAlt; }
    public function getStoryIntro(): ?string { return $this->storyIntro; }
    public function getStoryBlock(): ?string { return $this->storyBlock; }
    public function getToneType(): ?string { return $this->toneType; }
    public function getPriorityType(): ?string { return $this->priorityType; }
    public function isActive(): bool { return $this->isActive; }

    public function getTraits(): Collection
    {
        return $this->traits;
    }
}