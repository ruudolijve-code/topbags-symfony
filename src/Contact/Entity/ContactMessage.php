<?php

namespace App\Contact\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'contact_message')]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 190)]
    private string $email;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 20)]
    private string $status = 'new';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ---- Getters & Setters ----

    public function setName(string $name): void { $this->name = $name; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setPhone(?string $phone): void { $this->phone = $phone; }
    public function setSubject(?string $subject): void { $this->subject = $subject; }
    public function setMessage(string $message): void { $this->message = $message; }
    public function setSource(?string $source): void { $this->source = $source; }
    public function setIpAddress(?string $ip): void { $this->ipAddress = $ip; }
    public function setUserAgent(?string $ua): void { $this->userAgent = $ua; }
    public function setStatus(string $status): void { $this->status = $status; }
}