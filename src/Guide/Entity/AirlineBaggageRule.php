<?php

namespace App\Guide\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AirlineBaggageRule
{
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
    private string $ruleScope; 
    // personal_item | cabin | hold

    #[ORM\Column(length: 20)]
    private string $dimensionType; 
    // box | linear_sum

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

    /* Getters / setters – bewust geen business logic hier */
    public function isBox(): bool
    {
        return $this->dimensionType === 'box';
    }

    public function isLinearSum(): bool
    {
        return $this->dimensionType === 'linear_sum';
    }

    public function isCabin(): bool
    {
        return $this->ruleScope === 'cabin';
    }

    public function isHold(): bool
    {
        return $this->ruleScope === 'hold';
    }

    public function isPersonal(): bool
    {
        return $this->ruleScope === 'personal';
    }

    // ========================
// Pure getters
// ========================

public function getId(): ?int
{
    return $this->id;
}

public function getAirline(): Airline
{
    return $this->airline;
}

public function getTicketType(): AirlineTicketType
{
    return $this->ticketType;
}

public function getRuleScope(): string
{
    return $this->ruleScope;
}

public function getDimensionType(): string
{
    return $this->dimensionType;
}

public function getQuantityCabin(): ?int { return $this->quantityCabin; }

public function getMaxHeightCm(): ?int
{
    return $this->maxHeightCm;
}

public function getMaxWidthCm(): ?int
{
    return $this->maxWidthCm;
}

public function getMaxDepthCm(): ?int
{
    return $this->maxDepthCm;
}

public function getMaxLinearCm(): ?int
{
    return $this->maxLinearCm;
}

public function getMaxWeightKg(): ?float
{
    return $this->maxWeightKg;
}

public function isActive(): bool
{
    return $this->isActive;
}
}