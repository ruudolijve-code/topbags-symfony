<?php

namespace App\Catalog\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'supplier')]
class Supplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 150, unique: true)]
    private string $slug;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $parentCompany = null;

    #[ORM\Column(nullable: true)]
    private ?int $defaultLeadTimeMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $defaultLeadTimeMax = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: VariantSupply::class, orphanRemoval: true)]
    private Collection $variantSupplies;

    public function __construct()
    {
        $this->variantSupplies = new ArrayCollection();
        $this->isActive = true;
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

    public function getParentCompany(): ?string
    {
        return $this->parentCompany;
    }

    public function setParentCompany(?string $parentCompany): self
    {
        $this->parentCompany = $parentCompany;
        return $this;
    }

    public function getDefaultLeadTimeMin(): ?int
    {
        return $this->defaultLeadTimeMin;
    }

    public function setDefaultLeadTimeMin(?int $days): self
    {
        $this->defaultLeadTimeMin = $days;
        return $this;
    }

    public function getDefaultLeadTimeMax(): ?int
    {
        return $this->defaultLeadTimeMax;
    }

    public function setDefaultLeadTimeMax(?int $days): self
    {
        $this->defaultLeadTimeMax = $days;
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

    public function __toString(): string
    {
        return $this->name ?? ('Supplier #' . $this->id);
    }

    /**
     * @return Collection<int, VariantSupply>
     */
    public function getVariantSupplies(): Collection
    {
        return $this->variantSupplies;
    }

    public function addVariantSupply(VariantSupply $variantSupply): self
    {
        if (!$this->variantSupplies->contains($variantSupply)) {
            $this->variantSupplies->add($variantSupply);
            $variantSupply->setSupplier($this);
        }

        return $this;
    }

    public function removeVariantSupply(VariantSupply $variantSupply): self
    {
        if ($this->variantSupplies->removeElement($variantSupply)) {
            if ($variantSupply->getSupplier() === $this) {
                $variantSupply->setSupplier(null);
            }
        }

        return $this;
    }
}