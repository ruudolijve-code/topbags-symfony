<?php

namespace App\Shop\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column]
    private string $productName;

    #[ORM\Column]
    private string $variantSku;

    #[ORM\Column]
    private float $price;

    #[ORM\Column]
    private int $qty;

    #[ORM\Column]
    private float $lineTotal;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getVariantSku(): ?string
    {
        return $this->variantSku;
    }

    public function setVariantSku(string $variantSku): static
    {
        $this->variantSku = $variantSku;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function setQty(int $qty): static
    {
        $this->qty = $qty;

        return $this;
    }

    public function getLineTotal(): ?float
    {
        return $this->lineTotal;
    }

    public function setLineTotal(float $lineTotal): static
    {
        $this->lineTotal = $lineTotal;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

}