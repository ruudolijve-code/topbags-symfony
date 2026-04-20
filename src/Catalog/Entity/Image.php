<?php

namespace App\Catalog\Entity;

use App\Catalog\Service\VariantImagePathResolver;
use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[ORM\Table(name: 'image')]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Alleen bestandsnaam, bijvoorbeeld:
     * 1.jpg of main.webp
     */
    #[ORM\Column(length: 255)]
    private string $filename;

    /**
     * Sorteervolgorde binnen een variant (0 = eerste)
     */
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /**
     * Of dit de hoofdafbeelding is voor deze variant
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $isPrimary = false;

    /**
     * Elke afbeelding hoort altijd bij exact één productvariant
     */
    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProductVariant $productVariant;

    public function __construct()
    {
        $this->position = 0;
        $this->isPrimary = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = ltrim(trim($filename), '/');

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = max(0, $position);

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    public function getProductVariant(): ProductVariant
    {
        return $this->productVariant;
    }

    public function setProductVariant(ProductVariant $productVariant): self
    {
        $this->productVariant = $productVariant;

        if (!$productVariant->getImages()->contains($this)) {
            $productVariant->addImage($this);
        }

        return $this;
    }

    public function getPreviewPath(): ?string
    {
        $variant = $this->getProductVariant();
        $basePath = VariantImagePathResolver::fromSku($variant->getVariantSku());

        if ($basePath === null || $this->filename === '') {
            return null;
        }

        return $basePath . '/' . ltrim($this->filename, '/');
    }

    public function getPreviewUrl(): ?string
    {
        $path = $this->getPreviewPath();

        if ($path === null) {
            return null;
        }

        return '/' . ltrim($path, '/');
    }

    public function __toString(): string
    {
        return sprintf(
            '%s%s',
            $this->filename,
            $this->isPrimary ? ' (primair)' : ''
        );
    }
}