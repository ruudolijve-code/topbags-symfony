<?php

namespace App\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'variant_supply')]
class VariantSupply
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductVariant::class, inversedBy: 'supplies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProductVariant $variant;

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'variantSupplies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Supplier $supplier;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $supplierSku = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?int $leadTimeMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $leadTimeMax = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVariant(): ProductVariant
    {
        return $this->variant;
    }

    public function setVariant(ProductVariant $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSupplierSku(): ?string
    {
        return $this->supplierSku;
    }

    public function setSupplierSku(?string $supplierSku): self
    {
        $this->supplierSku = $supplierSku;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getLeadTimeMin(): ?int
    {
        return $this->leadTimeMin;
    }

    public function setLeadTimeMin(?int $days): self
    {
        $this->leadTimeMin = $days;

        return $this;
    }

    public function getLeadTimeMax(): ?int
    {
        return $this->leadTimeMax;
    }

    public function setLeadTimeMax(?int $days): self
    {
        $this->leadTimeMax = $days;

        return $this;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): self
    {
        $this->lastSyncedAt = $lastSyncedAt;

        return $this;
    }

    public function getEffectiveLeadTimeMin(): ?int
    {
        return $this->leadTimeMin ?? $this->supplier->getDefaultLeadTimeMin();
    }

    public function getEffectiveLeadTimeMax(): ?int
    {
        return $this->leadTimeMax ?? $this->supplier->getDefaultLeadTimeMax();
    }

    public function isPurchasable(): bool
    {
        return $this->isActive && $this->supplier->isActive();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s → %s',
            $this->variant->getVariantSku(),
            $this->supplier->getName()
        );
    }
}