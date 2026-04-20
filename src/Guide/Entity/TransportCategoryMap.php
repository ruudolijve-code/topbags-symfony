<?php

namespace App\Guide\Entity;

use App\Catalog\Entity\Category;
use App\Repository\TransportCategoryMapRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransportCategoryMapRepository::class)]
#[ORM\Table(name: 'transport_category_map')]
class TransportCategoryMap
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $transport;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $baggageType = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(type: 'integer')]
    private int $priority = 100;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    /* =====================
       Getters
    ===================== */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function getBaggageType(): ?string
    {
        return $this->baggageType;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}