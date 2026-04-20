<?php

namespace App\Catalog\Entity;

use App\Catalog\Repository\ColorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ColorRepository::class)]
class Color
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $hex = null;

    #[ORM\Column(length: 20, options: ['default' => 'solid'])]
    private string $swatchType = 'solid';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $swatchValue = null;

    /**
     * @var Collection<int, ProductVariant>
     */
    #[ORM\OneToMany(mappedBy: 'color', targetEntity: ProductVariant::class)]
    private Collection $variants;

    #[ORM\Column(length: 100)]
    private ?string $family = null;

    /**
     * @var Collection<int, ProductVariant>
     */
    #[ORM\OneToMany(targetEntity: ProductVariant::class, mappedBy: 'normalizedColor')]
    private Collection $productVariants;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
        $this->swatchType = 'solid';
        $this->productVariants = new ArrayCollection();
    }

    /* --------------------
       Getters / setters
    -------------------- */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getHex(): ?string
    {
        return $this->hex;
    }

    public function setHex(?string $hex): static
    {
        $this->hex = $hex;
        return $this;
    }

    public function getSwatchType(): string
    {
        return $this->swatchType;
    }

    public function setSwatchType(string $swatchType): static
    {
        $this->swatchType = $swatchType;
        return $this;
    }

    public function getSwatchValue(): ?string
    {
        return $this->swatchValue;
    }

    public function setSwatchValue(?string $swatchValue): static
    {
        $this->swatchValue = $swatchValue;
        return $this;
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function getProductVariants(): Collection
    {
        return $this->variants;
    }

    public function getFamily(): ?string
    {
        return $this->family;
    }

    public function setFamily(string $family): static
    {
        $this->family = $family;

        return $this;
    }

    public function addProductVariant(ProductVariant $productVariant): static
    {
        if (!$this->productVariants->contains($productVariant)) {
            $this->productVariants->add($productVariant);
            $productVariant->setNormalizedColor($this);
        }

        return $this;
    }

    public function removeProductVariant(ProductVariant $productVariant): static
    {
        if ($this->productVariants->removeElement($productVariant)) {
            // set the owning side to null (unless already changed)
            if ($productVariant->getNormalizedColor() === $this) {
                $productVariant->setNormalizedColor(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? $this->slug ?? ('Color #' . $this->id);
    }
}