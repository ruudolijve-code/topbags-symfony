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
    public const SOURCE_TOPBAGS_WEBSHOP = 'topbags_webshop';
    public const SOURCE_HOLTKAMP_STORE = 'holtkamp_store';
    public const SOURCE_ADMIN_MANUAL = 'admin_manual';
    public const SOURCE_TRAVELMILES_MEMBER = 'travelmiles_member';
    public const SOURCE_CUSTOMER_ORDER = 'customer_order';

    public const SOURCES = [
        self::SOURCE_TOPBAGS_WEBSHOP,
        self::SOURCE_HOLTKAMP_STORE,
        self::SOURCE_ADMIN_MANUAL,
        self::SOURCE_TRAVELMILES_MEMBER,
        self::SOURCE_CUSTOMER_ORDER,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $source = self::SOURCE_TOPBAGS_WEBSHOP;

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

    /* ======================
       EMAIL
    ====================== */

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $email = mb_strtolower(trim($email));

        if ($email === '') {
            throw new \InvalidArgumentException('Email cannot be empty.');
        }

        $this->email = $email;

        return $this;
    }

    /* ======================
       ACTIVE / UNSUBSCRIBE
    ====================== */

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

    /* ======================
       SOURCE
    ====================== */

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        if ($source === null || trim($source) === '') {
            $this->source = null;

            return $this;
        }

        $source = trim($source);

        if (!in_array($source, self::SOURCES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid newsletter source "%s".', $source));
        }

        $this->source = $source;

        return $this;
    }

    public function isFromTopbagsWebshop(): bool
    {
        return $this->source === self::SOURCE_TOPBAGS_WEBSHOP;
    }

    public function isFromHoltkampStore(): bool
    {
        return $this->source === self::SOURCE_HOLTKAMP_STORE;
    }

    public function isFromAdminManual(): bool
    {
        return $this->source === self::SOURCE_ADMIN_MANUAL;
    }

    public function isFromTravelmilesMember(): bool
    {
        return $this->source === self::SOURCE_TRAVELMILES_MEMBER;
    }

    public function isFromCustomerOrder(): bool
    {
        return $this->source === self::SOURCE_CUSTOMER_ORDER;
    }

    /* ======================
       UNSUBSCRIBE TOKEN
    ====================== */

    public function getUnsubscribeToken(): ?string
    {
        return $this->unsubscribeToken;
    }

    public function setUnsubscribeToken(?string $unsubscribeToken): self
    {
        $this->unsubscribeToken = $unsubscribeToken !== null ? trim($unsubscribeToken) : null;

        return $this;
    }

    public function ensureUnsubscribeToken(): self
    {
        if ($this->unsubscribeToken === null || $this->unsubscribeToken === '') {
            $this->unsubscribeToken = bin2hex(random_bytes(32));
        }

        return $this;
    }

    /* ======================
       DATES
    ====================== */

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

        if ($unsubscribedAt !== null) {
            $this->isActive = false;
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->email !== '' ? $this->email : 'Newsletter subscription';
    }
}