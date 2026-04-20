<?php

namespace App\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stock_movement')]
class StockMovement
{
    public const TYPE_SALE = 'sale';
    public const TYPE_RETURN = 'return';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_RESERVATION = 'reservation';
    public const TYPE_RELEASE = 'release';
    public const TYPE_BACKORDER_SALE = 'backorder_sale';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductVariant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProductVariant $variant;

    #[ORM\Column]
    private int $quantityChange;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $referenceType = null;

    #[ORM\Column(nullable: true)]
    private ?int $referenceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getQuantityChange(): int
    {
        return $this->quantityChange;
    }

    public function setQuantityChange(int $quantityChange): self
    {
        $this->quantityChange = $quantityChange;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getReferenceType(): ?string
    {
        return $this->referenceType;
    }

    public function setReferenceType(?string $referenceType): self
    {
        $this->referenceType = $referenceType;

        return $this;
    }

    public function getReferenceId(): ?int
    {
        return $this->referenceId;
    }

    public function setReferenceId(?int $referenceId): self
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %d (%s)',
            $this->variant->getVariantSku(),
            $this->quantityChange,
            $this->type
        );
    }
}