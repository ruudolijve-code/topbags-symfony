<?php

namespace App\Guide\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Airline
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 100)]
    private string $iataCode;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $hint = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    // relations with tickets

    #[ORM\OneToMany(
        mappedBy: 'airline',
        targetEntity: AirlineTicketType::class,
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['priorityLevel' => 'ASC'])]
    private Collection $ticketTypes;

    // getters & setters

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getLogo(): string { return $this->logo; }
    public function setLogo(string $logo): self { $this->logo = $logo; return $this; }

    public function getHint(): ?string { return $this->hint; }
    public function setHint(?string $hint): self { $this->hint = $hint; return $this; }

    public function getIataCode(): string { return $this->iataCode; }
    public function setIataCode(string $iataCode): self { $this->iataCode = $iataCode; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    /**
     * @return Collection<int, AirlineTicketType>
     */
    public function getTicketTypes(): Collection { return $this->ticketTypes; }
}