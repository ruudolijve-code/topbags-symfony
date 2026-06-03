<?php

declare(strict_types=1);

namespace App\Marketing\Entity;

use App\Marketing\Repository\NewsletterSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterSubscriptionRepository::class)]
#[ORM\Table(name: 'newsletter_subscription')]
#[ORM\UniqueConstraint(name: 'uniq_newsletter_subscription_email', columns: ['email'])]
class NewsletterSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $source = 'topbags_webshop';

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $unsubscribeToken = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $unsubscribedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->ensureUnsubscribeToken();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        if ($isActive) {
            $this->unsubscribedAt = null;
            $this->ensureUnsubscribeToken();
        } elseif ($this->unsubscribedAt === null) {
            $this->unsubscribedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source !== null ? trim($source) : null;

        return $this;
    }

    public function getUnsubscribeToken(): ?string
    {
        return $this->unsubscribeToken;
    }

    public function setUnsubscribeToken(?string $unsubscribeToken): self
    {
        $this->unsubscribeToken = $unsubscribeToken;

        return $this;
    }

    public function ensureUnsubscribeToken(): self
    {
        if ($this->unsubscribeToken === null || $this->unsubscribeToken === '') {
            $this->unsubscribeToken = bin2hex(random_bytes(32));
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUnsubscribedAt(): ?\DateTimeImmutable
    {
        return $this->unsubscribedAt;
    }

    public function setUnsubscribedAt(?\DateTimeImmutable $unsubscribedAt): self
    {
        $this->unsubscribedAt = $unsubscribedAt;

        return $this;
    }

    public function unsubscribe(): self
    {
        $this->isActive = false;
        $this->unsubscribedAt = new \DateTimeImmutable();

        return $this;
    }

    public function resubscribe(): self
    {
        $this->isActive = true;
        $this->unsubscribedAt = null;
        $this->ensureUnsubscribeToken();

        return $this;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}