<?php

declare(strict_types=1);

namespace App\Marketing\Entity;

use App\Marketing\Repository\NewsletterSendLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterSendLogRepository::class)]
#[ORM\Table(name: 'newsletter_send_log')]
#[ORM\UniqueConstraint(
    name: 'uniq_newsletter_campaign_subscription',
    columns: ['campaign_id', 'subscription_id']
)]
class NewsletterSendLog
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private NewsletterCampaign $campaign;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private NewsletterSubscription $subscription;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

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

    public function getCampaign(): NewsletterCampaign
    {
        return $this->campaign;
    }

    public function setCampaign(NewsletterCampaign $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getSubscription(): NewsletterSubscription
    {
        return $this->subscription;
    }

    public function setSubscription(NewsletterSubscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function markSent(): self
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTimeImmutable();
        $this->errorMessage = null;

        return $this;
    }

    public function markFailed(string $message): self
    {
        $this->status = self::STATUS_FAILED;
        $this->errorMessage = mb_substr($message, 0, 2000);

        return $this;
    }

    public function markSkipped(?string $message = null): self
    {
        $this->status = self::STATUS_SKIPPED;
        $this->errorMessage = $message !== null ? mb_substr($message, 0, 2000) : null;

        return $this;
    }
}