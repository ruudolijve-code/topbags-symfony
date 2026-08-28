<?php

namespace App\Catalog\Entity;

use App\Catalog\Repository\ProductRepository;
use App\Catalog\Enum\ProductType;
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

    #[ORM\Column(options: ['default' => false])]
    private bool $isFeatured = false;

    #[ORM\Column(options: ['default' => 0])]
    private int $featuredPosition = 0;

    #[ORM\Column(nullable: true)]
    private ?int $qualityScoreOverride = null;

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

    #[ORM\Column(
        type: 'string',
        enumType: ProductType::class,
        options: ['default' => 'physical']
    )]
    private ProductType $productType = ProductType::PHYSICAL;

    /**
     * @var Collection<int, ProductContext>
     */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductContext::class,
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    private Collection $contexts;

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
        $this->contexts = new ArrayCollection();
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
        return $this->hasContext(self::CONTEXT_SHOP);
    }

    public function isBagsContext(): bool
    {
        return $this->hasContext(self::CONTEXT_BAGS);
    }

    /**
     * @return Collection<int, ProductContext>
     */
    public function getContexts(): Collection
    {
        return $this->contexts;
    }

    public function addContext(ProductContext $context): self
    {
        if (!$this->contexts->contains($context)) {
            $this->contexts->add($context);
            $context->setProduct($this);
        }

        return $this;
    }

    public function removeContext(ProductContext $context): self
    {
        if (
            $this->contexts->removeElement($context)
            && $context->getProduct() === $this
        ) {
            $context->setProduct(null);
        }

        return $this;
    }

    public function hasContext(string $context): bool
    {
        if (!in_array($context, self::ALLOWED_CONTEXTS, true)) {
            return false;
        }

        /*
        * Zodra er nieuwe contextrecords bestaan, zijn die leidend.
        */
        if (!$this->contexts->isEmpty()) {
            foreach ($this->contexts as $productContext) {
                if (
                    $productContext->isActive()
                    && $productContext->getContext() === $context
                ) {
                    return true;
                }
            }

            return false;
        }

        /*
        * Tijdelijke backwards compatibility zolang de bestaande
        * product_context-kolom nog niet verwijderd is.
        */
        return $this->productContext === $context;
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
        $this->series = $series !== null ? trim($series) : null;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = trim($slug);

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
        $this->warrantyYears = $warrantyYears !== null ? trim($warrantyYears) : null;

        return $this;
    }

    public function getLuggageType(): ?string
    {
        return $this->luggageType;
    }

    public function setLuggageType(?string $luggageType): self
    {
        $this->luggageType = $luggageType !== null ? trim($luggageType) : null;

        return $this;
    }

    public function getProductContext(): string
    {
        return $this->productContext;
    }

    public function setProductContext(string $productContext): self
    {
        if (!in_array($productContext, self::ALLOWED_CONTEXTS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Ongeldige productcontext "%s".',
                $productContext,
            ));
        }

        // Legacy kolom synchroon houden zolang deze nog bestaat.
        $this->productContext = $productContext;

        // Bestaande relationele context activeren.
        foreach ($this->contexts as $context) {
            if ($context->getContext() === $productContext) {
                $context->setIsActive(true);

                return $this;
            }
        }

        // Ontbrekende relationele context toevoegen.
        $context = (new ProductContext())
            ->setContext($productContext)
            ->setPosition(0)
            ->setIsActive(true);

        $this->addContext($context);

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
        $this->closureType = $closureType !== null ? trim($closureType) : null;

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

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): self
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function getQualityScoreOverride(): ?int
    {
        return $this->qualityScoreOverride;
    }

    public function setQualityScoreOverride(
        ?int $qualityScoreOverride,
    ): self {
        $this->qualityScoreOverride =
            $qualityScoreOverride === null
                ? null
                : max(0, min(100, $qualityScoreOverride));

        return $this;
    }

    public function getFeaturedPosition(): int
    {
        return $this->featuredPosition;
    }

    public function setFeaturedPosition(int $featuredPosition): self
    {
        $this->featuredPosition = max(0, $featuredPosition);

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

    public function getProductType(): ProductType
    {
        return $this->productType;
    }

    public function setProductType(ProductType $productType): self
    {
        $this->productType = $productType;

        return $this;
    }

    public function isService(): bool
    {
        return $this->productType === ProductType::SERVICE;
    }

    public function requiresShipping(): bool
    {
        return !$this->isService();
    }

    public function acceptsCoupons(): bool
    {
        return !$this->isService();
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
                // Alleen loskoppelen als jouw relation nullable is.
                // Anders bewust niets doen.
            }
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getContextValues(): array
    {
        if ($this->contexts->isEmpty()) {
            return [$this->productContext];
        }

        $values = [];

        foreach ($this->contexts as $productContext) {
            if (!$productContext->isActive()) {
                continue;
            }

            $value = $productContext->getContext();

            if (!in_array($value, $values, true)) {
                $values[] = $value;
            }
        }

        return $values;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}