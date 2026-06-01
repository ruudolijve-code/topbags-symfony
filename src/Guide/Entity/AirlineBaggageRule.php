<?php

declare(strict_types=1);

namespace App\Guide\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AirlineBaggageRule
{
    public const SCOPE_PERSONAL = 'personal';
    public const SCOPE_CABIN = 'cabin';
    public const SCOPE_HOLD = 'hold';

    public const DIMENSION_BOX = 'box';
    public const DIMENSION_LINEAR_SUM = 'linear_sum';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Airline $airline;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private AirlineTicketType $ticketType;

    #[ORM\Column(length: 20)]
    private string $ruleScope = self::SCOPE_CABIN;

    #[ORM\Column(length: 20)]
    private string $dimensionType = self::DIMENSION_BOX;

    #[ORM\Column(nullable: true)]
    private ?int $quantityCabin = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxHeightCm = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxWidthCm = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxDepthCm = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxLinearCm = null;

    #[ORM\Column(nullable: true)]
    private ?float $maxWeightKg = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAirline(): Airline
    {
        return $this->airline;
    }

    public function setAirline(Airline $airline): self
    {
        $this->airline = $airline;

        return $this;
    }

    public function getTicketType(): AirlineTicketType
    {
        return $this->ticketType;
    }

    public function setTicketType(AirlineTicketType $ticketType): self
    {
        $this->ticketType = $ticketType;

        return $this;
    }

    public function getRuleScope(): string
    {
        return $this->ruleScope;
    }

    public function setRuleScope(string $ruleScope): self
    {
        $this->ruleScope = $ruleScope;

        return $this;
    }

    public function getDimensionType(): string
    {
        return $this->dimensionType;
    }

    public function setDimensionType(string $dimensionType): self
    {
        $this->dimensionType = $dimensionType;

        return $this;
    }

    public function getQuantityCabin(): ?int
    {
        return $this->quantityCabin;
    }

    public function setQuantityCabin(?int $quantityCabin): self
    {
        $this->quantityCabin = $quantityCabin;

        return $this;
    }

    public function getMaxHeightCm(): ?int
    {
        return $this->maxHeightCm;
    }

    public function setMaxHeightCm(?int $maxHeightCm): self
    {
        $this->maxHeightCm = $maxHeightCm;

        return $this;
    }

    public function getMaxWidthCm(): ?int
    {
        return $this->maxWidthCm;
    }

    public function setMaxWidthCm(?int $maxWidthCm): self
    {
        $this->maxWidthCm = $maxWidthCm;

        return $this;
    }

    public function getMaxDepthCm(): ?int
    {
        return $this->maxDepthCm;
    }

    public function setMaxDepthCm(?int $maxDepthCm): self
    {
        $this->maxDepthCm = $maxDepthCm;

        return $this;
    }

    public function getMaxLinearCm(): ?int
    {
        return $this->maxLinearCm;
    }

    public function setMaxLinearCm(?int $maxLinearCm): self
    {
        $this->maxLinearCm = $maxLinearCm;

        return $this;
    }

    public function getMaxWeightKg(): ?float
    {
        return $this->maxWeightKg;
    }

    public function setMaxWeightKg(?float $maxWeightKg): self
    {
        $this->maxWeightKg = $maxWeightKg;

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

    public function isBox(): bool
    {
        return $this->dimensionType === self::DIMENSION_BOX;
    }

    public function isLinearSum(): bool
    {
        return $this->dimensionType === self::DIMENSION_LINEAR_SUM;
    }

    public function isCabin(): bool
    {
        return $this->ruleScope === self::SCOPE_CABIN;
    }

    public function isHold(): bool
    {
        return $this->ruleScope === self::SCOPE_HOLD;
    }

    public function isPersonal(): bool
    {
        return $this->ruleScope === self::SCOPE_PERSONAL;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s – %s – %s',
            $this->airline->getName(),
            $this->ticketType->getName(),
            $this->ruleScope
        );
    }
}