<?php

declare(strict_types=1);

namespace App\Loyalty\Entity;

use App\Shop\Entity\Coupon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'travel_miles_voucher')]
#[ORM\UniqueConstraint(name: 'UNIQ_TRAVEL_MILES_VOUCHER_CODE', columns: ['code'])]
class TravelMilesVoucher
{
    public const STATUS_CREATED = 'created';
    public const STATUS_SENT = 'sent';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TravelMilesMember::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TravelMilesMember $member = null;

    #[ORM\OneToOne(targetEntity: Coupon::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Coupon $coupon = null;

    #[ORM\Column(length: 50)]
    private string $code = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount = '10.00';

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_CREATED;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $campaign = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $redeemedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable('+6 months');
        $this->code = self::generateCode();
    }

    public function __toString(): string
    {
        return $this->code !== '' ? $this->code : 'Travelmiles voucher';
    }

    public static function generateCode(): string
    {
        return 'TM-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): ?TravelMilesMember
    {
        return $this->member;
    }

    public function setMember(?TravelMilesMember $member): self
    {
        $this->member = $member;

        return $this;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): self
    {
        $this->coupon = $coupon;

        return $this;
    }

    public function hasCoupon(): bool
    {
        return $this->coupon instanceof Coupon;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper(trim($code));

        if ($this->coupon instanceof Coupon) {
            $this->coupon->setCode($this->code);
        }

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getAmountAsFloat(): float
    {
        return (float) $this->amount;
    }

    public function setAmount(string|float|int $amount): self
    {
        $this->amount = number_format((float) $amount, 2, '.', '');

        if ($this->coupon instanceof Coupon) {
            $this->coupon->setDiscountAmount($this->amount);
        }

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $currency = strtoupper(trim($currency));

        $this->currency = $currency !== '' ? mb_substr($currency, 0, 3) : 'EUR';

        return $this;
    }

    public function getFormattedAmount(): string
    {
        return '€' . number_format((float) $this->amount, 2, ',', '.');
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $allowedStatuses = [
            self::STATUS_CREATED,
            self::STATUS_SENT,
            self::STATUS_REDEEMED,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED,
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = self::STATUS_CREATED;
        }

        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_CREATED => 'Aangemaakt',
            self::STATUS_SENT => 'Verstuurd',
            self::STATUS_REDEEMED => 'Gebruikt',
            self::STATUS_EXPIRED => 'Verlopen',
            self::STATUS_CANCELLED => 'Geannuleerd',
            default => $this->status,
        };
    }

    public function getCampaign(): ?string
    {
        return $this->campaign;
    }

    public function setCampaign(?string $campaign): self
    {
        $campaign = $campaign !== null ? trim($campaign) : null;

        $this->campaign = $campaign !== '' ? $campaign : null;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function markAsSent(): self
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTimeImmutable();

        return $this;
    }

    public function getRedeemedAt(): ?\DateTimeImmutable
    {
        return $this->redeemedAt;
    }

    public function setRedeemedAt(?\DateTimeImmutable $redeemedAt): self
    {
        $this->redeemedAt = $redeemedAt;

        return $this;
    }

    public function markAsRedeemed(): self
    {
        $this->status = self::STATUS_REDEEMED;
        $this->redeemedAt = new \DateTimeImmutable();

        if ($this->coupon instanceof Coupon) {
            $this->coupon->incrementTimesRedeemed();
        }

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        if ($this->coupon instanceof Coupon) {
            $this->coupon->setEndsAt($expiresAt);
        }

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function markAsCancelled(): self
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();

        if ($this->coupon instanceof Coupon) {
            $this->coupon->setIsActive(false);
        }

        return $this;
    }

    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_SENT) {
            return false;
        }

        if ($this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable()) {
            return false;
        }

        return true;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $notes = $notes !== null ? trim($notes) : null;

        $this->notes = $notes !== '' ? $notes : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}