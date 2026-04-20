<?php

namespace App\Guide\Entity;

use App\Repository\TransportAdviceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransportAdviceRepository::class)]
class TransportAdvice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $transport; // car | bus | train | plane

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $advice;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $extraTip = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /* =============================
       Getters
    ============================= */

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAdvice(): string
    {
        return $this->advice;
    }

    public function getExtraTip(): ?string
    {
        return $this->extraTip;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}