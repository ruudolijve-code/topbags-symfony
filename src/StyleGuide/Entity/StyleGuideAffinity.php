<?php

declare(strict_types=1);

namespace App\StyleGuide\Entity;

use App\Catalog\Entity\Brand;
use App\Catalog\Entity\Category;
use App\Catalog\Entity\Color;
use App\Catalog\Entity\Material;
use App\StyleGuide\Repository\StyleGuideAffinityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: StyleGuideAffinityRepository::class)]
#[ORM\Table(name: 'style_guide_affinity')]
class StyleGuideAffinity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StyleGuideWorld $styleWorld = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Brand $brand = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Material $material = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Color $color = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Category $category = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $colorFamily = null;

    #[ORM\Column]
    private int $score = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $position = 0;

    public function __toString(): string
    {
        return sprintf('%s — %s (%+d)', $this->styleWorld ?? 'Stijlwereld', $this->targetLabel(), $this->score);
    }

    public function getId(): ?int { return $this->id; }
    public function getStyleWorld(): ?StyleGuideWorld { return $this->styleWorld; }
    public function setStyleWorld(?StyleGuideWorld $styleWorld): self { $this->styleWorld = $styleWorld; return $this; }
    public function getBrand(): ?Brand { return $this->brand; }
    public function setBrand(?Brand $brand): self { $this->brand = $brand; return $this; }
    public function getMaterial(): ?Material { return $this->material; }
    public function setMaterial(?Material $material): self { $this->material = $material; return $this; }
    public function getColor(): ?Color { return $this->color; }
    public function setColor(?Color $color): self { $this->color = $color; return $this; }
    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): self { $this->category = $category; return $this; }
    public function getColorFamily(): ?string { return $this->colorFamily; }
    public function setColorFamily(?string $colorFamily): self { $value = $colorFamily !== null ? strtolower(trim($colorFamily)) : null; $this->colorFamily = $value !== '' ? $value : null; return $this; }
    public function getScore(): int { return $this->score; }
    public function setScore(int $score): self { $this->score = $score; return $this; }
    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $reason): self { $value = $reason !== null ? trim($reason) : null; $this->reason = $value !== '' ? $value : null; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function getIsActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = max(0, $position); return $this; }

    #[\Symfony\Component\Validator\Constraints\Callback]
    public function validateExactlyOneTarget(ExecutionContextInterface $context): void
    {
        $targets = [$this->brand, $this->material, $this->color, $this->category, $this->colorFamily];
        if (count(array_filter($targets, static fn (mixed $target): bool => $target !== null)) !== 1) {
            $context->buildViolation('Kies exact één doel: merk, materiaal, kleur, categorie of kleurfamilie.')->addViolation();
        }
    }

    public function targetLabel(): string
    {
        return (string) ($this->brand ?? $this->material ?? $this->color ?? $this->category ?? $this->colorFamily ?? 'Geen doel');
    }
}
