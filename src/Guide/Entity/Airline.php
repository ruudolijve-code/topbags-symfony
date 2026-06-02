<?php

declare(strict_types=1);

namespace App\Guide\Entity;

use App\Guide\Repository\AirlineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AirlineRepository::class)]
class Airline
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 100)]
    private string $iataCode = '';

    #[ORM\Column(length: 100, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $hint = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /**
     * @var Collection<int, AirlineTicketType>
     */
    #[ORM\OneToMany(
        mappedBy: 'airline',
        targetEntity: AirlineTicketType::class,
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['priorityLevel' => 'ASC'])]
    private Collection $ticketTypes;

    public function __construct()
    {
        $this->ticketTypes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name !== '' ? $this->name : 'Vliegmaatschappij';
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
        $this->name = trim($name);

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo !== null && trim($logo) !== '' ? trim($logo) : null;

        return $this;
    }

    public function getIataCode(): string
    {
        return $this->iataCode;
    }

    public function setIataCode(string $iataCode): self
    {
        $this->iataCode = strtoupper(trim($iataCode));

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

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function setHint(?string $hint): self
    {
        $this->hint = $hint !== null && trim($hint) !== '' ? trim($hint) : null;

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

    /**
     * @return Collection<int, AirlineTicketType>
     */
    public function getTicketTypes(): Collection
    {
        return $this->ticketTypes;
    }

    public function addTicketType(AirlineTicketType $ticketType): self
    {
        if (!$this->ticketTypes->contains($ticketType)) {
            $this->ticketTypes->add($ticketType);
            $ticketType->setAirline($this);
        }

        return $this;
    }

    public function removeTicketType(AirlineTicketType $ticketType): self
    {
        if ($this->ticketTypes->removeElement($ticketType)) {
            if ($ticketType->getAirline() === $this) {
                $ticketType->setAirline(null);
            }
        }

        return $this;
    }
}