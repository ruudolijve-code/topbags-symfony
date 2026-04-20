<?php

namespace App\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'material')]
class Material
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    #[ORM\Column(length: 50, unique: true)]
    private string $slug;

    // optioneel maar handig
    #[ORM\Column(nullable: true)]
    private ?float $density = null;

    #[ORM\Column(options: ["default" => true])]
    private ?bool $isRigid = true;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $isFlexible = false;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $sustainabilityScore = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $notes = null;

    public function isFlexible(): bool
    {
        return (bool) $this->isFlexible;
    }

    public function isRigid(): bool
    {
        return (bool) $this->isRigid;
    }

    public function __toString(): string
    {
        return $this->name ?? ('Material #' . $this->id);
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }

    public function getDensity(): ?float { return $this->density; }
    public function setDensity(?float $density): self { $this->density = $density; return $this; }

    public function getIsRigid(): bool { return $this->isRigid; }
    public function setIsRigid(bool $isRigid): self { $this->isRigid = $isRigid; return $this; }

    public function getIsFlexible(): bool { return $this->isFlexible; }
    public function setIsFlexible(bool $isFlexible): self { $this->isFlexible = $isFlexible; return $this; }

}