<?php

namespace App\Catalog\Entity;

use App\Catalog\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
class Product
{
    public const CONTEXT_SHOP = 'shop';
    public const CONTEXT_BAGS = 'bags';

    private const ALLOWED_CONTEXTS = [
        self::CONTEXT_SHOP,
        self::CONTEXT_BAGS,
    ];

    public const LW_ULTRA_LIGHT = 'ultra_light';
    public const LW_LIGHT = 'light';
    public const LW_NORMAL = 'normal';
    public const LW_HEAVY = 'heavy';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $modelSku;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $series = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Material $material = null;

    #[ORM\Column(nullable: true)]
    private ?float $heightCm = null;

    #[ORM\Column(nullable: true)]
    private ?float $widthCm = null;

    #[ORM\Column(nullable: true)]
    private ?float $depthCm = null;

    #[ORM\Column(nullable: true)]
    private ?float $weightKg = null;

    #[ORM\Column(nullable: true)]
    private ?float $volumeL = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $expandable = false;

    #[ORM\Column(nullable: true)]
    private ?float $expandableVolumeL = null;

    #[ORM\Column(nullable: true)]
    private ?float $expandableDepthCm = null;

    #[ORM\Column(nullable: true)]
    private ?int $wheelsCount = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $warrantyYears = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $luggageType = null;

    #[ORM\Column(length: 20, options: ['default' => self::CONTEXT_SHOP])]
    private string $productContext = self::CONTEXT_SHOP;

    #[ORM\Column(options: ['default' => false])]
    private bool $cabinSize = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $underseater = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $tsaLock = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $closureType = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $laptopCompartment = false;

    #[ORM\Column(nullable: true)]
    private ?float $laptopMaxInch = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(
        name: 'weight_per_liter',
        type: 'integer',
        nullable: true,
        insertable: false,
        updatable: false
    )]
    private ?int $weightPerLiter = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private Brand $brand;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinTable(name: 'product_category')]
    private Collection $categories;

    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductVariant::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        fetch: 'EXTRA_LAZY'
    )]
    private Collection $variants;

    private array $badges = [];

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->variants = new ArrayCollection();
    }

    public function getMasterVariant(): ?ProductVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->isMaster() && $variant->isActive()) {
                return $variant;
            }
        }

        return null;
    }

    public function addBadge(array $badge): void
    {
        $this->badges[] = $badge;
    }

    public function getBadges(): array
    {
        return $this->badges;
    }

    public function getLightweightClass(): ?string
    {
        if ($this->weightPerLiter === null) {
            return null;
        }

        return match (true) {
            $this->weightPerLiter <= 60 => self::LW_ULTRA_LIGHT,
            $this->weightPerLiter <= 75 => self::LW_LIGHT,
            $this->weightPerLiter <= 95 => self::LW_NORMAL,
            default => self::LW_HEAVY,
        };
    }

    public function getLightweightLabel(): ?string
    {
        return match ($this->getLightweightClass()) {
            self::LW_ULTRA_LIGHT => 'Ultra licht',
            self::LW_LIGHT => 'Lichtgewicht',
            self::LW_NORMAL => 'Stevig',
            self::LW_HEAVY => 'Extra stevig',
            default => null,
        };
    }

    public function isUltraLight(): bool
    {
        return $this->getLightweightClass() === self::LW_ULTRA_LIGHT;
    }

    public function isLightweight(): bool
    {
        $class = $this->getLightweightClass();

        return $class === self::LW_ULTRA_LIGHT || $class === self::LW_LIGHT;
    }

    public function isHeavy(): bool
    {
        return $this->getLightweightClass() === self::LW_HEAVY;
    }

    public function getMainImageUrl(): ?string
    {
        return $this->getMasterVariant()?->getPrimaryImagePath();
    }

    public function isDuffle(): bool
    {
        return $this->luggageType === 'duffle';
    }

    public function isTrolley(): bool
    {
        return in_array($this->luggageType, ['hardcase', 'softcase', 'duffle_trolley'], true);
    }

    public function isBackpack(): bool
    {
        return $this->luggageType === 'backpack';
    }

    public function isShopContext(): bool
    {
        return $this->productContext === self::CONTEXT_SHOP;
    }

    public function isBagsContext(): bool
    {
        return $this->productContext === self::CONTEXT_BAGS;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeightPerLiter(): ?int
    {
        return $this->weightPerLiter;
    }

    public function getModelSku(): string
    {
        return $this->modelSku;
    }

    public function setModelSku(string $modelSku): self
    {
        $this->modelSku = $modelSku;

        return $this;
    }

    public function getSeries(): ?string
    {
        return $this->series;
    }

    public function setSeries(?string $series): self
    {
        $this->series = $series;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getMaterial(): ?Material
    {
        return $this->material;
    }

    public function setMaterial(?Material $material): self
    {
        $this->material = $material;

        return $this;
    }

    public function getHeightCm(): ?float
    {
        return $this->heightCm;
    }

    public function setHeightCm(?float $heightCm): self
    {
        $this->heightCm = $heightCm;

        return $this;
    }

    public function getWidthCm(): ?float
    {
        return $this->widthCm;
    }

    public function setWidthCm(?float $widthCm): self
    {
        $this->widthCm = $widthCm;

        return $this;
    }

    public function getDepthCm(): ?float
    {
        return $this->depthCm;
    }

    public function setDepthCm(?float $depthCm): self
    {
        $this->depthCm = $depthCm;

        return $this;
    }

    public function getWeightKg(): ?float
    {
        return $this->weightKg;
    }

    public function setWeightKg(?float $weightKg): self
    {
        $this->weightKg = $weightKg;

        return $this;
    }

    public function getVolumeL(): ?float
    {
        return $this->volumeL;
    }

    public function setVolumeL(?float $volumeL): self
    {
        $this->volumeL = $volumeL;

        return $this;
    }

    public function isExpandable(): bool
    {
        return $this->expandable;
    }

    public function setExpandable(bool $expandable): self
    {
        $this->expandable = $expandable;

        return $this;
    }

    public function getExpandableVolumeL(): ?float
    {
        return $this->expandableVolumeL;
    }

    public function setExpandableVolumeL(?float $expandableVolumeL): self
    {
        $this->expandableVolumeL = $expandableVolumeL;

        return $this;
    }

    public function getExpandableDepthCm(): ?float
    {
        return $this->expandableDepthCm;
    }

    public function setExpandableDepthCm(?float $expandableDepthCm): self
    {
        $this->expandableDepthCm = $expandableDepthCm;

        return $this;
    }

    public function getWheelsCount(): ?int
    {
        return $this->wheelsCount;
    }

    public function setWheelsCount(?int $wheelsCount): self
    {
        $this->wheelsCount = $wheelsCount;

        return $this;
    }

    public function getWarrantyYears(): ?string
    {
        return $this->warrantyYears;
    }

    public function setWarrantyYears(?string $warrantyYears): self
    {
        $this->warrantyYears = $warrantyYears;

        return $this;
    }

    public function getLuggageType(): ?string
    {
        return $this->luggageType;
    }

    public function setLuggageType(?string $luggageType): self
    {
        $this->luggageType = $luggageType;

        return $this;
    }

    public function getProductContext(): string
    {
        return $this->productContext;
    }

    public function setProductContext(string $productContext): self
    {
        if (!in_array($productContext, self::ALLOWED_CONTEXTS, true)) {
            throw new \InvalidArgumentException('Invalid product context.');
        }

        $this->productContext = $productContext;

        if ($productContext === self::CONTEXT_BAGS) {
            $this->luggageType = null;
            $this->cabinSize = false;
            $this->underseater = false;
        }

        return $this;
    }

    public function isCabinSize(): bool
    {
        return $this->cabinSize;
    }

    public function setCabinSize(bool $cabinSize): self
    {
        $this->cabinSize = $cabinSize;

        return $this;
    }

    public function isUnderseater(): bool
    {
        return $this->underseater;
    }

    public function setUnderseater(bool $underseater): self
    {
        $this->underseater = $underseater;

        return $this;
    }

    public function isTsaLock(): bool
    {
        return $this->tsaLock;
    }

    public function setTsaLock(bool $tsaLock): self
    {
        $this->tsaLock = $tsaLock;

        return $this;
    }

    public function getClosureType(): ?string
    {
        return $this->closureType;
    }

    public function setClosureType(?string $closureType): self
    {
        $this->closureType = $closureType;

        return $this;
    }

    public function isLaptopCompartment(): bool
    {
        return $this->laptopCompartment;
    }

    public function setLaptopCompartment(bool $laptopCompartment): self
    {
        $this->laptopCompartment = $laptopCompartment;

        return $this;
    }

    public function getLaptopMaxInch(): ?float
    {
        return $this->laptopMaxInch;
    }

    public function setLaptopMaxInch(?float $laptopMaxInch): self
    {
        $this->laptopMaxInch = $laptopMaxInch;

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

    public function getBrand(): Brand
    {
        return $this->brand;
    }

    public function setBrand(Brand $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addProduct($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->removeElement($category)) {
            $category->removeProduct($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(ProductVariant $variant): self
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setProduct($this);
        }

        return $this;
    }

    public function removeVariant(ProductVariant $variant): self
    {
        if ($this->variants->removeElement($variant)) {
            if ($variant->getProduct() === $this) {
                // alleen loskoppelen als jouw relation nullable is;
                // anders laat je dit weg
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}