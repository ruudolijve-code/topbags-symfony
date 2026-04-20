<?php

namespace App\Catalog\Entity;

use App\Catalog\Repository\StockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\Table(name: 'stock')]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $onHand = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $reserved = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(inversedBy: 'stock')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', unique: true)]
    private ProductVariant $productVariant;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOnHand(): int
    {
        return $this->onHand;
    }

    public function setOnHand(int $onHand): self
    {
        $this->onHand = max(0, $onHand);
        $this->touch();

        return $this;
    }

    public function getReserved(): int
    {
        return $this->reserved;
    }

    public function setReserved(int $reserved): self
    {
        $this->reserved = max(0, $reserved);
        $this->touch();

        return $this;
    }

    public function getAvailable(): int
    {
        return max(0, $this->onHand - $this->reserved);
    }

    public function increase(int $qty): self
    {
        $this->onHand += max(0, $qty);
        $this->touch();

        return $this;
    }

    public function decrease(int $qty): self
    {
        $this->onHand = max(0, $this->onHand - max(0, $qty));
        $this->touch();

        return $this;
    }

    public function reserve(int $qty): self
    {
        $this->reserved += max(0, $qty);
        $this->touch();

        return $this;
    }

    public function release(int $qty): self
    {
        $this->reserved = max(0, $this->reserved - max(0, $qty));
        $this->touch();

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getProductVariant(): ProductVariant
    {
        return $this->productVariant;
    }

    public function setProductVariant(ProductVariant $productVariant): self
    {
        $this->productVariant = $productVariant;

        if ($productVariant->getStock() !== $this) {
            $productVariant->setStock($this);
        }

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        $sku = $this->productVariant?->getVariantSku() ?? 'unknown';

        return sprintf(
            '%s — vrij: %d (op voorraad: %d)',
            $sku,
            $this->getAvailable(),
            $this->onHand
        );
    }
}