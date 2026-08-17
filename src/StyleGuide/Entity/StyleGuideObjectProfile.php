<?php

declare(strict_types=1);

namespace App\StyleGuide\Entity;

use App\StyleGuide\Repository\StyleGuideObjectProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StyleGuideObjectProfileRepository::class)]
#[ORM\Table(name: 'style_guide_object_profile')]
class StyleGuideObjectProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\Column(nullable: true)]
    private ?float $widthCm = null;

    #[ORM\Column(nullable: true)]
    private ?float $heightCm = null;

    #[ORM\Column(nullable: true)]
    private ?float $depthCm = null;

    #[ORM\Column(nullable: true)]
    private ?float $volumeL = null;

    #[ORM\Column(length: 30)]
    private string $shapeType = 'regular';

    #[ORM\Column(length: 30)]
    private string $orientation = 'any';

    #[ORM\Column(options: ['default' => false])]
    private bool $requiresLaptopCompartment = false;

    #[ORM\Column(nullable: true)]
    private ?float $requiredLaptopInch = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $requiresA4Fit = false;

    #[ORM\Column(nullable: true)]
    private ?float $widthMarginCm = null;

    #[ORM\Column(nullable: true)]
    private ?float $heightMarginCm = null;

    #[ORM\Column(nullable: true)]
    private ?float $depthMarginCm = null;

    #[ORM\Column]
    private float $bulkFactor = 1.0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    public function __toString(): string
    {
        return $this->name !== ''
            ? $this->name
            : sprintf('Objectprofiel #%s', $this->id ?? 'nieuw');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = trim($code);

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

    public function getWidthCm(): ?float
    {
        return $this->widthCm;
    }

    public function setWidthCm(?float $widthCm): self
    {
        $this->widthCm = $widthCm;

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

    public function getDepthCm(): ?float
    {
        return $this->depthCm;
    }

    public function setDepthCm(?float $depthCm): self
    {
        $this->depthCm = $depthCm;

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

    public function getShapeType(): string
    {
        return $this->shapeType;
    }

    public function setShapeType(string $shapeType): self
    {
        $this->shapeType = trim($shapeType);

        return $this;
    }

    public function getOrientation(): string
    {
        return $this->orientation;
    }

    public function setOrientation(string $orientation): self
    {
        $this->orientation = trim($orientation);

        return $this;
    }

    public function requiresLaptopCompartment(): bool
    {
        return $this->requiresLaptopCompartment;
    }

    public function getRequiresLaptopCompartment(): bool
    {
        return $this->requiresLaptopCompartment;
    }

    public function setRequiresLaptopCompartment(
        bool $requiresLaptopCompartment,
    ): self {
        $this->requiresLaptopCompartment = $requiresLaptopCompartment;

        return $this;
    }

    public function getRequiredLaptopInch(): ?float
    {
        return $this->requiredLaptopInch;
    }

    public function setRequiredLaptopInch(
        ?float $requiredLaptopInch,
    ): self {
        $this->requiredLaptopInch = $requiredLaptopInch;

        return $this;
    }

    public function requiresA4Fit(): bool
    {
        return $this->requiresA4Fit;
    }

    public function getRequiresA4Fit(): bool
    {
        return $this->requiresA4Fit;
    }

    public function setRequiresA4Fit(bool $requiresA4Fit): self
    {
        $this->requiresA4Fit = $requiresA4Fit;

        return $this;
    }

    public function getWidthMarginCm(): ?float
    {
        return $this->widthMarginCm;
    }

    public function setWidthMarginCm(?float $widthMarginCm): self
    {
        $this->widthMarginCm = $widthMarginCm;

        return $this;
    }

    public function getHeightMarginCm(): ?float
    {
        return $this->heightMarginCm;
    }

    public function setHeightMarginCm(?float $heightMarginCm): self
    {
        $this->heightMarginCm = $heightMarginCm;

        return $this;
    }

    public function getDepthMarginCm(): ?float
    {
        return $this->depthMarginCm;
    }

    public function setDepthMarginCm(?float $depthMarginCm): self
    {
        $this->depthMarginCm = $depthMarginCm;

        return $this;
    }

    public function getBulkFactor(): float
    {
        return $this->bulkFactor;
    }

    public function setBulkFactor(float $bulkFactor): self
    {
        $this->bulkFactor = max(0.1, $bulkFactor);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }
}