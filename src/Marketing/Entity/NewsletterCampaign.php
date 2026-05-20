<?php

declare(strict_types=1);

namespace App\Marketing\Entity;

use App\Marketing\Repository\NewsletterCampaignRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterCampaignRepository::class)]
#[ORM\Table(name: 'newsletter_campaign')]
class NewsletterCampaign
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(length: 180)]
    private string $subject = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $preheader = null;

    #[ORM\Column(type: 'text')]
    private string $htmlBody = '';

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(options: ['default' => 0])]
    private int $recipientCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $sentCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $failedCount = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = trim($subject);

        return $this;
    }

    public function getPreheader(): ?string
    {
        return $this->preheader;
    }

    public function setPreheader(?string $preheader): self
    {
        $this->preheader = $preheader !== null ? trim($preheader) : null;

        return $this;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    public function setHtmlBody(string $htmlBody): self
    {
        $this->htmlBody = $htmlBody;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [
            self::STATUS_DRAFT,
            self::STATUS_SENDING,
            self::STATUS_SENT,
        ], true)) {
            throw new \InvalidArgumentException(sprintf('Ongeldige nieuwsbriefstatus "%s".', $status));
        }

        $this->status = $status;

        return $this;
    }

    public function getRecipientCount(): int
    {
        return $this->recipientCount;
    }

    public function setRecipientCount(int $recipientCount): self
    {
        $this->recipientCount = max(0, $recipientCount);

        return $this;
    }

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function setSentCount(int $sentCount): self
    {
        $this->sentCount = max(0, $sentCount);

        return $this;
    }

    public function incrementSentCount(): self
    {
        ++$this->sentCount;

        return $this;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function setFailedCount(int $failedCount): self
    {
        $this->failedCount = max(0, $failedCount);

        return $this;
    }

    public function incrementFailedCount(): self
    {
        ++$this->failedCount;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function markSending(int $recipientCount): self
    {
        $this->status = self::STATUS_SENDING;
        $this->recipientCount = max(0, $recipientCount);
        $this->sentCount = 0;
        $this->failedCount = 0;
        $this->sentAt = null;

        return $this;
    }

    public function markSent(): self
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTimeImmutable();

        return $this;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function __toString(): string
    {
        return $this->title !== '' ? $this->title : 'Nieuwsbrief';
    }
}