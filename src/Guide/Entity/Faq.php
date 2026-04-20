<?php

namespace App\Guide\Entity;

use App\Guide\Repository\FaqRepository;
use App\Guide\Entity\Airline;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaqRepository::class)]
class Faq
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $transportType;

    #[ORM\ManyToOne(targetEntity: Airline::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Airline $airline = null;

    #[ORM\Column(type: 'text')]
    private string $question;

    #[ORM\Column(type: 'text')]
    private string $answer;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters / Setters hieronder genereren via Symfony maker of IDE
    public function getQuestion(): string { return $this->question; }

    public function getAnswer(): string { return $this->answer; }
}