<?php

namespace App\Catalog\Entity;

use App\Catalog\Repository\ProductVariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\Entity(repositoryClass: ProductVariantRepository::class)]
#[ORM\Table(name: 'product_variant')]
class ProductVariant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $variantSku;

    #[ORM\Column(length: 20)]
    private string $ean;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price = '0.00';

    #[ORM\Column(options: ['default' => false])]
    private bool $isMaster = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(length: 100)]
    private string $supplierColorName;

    #[ORM\Column(length: 120)]
    private string $supplierColorSlug;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $supplierColorCode = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $allowBackorder = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $compareAtPrice = null;

    #[ORM\Column(nullable: true)]
    private ?int $salePercentage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $saleStartsAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $saleEndsAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $saleLabel = null;

    #[ORM\ManyToOne(inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\ManyToOne(inversedBy: 'variants')]
    private ?Color $color = null;

    #[ORM\OneToMany(
        mappedBy: 'productVariant',
        targetEntity: Image::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $images;

    #[ORM\OneToOne(
        mappedBy: 'productVariant',
        targetEntity: Stock::class,
        cascade: ['persist', 'remove']
    )]
    private ?Stock $stock = null;

    #[ORM\ManyToOne(inversedBy: 'productVariants')]
    private ?Color $normalizedColor = null;

    #[ORM\OneToMany(mappedBy: 'variant', targetEntity: VariantSupply::class)]
    private Collection $supplies;

    /**
     * Alleen voor EasyAdmin upload, niet opslaan in database.
     *
     * @var array<int, UploadedFile>|null
     */
    private ?array $uploadedImages = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->supplies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVariantSku(): string
    {
        return $this->variantSku;
    }

    public function setVariantSku(string $variantSku): self
    {
        $this->variantSku = trim($variantSku);

        return $this;
    }

    public function getEan(): string
    {
        return $this->ean;
    }

    public function setEan(string $ean): self
    {
        $this->ean = trim($ean);

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string|float|int $price): self
    {
        $this->price = number_format((float) $price, 2, '.', '');

        return $this;
    }

    public function isMaster(): bool
    {
        return $this->isMaster;
    }

    public function setIsMaster(bool $isMaster): self
    {
        $this->isMaster = $isMaster;

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

    public function getSupplierColorName(): string
    {
        return $this->supplierColorName;
    }

    public function setSupplierColorName(string $name): self
    {
        $this->supplierColorName = trim($name);

        return $this;
    }

    public function getSupplierColorSlug(): string
    {
        return $this->supplierColorSlug;
    }

    public function setSupplierColorSlug(string $supplierColorSlug): self
    {
        $this->supplierColorSlug = trim($supplierColorSlug);

        return $this;
    }

    public function getSupplierColorCode(): ?string
    {
        return $this->supplierColorCode;
    }

    public function setSupplierColorCode(?string $supplierColorCode): self
    {
        $this->supplierColorCode = $supplierColorCode !== null
            ? trim($supplierColorCode)
            : null;

        return $this;
    }

    public function isAllowBackorder(): bool
    {
        return $this->allowBackorder;
    }

    public function allowsBackorder(): bool
    {
        return $this->allowBackorder;
    }

    public function setAllowBackorder(bool $allowBackorder): self
    {
        $this->allowBackorder = $allowBackorder;

        return $this;
    }

    public function getCompareAtPrice(): ?string
    {
        return $this->compareAtPrice;
    }

    public function setCompareAtPrice(string|float|int|null $compareAtPrice): self
    {
        $this->compareAtPrice = $compareAtPrice !== null
            ? number_format((float) $compareAtPrice, 2, '.', '')
            : null;

        return $this;
    }

    public function getSalePercentage(): ?int
    {
        return $this->salePercentage;
    }

    public function setSalePercentage(?int $salePercentage): self
    {
        if ($salePercentage !== null) {
            $salePercentage = max(0, min(90, $salePercentage));
        }

        $this->salePercentage = $salePercentage;

        return $this;
    }

    public function getSaleStartsAt(): ?\DateTimeImmutable
    {
        return $this->saleStartsAt;
    }

    public function setSaleStartsAt(?\DateTimeImmutable $saleStartsAt): self
    {
        $this->saleStartsAt = $saleStartsAt;

        return $this;
    }

    public function getSaleEndsAt(): ?\DateTimeImmutable
    {
        return $this->saleEndsAt;
    }

    public function setSaleEndsAt(?\DateTimeImmutable $saleEndsAt): self
    {
        $this->saleEndsAt = $saleEndsAt;

        return $this;
    }

    public function getSaleLabel(): ?string
    {
        return $this->saleLabel;
    }

    public function setSaleLabel(?string $saleLabel): self
    {
        $this->saleLabel = $saleLabel !== null
            ? trim($saleLabel)
            : null;

        return $this;
    }

    public function isSaleActive(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        if ($this->salePercentage === null || $this->salePercentage <= 0) {
            return false;
        }

        if ($this->saleStartsAt !== null && $now < $this->saleStartsAt) {
            return false;
        }

        if ($this->saleEndsAt !== null && $now > $this->saleEndsAt) {
            return false;
        }

        return true;
    }

    public function hasActiveSale(): bool
    {
        return $this->isSaleActive();
    }

    public function getSalePrice(): string
    {
        if (!$this->isSaleActive()) {
            return $this->price;
        }

        $basePrice = (float) $this->price;
        $percentage = (int) $this->salePercentage;
        $salePrice = $basePrice * (1 - ($percentage / 100));

        return number_format(max(0, $salePrice), 2, '.', '');
    }

    public function getDisplayPrice(): string
    {
        return $this->isSaleActive()
            ? $this->getSalePrice()
            : $this->price;
    }

    public function getDiscountBadge(): ?string
    {
        if (!$this->isSaleActive()) {
            return null;
        }

        if ($this->saleLabel !== null && $this->saleLabel !== '') {
            return $this->saleLabel;
        }

        if ($this->salePercentage !== null) {
            return '-' . $this->salePercentage . '%';
        }

        return null;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getNormalizedColor(): ?Color
    {
        return $this->normalizedColor;
    }

    public function setNormalizedColor(?Color $normalizedColor): self
    {
        $this->normalizedColor = $normalizedColor;

        return $this;
    }

    /**
     * @return Collection<int, VariantSupply>
     */
    public function getSupplies(): Collection
    {
        return $this->supplies;
    }

    public function addSupply(VariantSupply $supply): self
    {
        if (!$this->supplies->contains($supply)) {
            $this->supplies->add($supply);
            $supply->setVariant($this);
        }

        return $this;
    }

    public function removeSupply(VariantSupply $supply): self
    {
        if ($this->supplies->removeElement($supply) && $supply->getVariant() === $this) {
            $supply->setVariant(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProductVariant($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        $this->images->removeElement($image);

        return $this;
    }

    public function getPrimaryImage(): ?Image
    {
        foreach ($this->images as $image) {
            if ($image->isPrimary()) {
                return $image;
            }
        }

        foreach ($this->images as $image) {
            return $image;
        }

        return null;
    }

    public function getPrimaryImagePath(): ?string
    {
        return $this->getPrimaryImage()?->getFilename();
    }

    public function getPrimaryImageFilename(): ?string
    {
        return $this->getPrimaryImage()?->getFilename();
    }

    public function hasImages(): bool
    {
        return !$this->images->isEmpty();
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;

        if ($stock !== null && $stock->getProductVariant() !== $this) {
            $stock->setProductVariant($this);
        }

        return $this;
    }

    public function getOrCreateStock(): Stock
    {
        if ($this->stock === null) {
            $stock = new Stock();
            $stock->setProductVariant($this);
            $this->stock = $stock;
        }

        return $this->stock;
    }

    public function getStockOnHand(): int
    {
        return $this->stock?->getOnHand() ?? 0;
    }

    public function setStockOnHand(int $onHand): self
    {
        $this->getOrCreateStock()->setOnHand($onHand);

        return $this;
    }

    public function getStockReserved(): int
    {
        return $this->stock?->getReserved() ?? 0;
    }

    public function setStockReserved(int $reserved): self
    {
        $this->getOrCreateStock()->setReserved($reserved);

        return $this;
    }

    public function getStockAvailable(): int
    {
        return $this->stock?->getAvailable() ?? 0;
    }

    public function getAvailableStock(): int
    {
        return $this->getStockAvailable();
    }

    public function isInStock(): bool
    {
        return $this->getAvailableStock() > 0;
    }

    public function isPurchasable(): bool
    {
        return $this->isActive() && ($this->isInStock() || $this->allowsBackorder());
    }

    public function hasBackorderOption(): bool
    {
        return $this->allowsBackorder();
    }

    public function getPreferredBackorderSupply(): ?VariantSupply
    {
        foreach ($this->supplies as $supply) {
            if (!$supply->isActive()) {
                continue;
            }

            if (!$supply->isPurchasable()) {
                continue;
            }

            return $supply;
        }

        return null;
    }

    public function getBackorderSupplier(): ?Supplier
    {
        $supply = $this->getPreferredBackorderSupply();

        if ($supply?->getSupplier() !== null) {
            return $supply->getSupplier();
        }

        return $this->product->getBrand()?->getDefaultSupplier();
    }

    public function getBackorderLeadTimeMinDays(): ?int
    {
        $supply = $this->getPreferredBackorderSupply();

        if ($supply?->getLeadTimeMin() !== null) {
            return $supply->getLeadTimeMin();
        }

        return $this->getBackorderSupplier()?->getDefaultLeadTimeMin();
    }

    public function getBackorderLeadTimeMaxDays(): ?int
    {
        $supply = $this->getPreferredBackorderSupply();

        if ($supply?->getLeadTimeMax() !== null) {
            return $supply->getLeadTimeMax();
        }

        return $this->getBackorderSupplier()?->getDefaultLeadTimeMax();
    }

    public function getDeliveryLabel(): string
    {
        if ($this->isInStock()) {
            return 'Zelfde dag verzonden';
        }

        if ($this->allowsBackorder()) {
            $min = $this->getBackorderLeadTimeMinDays();
            $max = $this->getBackorderLeadTimeMaxDays();

            if ($min !== null && $max !== null) {
                return sprintf('%d-%d werkdagen', $min, $max);
            }

            if ($min !== null) {
                return sprintf('ca. %d werkdagen', $min);
            }

            return 'Leverbaar via leverancier';
        }

        return 'Niet op voorraad';
    }

    /**
     * @return array<int, UploadedFile>|null
     */
    public function getUploadedImages(): ?array
    {
        return $this->uploadedImages;
    }

    /**
     * @param array<int, UploadedFile>|null $uploadedImages
     */
    public function setUploadedImages(?array $uploadedImages): self
    {
        $this->uploadedImages = $uploadedImages;

        return $this;
    }

    /**
     * @return array<int, array{
     *     id: int|null,
     *     filename: string,
     *     previewUrl: string|null,
     *     isPrimary: bool,
     *     position: int
     * }>
     */
    public function getImagesPreview(): array
    {
        $items = [];

        foreach ($this->images as $image) {
            $items[] = [
                'id' => $image->getId(),
                'filename' => $image->getFilename(),
                'previewUrl' => $image->getPreviewUrl(),
                'isPrimary' => $image->isPrimary(),
                'position' => $image->getPosition(),
            ];
        }

        usort(
            $items,
            static fn (array $a, array $b): int => $a['position'] <=> $b['position']
        );

        return $items;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (%s)',
            $this->variantSku,
            $this->supplierColorName ?: 'variant'
        );
    }
}