<?php

declare(strict_types=1);

namespace App\Catalog\Entity;

use App\Catalog\Repository\BrandRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BrandRepository::class)]
#[ORM\Table(name: 'brand')]
class Brand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Basispositionering van het merk voor de Style Guide.
     *
     * Dit is nadrukkelijk niet de uiteindelijke productkwaliteit.
     * Die wordt later samengesteld uit:
     *
     * - merkpositionering
     * - materiaalmodifier
     * - prijspositie
     * - optionele productoverride
     *
     * Bereik: 0 t/m 100.
     *
     * De databasekolom wordt via een databehoudende migratie hernoemd.
     */
    #[ORM\Column(
        name: 'market_position',
        options: ['default' => 50],
    )]
    private int $marketPosition = 50;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Supplier $defaultSupplier = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(
        mappedBy: 'brand',
        targetEntity: Product::class,
    )]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = trim($slug);

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo !== null
            ? trim($logo)
            : null;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description !== null
            ? trim($description)
            : null;

        return $this;
    }

    public function getMarketPosition(): int
    {
        return $this->marketPosition;
    }

    public function setMarketPosition(int $marketPosition): self
    {
        $this->marketPosition = max(
            0,
            min(100, $marketPosition),
        );

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

    public function getDefaultSupplier(): ?Supplier
    {
        return $this->defaultSupplier;
    }

    public function setDefaultSupplier(
        ?Supplier $defaultSupplier,
    ): self {
        $this->defaultSupplier = $defaultSupplier;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setBrand($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            if ($product->getBrand() === $this) {
                /*
                 * Alleen uitvoeren wanneer Product::brand nullable is.
                 * Als brand verplicht is, deze regel liever verwijderen.
                 */
                $product->setBrand(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name
            ?? sprintf('Brand #%s', $this->id ?? 'nieuw');
    }
}
