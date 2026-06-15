<?php

declare(strict_types=1);

namespace App\Marketing\Entity;

use App\Marketing\Repository\NewsletterDeliveryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterDeliveryRepository::class)]
#[ORM\Table(name: 'newsletter_delivery')]
#[ORM\UniqueConstraint(
    name: 'uniq_newsletter_delivery_token',
    columns: ['delivery_token']
)]
#[ORM\Index(
    name: 'idx_newsletter_delivery_campaign_status',
    columns: ['campaign_id', 'status']
)]
#[ORM\Index(
    name: 'idx_newsletter_delivery_recipient_email',
    columns: ['recipient_email']
)]
#[ORM\Index(
    name: 'idx_newsletter_delivery_message_id',
    columns: ['message_id']
)]
#[ORM\HasLifecycleCallbacks]
class NewsletterDelivery
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SMTP_ACCEPTED = 'smtp_accepted';
    public const STATUS_DIRECT_FAILED = 'direct_failed';
    public const STATUS_HARD_BOUNCE = 'hard_bounce';
    public const STATUS_SOFT_BOUNCE = 'soft_bounce';
    public const STATUS_TECHNICAL_FAILURE = 'technical_failure';
    public const STATUS_REVIEW = 'review';

    public const STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_SMTP_ACCEPTED,
        self::STATUS_DIRECT_FAILED,
        self::STATUS_HARD_BOUNCE,
        self::STATUS_SOFT_BOUNCE,
        self::STATUS_TECHNICAL_FAILURE,
        self::STATUS_REVIEW,
    ];

    public const BOUNCE_TYPE_HARD = 'hard';
    public const BOUNCE_TYPE_SOFT = 'soft';
    public const BOUNCE_TYPE_TECHNICAL = 'technical';
    public const BOUNCE_TYPE_REVIEW = 'review';

    public const BOUNCE_TYPES = [
        self::BOUNCE_TYPE_HARD,
        self::BOUNCE_TYPE_SOFT,
        self::BOUNCE_TYPE_TECHNICAL,
        self::BOUNCE_TYPE_REVIEW,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NewsletterCampaign::class)]
    #[ORM\JoinColumn(
        name: 'campaign_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?NewsletterCampaign $campaign = null;

    /**
     * De relatie mag leeg worden wanneer een inschrijving ooit wordt verwijderd.
     * recipientEmail blijft als historische momentopname bewaard.
     */
    #[ORM\ManyToOne(targetEntity: NewsletterSubscription::class)]
    #[ORM\JoinColumn(
        name: 'subscription_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?NewsletterSubscription $subscription = null;

    #[ORM\Column(name: 'recipient_email', length: 180)]
    private string $recipientEmail = '';

    /**
     * Unieke, niet-oplopende identifier die later in de mailheader komt.
     */
    #[ORM\Column(name: 'delivery_token', length: 64, unique: true)]
    private string $deliveryToken;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_QUEUED;

    /**
     * Message-ID van de verstuurde e-mail.
     */
    #[ORM\Column(name: 'message_id', length: 255, nullable: true)]
    private ?string $messageId = null;

    /**
     * Moment waarop de uitgaande SMTP-server het bericht accepteerde.
     */
    #[ORM\Column(
        name: 'smtp_accepted_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $smtpAcceptedAt = null;

    /**
     * Directe fout tijdens het aanbieden aan de uitgaande mailserver.
     */
    #[ORM\Column(
        name: 'direct_failed_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $directFailedAt = null;

    #[ORM\Column(
        name: 'direct_failure_reason',
        type: Types::TEXT,
        nullable: true
    )]
    private ?string $directFailureReason = null;

    #[ORM\Column(name: 'bounce_type', length: 20, nullable: true)]
    private ?string $bounceType = null;

    #[ORM\Column(
        name: 'bounce_reason',
        type: Types::TEXT,
        nullable: true
    )]
    private ?string $bounceReason = null;

    #[ORM\Column(
        name: 'bounced_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $bouncedAt = null;

    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->deliveryToken = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaign(): ?NewsletterCampaign
    {
        return $this->campaign;
    }

    public function setCampaign(NewsletterCampaign $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getSubscription(): ?NewsletterSubscription
    {
        return $this->subscription;
    }

    public function setSubscription(
        ?NewsletterSubscription $subscription
    ): self {
        $this->subscription = $subscription;

        return $this;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): self
    {
        $recipientEmail = mb_strtolower(trim($recipientEmail));

        if ($recipientEmail === '') {
            throw new \InvalidArgumentException(
                'Recipient email cannot be empty.'
            );
        }

        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid recipient email "%s".',
                $recipientEmail
            ));
        }

        $this->recipientEmail = $recipientEmail;

        return $this;
    }

    public function getDeliveryToken(): string
    {
        return $this->deliveryToken;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $status = mb_strtolower(trim($status));

        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid newsletter delivery status "%s".',
                $status
            ));
        }

        $this->status = $status;

        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): self
    {
        $messageId = $messageId !== null
            ? trim($messageId)
            : null;

        $this->messageId = $messageId !== ''
            ? $messageId
            : null;

        return $this;
    }

    public function getSmtpAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->smtpAcceptedAt;
    }

    public function getDirectFailedAt(): ?\DateTimeImmutable
    {
        return $this->directFailedAt;
    }

    public function getDirectFailureReason(): ?string
    {
        return $this->directFailureReason;
    }

    public function getBounceType(): ?string
    {
        return $this->bounceType;
    }

    public function getBounceReason(): ?string
    {
        return $this->bounceReason;
    }

    public function getBouncedAt(): ?\DateTimeImmutable
    {
        return $this->bouncedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markQueued(): self
    {
        $this->status = self::STATUS_QUEUED;
        $this->directFailedAt = null;
        $this->directFailureReason = null;

        return $this;
    }

    public function markSmtpAccepted(
        ?string $messageId = null,
        ?\DateTimeImmutable $acceptedAt = null,
    ): self {
        $this->status = self::STATUS_SMTP_ACCEPTED;
        $this->smtpAcceptedAt = $acceptedAt ?? new \DateTimeImmutable();

        $this->setMessageId($messageId);

        $this->directFailedAt = null;
        $this->directFailureReason = null;

        return $this;
    }

    public function markDirectFailed(
        ?string $reason = null,
        ?\DateTimeImmutable $failedAt = null,
    ): self {
        $this->status = self::STATUS_DIRECT_FAILED;
        $this->directFailedAt = $failedAt ?? new \DateTimeImmutable();
        $this->directFailureReason = $this->normalizeNullableText($reason);

        return $this;
    }

    public function markHardBounce(
        ?string $reason = null,
        ?\DateTimeImmutable $bouncedAt = null,
    ): self {
        return $this->markBounce(
            self::BOUNCE_TYPE_HARD,
            self::STATUS_HARD_BOUNCE,
            $reason,
            $bouncedAt
        );
    }

    public function markSoftBounce(
        ?string $reason = null,
        ?\DateTimeImmutable $bouncedAt = null,
    ): self {
        return $this->markBounce(
            self::BOUNCE_TYPE_SOFT,
            self::STATUS_SOFT_BOUNCE,
            $reason,
            $bouncedAt
        );
    }

    public function markTechnicalFailure(
        ?string $reason = null,
        ?\DateTimeImmutable $bouncedAt = null,
    ): self {
        return $this->markBounce(
            self::BOUNCE_TYPE_TECHNICAL,
            self::STATUS_TECHNICAL_FAILURE,
            $reason,
            $bouncedAt
        );
    }

    public function markForReview(
        ?string $reason = null,
        ?\DateTimeImmutable $bouncedAt = null,
    ): self {
        return $this->markBounce(
            self::BOUNCE_TYPE_REVIEW,
            self::STATUS_REVIEW,
            $reason,
            $bouncedAt
        );
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function markBounce(
        string $bounceType,
        string $status,
        ?string $reason,
        ?\DateTimeImmutable $bouncedAt,
    ): self {
        if (!in_array($bounceType, self::BOUNCE_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid bounce type "%s".',
                $bounceType
            ));
        }

        $this->bounceType = $bounceType;
        $this->status = $status;
        $this->bounceReason = $this->normalizeNullableText($reason);
        $this->bouncedAt = $bouncedAt ?? new \DateTimeImmutable();

        return $this;
    }

    private function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    public function __toString(): string
    {
        return $this->recipientEmail !== ''
            ? $this->recipientEmail
            : 'Newsletter delivery';
    }
}