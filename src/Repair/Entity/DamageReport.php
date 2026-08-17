<?php

declare(strict_types=1);

namespace App\Repair\Entity;

use App\Repair\Repository\DamageReportRepository;
use App\Shop\Entity\Order;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DamageReportRepository::class)]
#[ORM\Table(name: 'damage_report')]
#[ORM\HasLifecycleCallbacks]
class DamageReport
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINAL = 'final';

    public const STATUS_CHOICES = [
        'Concept' => self::STATUS_DRAFT,
        'Definitief' => self::STATUS_FINAL,
    ];

    public const ASSESSMENT_REPAIRABLE = 'repairable';
    public const ASSESSMENT_UNECONOMIC = 'uneconomic';
    public const ASSESSMENT_TOTAL_LOSS = 'total_loss';

    public const ASSESSMENT_CHOICES = [
        'Repareerbaar' => self::ASSESSMENT_REPAIRABLE,
        'Reparatie economisch niet verantwoord' => self::ASSESSMENT_UNECONOMIC,
        'Total loss' => self::ASSESSMENT_TOTAL_LOSS,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $reportNumber = '';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $reportDate;

    /*
     * Klantgegevens
     */

    #[ORM\Column(length: 255)]
    private string $customerName = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $customerPostalCode = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $customerCity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $customerPhone = null;

    /*
     * Bagage
     */

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $series = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dimensions = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $estimatedPurchaseDate = null;

    #[ORM\Column(nullable: true)]
    private ?float $estimatedPurchasePrice = null;

    /*
     * Reis / luchtvaart
     */

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $airline = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $flightNumber = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $flightDate = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $airport = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pirNumber = null;

    /*
     * Schade
     */

    #[ORM\Column(type: Types::TEXT)]
    private string $damageDescription = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $assessment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $technicalAssessment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conclusion = null;

    #[ORM\Column(nullable: true)]
    private ?float $repairCosts = null;

    #[ORM\Column(nullable: true)]
    private ?float $replacementValue = null;

    /*
     * Interne notitie komt niet op PDF.
     */

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $internalNotes = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $assessorName = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /*
     * Order / opdracht
     */

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Order $order = null;

    /*
     * Schadefoto's
     */

    /**
     * @var Collection<int, DamageReportImage>
     */
    #[ORM\OneToMany(
        mappedBy: 'damageReport',
        targetEntity: DamageReportImage::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy([
        'position' => 'ASC',
        'id' => 'ASC',
    ])]
    #[Assert\Count(
        max: 4,
        maxMessage: 'Je kunt maximaal {{ limit }} schadefoto’s toevoegen.'
    )]
    private Collection $images;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->reportDate = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->images = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        if ($this->reportNumber !== '') {
            return sprintf(
                '%s – %s',
                $this->reportNumber,
                $this->customerName
            );
        }

        return $this->customerName !== ''
            ? sprintf('Nieuw rapport – %s', $this->customerName)
            : 'Nieuw schaderapport';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReportNumber(): string
    {
        return $this->reportNumber;
    }

    public function setReportNumber(string $reportNumber): self
    {
        $this->reportNumber = trim($reportNumber);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, array_values(self::STATUS_CHOICES), true)) {
            throw new \InvalidArgumentException('Ongeldige status.');
        }

        $this->status = $status;

        return $this;
    }

    public function getReportDate(): \DateTimeImmutable
    {
        return $this->reportDate;
    }

    public function setReportDate(\DateTimeImmutable $reportDate): self
    {
        $this->reportDate = $reportDate;

        return $this;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): self
    {
        $this->customerName = trim($customerName);

        return $this;
    }

    public function getCustomerAddress(): ?string
    {
        return $this->customerAddress;
    }

    public function setCustomerAddress(?string $customerAddress): self
    {
        $this->customerAddress = $customerAddress !== null
            ? trim($customerAddress)
            : null;

        return $this;
    }

    public function getCustomerPostalCode(): ?string
    {
        return $this->customerPostalCode;
    }

    public function setCustomerPostalCode(?string $customerPostalCode): self
    {
        $this->customerPostalCode = $customerPostalCode !== null
            ? strtoupper(trim($customerPostalCode))
            : null;

        return $this;
    }

    public function getCustomerCity(): ?string
    {
        return $this->customerCity;
    }

    public function setCustomerCity(?string $customerCity): self
    {
        $this->customerCity = $customerCity !== null
            ? trim($customerCity)
            : null;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): self
    {
        $this->customerEmail = $customerEmail !== null
            ? mb_strtolower(trim($customerEmail))
            : null;

        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $customerPhone): self
    {
        $this->customerPhone = $customerPhone !== null
            ? trim($customerPhone)
            : null;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): self
    {
        $this->brand = $brand !== null ? trim($brand) : null;

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

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model !== null ? trim($model) : null;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color !== null ? trim($color) : null;

        return $this;
    }

    public function getDimensions(): ?string
    {
        return $this->dimensions;
    }

    public function setDimensions(?string $dimensions): self
    {
        $this->dimensions = $dimensions !== null
            ? trim($dimensions)
            : null;

        return $this;
    }

    public function getEstimatedPurchaseDate(): ?string
    {
        return $this->estimatedPurchaseDate;
    }

    public function setEstimatedPurchaseDate(?string $estimatedPurchaseDate): self
    {
        $this->estimatedPurchaseDate = $estimatedPurchaseDate !== null
            ? trim($estimatedPurchaseDate)
            : null;

        return $this;
    }

    public function getEstimatedPurchasePrice(): ?float
    {
        return $this->estimatedPurchasePrice;
    }

    public function setEstimatedPurchasePrice(?float $estimatedPurchasePrice): self
    {
        $this->estimatedPurchasePrice = $estimatedPurchasePrice;

        return $this;
    }

    public function getAirline(): ?string
    {
        return $this->airline;
    }

    public function setAirline(?string $airline): self
    {
        $this->airline = $airline !== null ? trim($airline) : null;

        return $this;
    }

    public function getFlightNumber(): ?string
    {
        return $this->flightNumber;
    }

    public function setFlightNumber(?string $flightNumber): self
    {
        $this->flightNumber = $flightNumber !== null
            ? trim($flightNumber)
            : null;

        return $this;
    }

    public function getFlightDate(): ?\DateTimeImmutable
    {
        return $this->flightDate;
    }

    public function setFlightDate(?\DateTimeImmutable $flightDate): self
    {
        $this->flightDate = $flightDate;

        return $this;
    }

    public function getAirport(): ?string
    {
        return $this->airport;
    }

    public function setAirport(?string $airport): self
    {
        $this->airport = $airport !== null ? trim($airport) : null;

        return $this;
    }

    public function getPirNumber(): ?string
    {
        return $this->pirNumber;
    }

    public function setPirNumber(?string $pirNumber): self
    {
        $this->pirNumber = $pirNumber !== null
            ? trim($pirNumber)
            : null;

        return $this;
    }

    public function getDamageDescription(): string
    {
        return $this->damageDescription;
    }

    public function setDamageDescription(string $damageDescription): self
    {
        $this->damageDescription = trim($damageDescription);

        return $this;
    }

    public function getAssessment(): ?string
    {
        return $this->assessment;
    }

    public function setAssessment(?string $assessment): self
    {
        if (
            $assessment !== null
            && !in_array($assessment, array_values(self::ASSESSMENT_CHOICES), true)
        ) {
            throw new \InvalidArgumentException('Ongeldige beoordeling.');
        }

        $this->assessment = $assessment;

        return $this;
    }

    public function getTechnicalAssessment(): ?string
    {
        return $this->technicalAssessment;
    }

    public function setTechnicalAssessment(?string $technicalAssessment): self
    {
        $this->technicalAssessment = $technicalAssessment !== null
            ? trim($technicalAssessment)
            : null;

        return $this;
    }

    public function getConclusion(): ?string
    {
        return $this->conclusion;
    }

    public function setConclusion(?string $conclusion): self
    {
        $this->conclusion = $conclusion !== null
            ? trim($conclusion)
            : null;

        return $this;
    }

    public function getRepairCosts(): ?float
    {
        return $this->repairCosts;
    }

    public function setRepairCosts(?float $repairCosts): self
    {
        $this->repairCosts = $repairCosts;

        return $this;
    }

    public function getReplacementValue(): ?float
    {
        return $this->replacementValue;
    }

    public function setReplacementValue(?float $replacementValue): self
    {
        $this->replacementValue = $replacementValue;

        return $this;
    }

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): self
    {
        $this->internalNotes = $internalNotes !== null
            ? trim($internalNotes)
            : null;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getAssessorName(): ?string
    {
        return $this->assessorName;
    }

    public function setAssessorName(?string $assessorName): self
    {
        $this->assessorName = $assessorName !== null
            ? trim($assessorName)
            : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, DamageReportImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(DamageReportImage $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setDamageReport($this);
        }

        return $this;
    }

    public function removeImage(DamageReportImage $image): self
    {
        if (
            $this->images->removeElement($image)
            && $image->getDamageReport() === $this
        ) {
            $image->setDamageReport(null);
        }

        return $this;
    }
}