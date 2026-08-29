<?php

declare(strict_types=1);

namespace App\StyleGuide\Entity;

use App\Catalog\Entity\Product;
use App\StyleGuide\Repository\StyleGuideProductOverrideRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StyleGuideProductOverrideRepository::class)]
#[ORM\Table(name: 'style_guide_product_override', uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_style_guide_product_world_override', columns: ['product_id', 'style_world_id'])])]
class StyleGuideProductOverride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StyleGuideWorld $styleWorld = null;

    #[ORM\Column]
    private int $scoreAdjustment = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    public function __toString(): string { return sprintf('%s — %s (%+d)', $this->styleWorld ?? 'Stijlwereld', $this->product ?? 'Product', $this->scoreAdjustment); }
    public function getId(): ?int { return $this->id; }
    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): self { $this->product = $product; return $this; }
    public function getStyleWorld(): ?StyleGuideWorld { return $this->styleWorld; }
    public function setStyleWorld(?StyleGuideWorld $styleWorld): self { $this->styleWorld = $styleWorld; return $this; }
    public function getScoreAdjustment(): int { return $this->scoreAdjustment; }
    public function setScoreAdjustment(int $scoreAdjustment): self { $this->scoreAdjustment = $scoreAdjustment; return $this; }
    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $reason): self { $value = $reason !== null ? trim($reason) : null; $this->reason = $value !== '' ? $value : null; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function getIsActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
}
