<?php

namespace App\Catalog\Entity;

use App\Catalog\Repository\MaterialRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaterialRepository::class)]
#[ORM\Table(name: 'material')]
class Material
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $name = '';

    #[ORM\Column(length: 50, unique: true)]
    private string $slug = '';

    #[ORM\Column(nullable: true)]
    private ?float $density = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isRigid = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $isFlexible = false;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $sustainabilityScore = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $marketPositionModifier = 0;

    #[ORM\ManyToOne(inversedBy: 'materials')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MaterialFamily $family = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $notes = null;

    public function __toString(): string
    {
        return $this->name ?: ('Material #' . $this->id);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDensity(): ?float
    {
        return $this->density;
    }

    public function setDensity(?float $density): self
    {
        $this->density = $density;

        return $this;
    }

    public function isRigid(): bool
    {
        return $this->isRigid;
    }

    public function getIsRigid(): bool
    {
        return $this->isRigid;
    }

    public function setIsRigid(bool $isRigid): self
    {
        $this->isRigid = $isRigid;

        return $this;
    }

    public function isFlexible(): bool
    {
        return $this->isFlexible;
    }

    public function getIsFlexible(): bool
    {
        return $this->isFlexible;
    }

    public function setIsFlexible(bool $isFlexible): self
    {
        $this->isFlexible = $isFlexible;

        return $this;
    }

    public function getSustainabilityScore(): ?int
    {
        return $this->sustainabilityScore;
    }

    public function setSustainabilityScore(?int $sustainabilityScore): self
    {
        $this->sustainabilityScore = $sustainabilityScore;

        return $this;
    }

    public function getMarketPositionModifier(): int
    {
        return $this->marketPositionModifier;
    }

    public function setMarketPositionModifier(int $marketPositionModifier): self
    {
        $this->marketPositionModifier = max(
            -30,
            min(30, $marketPositionModifier),
        );

        return $this;
    }

    public function getFamily(): ?MaterialFamily
    {
        return $this->family;
    }

    public function setFamily(?MaterialFamily $family): self
    {
        $this->family = $family;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }
}
