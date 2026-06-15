<?php

declare(strict_types=1);

namespace App\Marketing\Entity;

use App\Marketing\Repository\NewsletterSubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterSubscriptionRepository::class)]
#[ORM\Table(name: 'newsletter_subscription')]
#[ORM\UniqueConstraint(
    name: 'uniq_newsletter_subscription_email',
    columns: ['email']
)]
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

    public const BOUNCE_TYPE_HARD = 'hard';
    public const BOUNCE_TYPE_SOFT = 'soft';

    public const BOUNCE_TYPES = [
        self::BOUNCE_TYPE_HARD,
        self::BOUNCE_TYPE_SOFT,
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

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $unsubscribedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $bounceCount = 0;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $lastBounceType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastBounceReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastBouncedAt = null;

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
            throw new \InvalidArgumentException(
                'Email cannot be empty.'
            );
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

    /**
     * Handmatige wijziging van de actieve status.
     *
     * Bij deactivering wordt dit als uitschrijving geregistreerd.
     * Gebruik markHardBounce() voor een permanente afleverfout.
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        if ($isActive) {
            $this->unsubscribedAt = null;
            $this->ensureUnsubscribeToken();

            return $this;
        }

        if ($this->unsubscribedAt === null) {
            $this->unsubscribedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * Vrijwillige uitschrijving door de ontvanger.
     */
    public function unsubscribe(): self
    {
        $this->isActive = false;
        $this->unsubscribedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Opnieuw activeren na toestemming of correctie van het adres.
     */
    public function resubscribe(): self
    {
        $this->isActive = true;
        $this->unsubscribedAt = null;
        $this->ensureUnsubscribeToken();
        $this->clearBounceState();

        return $this;
    }

    /* ======================
       BOUNCES
    ====================== */

    public function getBounceCount(): int
    {
        return $this->bounceCount;
    }

    public function setBounceCount(int $bounceCount): self
    {
        $this->bounceCount = max(0, $bounceCount);

        return $this;
    }

    public function incrementBounceCount(): self
    {
        ++$this->bounceCount;

        return $this;
    }

    public function hasBounced(): bool
    {
        return $this->bounceCount > 0;
    }

    public function isHardBounced(): bool
    {
        return $this->lastBounceType === self::BOUNCE_TYPE_HARD;
    }

    public function isSoftBounced(): bool
    {
        return $this->lastBounceType === self::BOUNCE_TYPE_SOFT;
    }

    public function getLastBounceType(): ?string
    {
        return $this->lastBounceType;
    }

    public function setLastBounceType(?string $lastBounceType): self
    {
        if ($lastBounceType === null || trim($lastBounceType) === '') {
            $this->lastBounceType = null;

            return $this;
        }

        $lastBounceType = mb_strtolower(trim($lastBounceType));

        if (!in_array($lastBounceType, self::BOUNCE_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid bounce type "%s".',
                $lastBounceType
            ));
        }

        $this->lastBounceType = $lastBounceType;

        return $this;
    }

    public function getLastBounceReason(): ?string
    {
        return $this->lastBounceReason;
    }

    public function setLastBounceReason(?string $lastBounceReason): self
    {
        if ($lastBounceReason === null) {
            $this->lastBounceReason = null;

            return $this;
        }

        $lastBounceReason = trim($lastBounceReason);

        $this->lastBounceReason = $lastBounceReason !== ''
            ? $lastBounceReason
            : null;

        return $this;
    }

    public function getLastBouncedAt(): ?\DateTimeImmutable
    {
        return $this->lastBouncedAt;
    }

    public function setLastBouncedAt(
        ?\DateTimeImmutable $lastBouncedAt
    ): self {
        $this->lastBouncedAt = $lastBouncedAt;

        return $this;
    }

    /**
     * Registreert een tijdelijke afleverfout.
     *
     * De inschrijving blijft actief.
     */
    public function markSoftBounce(
        ?string $reason = null,
        ?\DateTimeImmutable $bouncedAt = null,
    ): self {
        $this->recordBounce(
            self::BOUNCE_TYPE_SOFT,
            $reason,
            $bouncedAt
        );

        return $this;
    }

    /**
     * Registreert een permanente afleverfout.
     *
     * De inschrijving wordt gedeactiveerd zonder unsubscribedAt te vullen,
     * omdat de ontvanger zichzelf niet heeft uitgeschreven.
     */
    public function markHardBounce(
        ?string $reason = null,
        ?\DateTimeImmutable $bouncedAt = null,
    ): self {
        $this->recordBounce(
            self::BOUNCE_TYPE_HARD,
            $reason,
            $bouncedAt
        );

        $this->isActive = false;

        return $this;
    }

    /**
     * Verwijdert de actuele bouncestatus.
     *
     * Gebruik dit alleen wanneer het adres aantoonbaar is gecorrigeerd
     * of opnieuw door de ontvanger is bevestigd.
     */
    public function clearBounceState(): self
    {
        $this->bounceCount = 0;
        $this->lastBounceType = null;
        $this->lastBounceReason = null;
        $this->lastBouncedAt = null;

        return $this;
    }

    private function recordBounce(
        string $type,
        ?string $reason = null,
        ?\DateTimeImmutable $bouncedAt = null,
    ): void {
        $this->setLastBounceType($type);
        $this->setLastBounceReason($reason);

        $this->lastBouncedAt = $bouncedAt ?? new \DateTimeImmutable();
        ++$this->bounceCount;
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
            throw new \InvalidArgumentException(sprintf(
                'Invalid newsletter source "%s".',
                $source
            ));
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

    public function setUnsubscribeToken(
        ?string $unsubscribeToken
    ): self {
        if ($unsubscribeToken === null) {
            $this->unsubscribeToken = null;

            return $this;
        }

        $unsubscribeToken = trim($unsubscribeToken);

        $this->unsubscribeToken = $unsubscribeToken !== ''
            ? $unsubscribeToken
            : null;

        return $this;
    }

    public function ensureUnsubscribeToken(): self
    {
        if (
            $this->unsubscribeToken === null
            || $this->unsubscribeToken === ''
        ) {
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

    public function setUnsubscribedAt(
        ?\DateTimeImmutable $unsubscribedAt
    ): self {
        $this->unsubscribedAt = $unsubscribedAt;

        if ($unsubscribedAt !== null) {
            $this->isActive = false;
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->email !== ''
            ? $this->email
            : 'Newsletter subscription';
    }
}