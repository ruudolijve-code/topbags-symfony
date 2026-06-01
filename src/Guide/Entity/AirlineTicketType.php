<?php

declare(strict_types=1);

namespace App\Guide\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AirlineTicketType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(
        targetEntity: Airline::class,
        inversedBy: 'ticketTypes'
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Airline $airline;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $priorityLevel = 0;

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

    public function getPriorityLevel(): int
    {
        return $this->priorityLevel;
    }

    public function setPriorityLevel(int $priorityLevel): self
    {
        $this->priorityLevel = $priorityLevel;

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

    public function __toString(): string
    {
        return sprintf('%s – %s', $this->airline->getName(), $this->name);
    }
}