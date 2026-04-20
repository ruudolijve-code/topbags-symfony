<?php

namespace App\Shop\Entity;

use App\Shop\Repository\CouponRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name: 'coupon')]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80, unique: true)]
    private string $code;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $discountPercent = '0.00';

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $minimumOrderAmount = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxRedemptions = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $timesRedeemed = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->timesRedeemed = 0;
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /* ======================
       CODE
    ====================== */

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = mb_strtoupper(trim($code));
        return $this;
    }

    /* ======================
       NAME
    ====================== */

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    /* ======================
       DISCOUNT
    ====================== */

    public function getDiscountPercent(): string
    {
        return $this->discountPercent;
    }

    public function getDiscountPercentAsFloat(): float
    {
        return (float) $this->discountPercent;
    }

    public function setDiscountPercent(string|float|int $percent): self
    {
        $value = (float) $percent;

        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Discount percent must be between 0 and 100.');
        }

        $this->discountPercent = number_format($value, 2, '.', '');

        return $this;
    }

    /* ======================
       ACTIVE
    ====================== */

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $active): self
    {
        $this->isActive = $active;
        return $this;
    }

    /* ======================
       DATE WINDOW
    ====================== */

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeImmutable $date): self
    {
        $this->startsAt = $date;
        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $date): self
    {
        $this->endsAt = $date;
        return $this;
    }

    /* ======================
       MINIMUM ORDER
    ====================== */

    public function getMinimumOrderAmount(): ?string
    {
        return $this->minimumOrderAmount;
    }

    public function getMinimumOrderAmountAsFloat(): ?float
    {
        return $this->minimumOrderAmount !== null
            ? (float) $this->minimumOrderAmount
            : null;
    }

    public function setMinimumOrderAmount(string|float|int|null $amount): self
    {
        if ($amount === null || $amount === '') {
            $this->minimumOrderAmount = null;
            return $this;
        }

        $value = (float) $amount;

        if ($value < 0) {
            throw new \InvalidArgumentException('Minimum order amount cannot be negative.');
        }

        $this->minimumOrderAmount = number_format($value, 2, '.', '');

        return $this;
    }

    /* ======================
       REDEMPTIONS
    ====================== */

    public function getMaxRedemptions(): ?int
    {
        return $this->maxRedemptions;
    }

    public function setMaxRedemptions(?int $max): self
    {
        if ($max !== null && $max < 1) {
            throw new \InvalidArgumentException('Max redemptions must be null or greater than 0.');
        }

        $this->maxRedemptions = $max;

        return $this;
    }

    public function getTimesRedeemed(): int
    {
        return $this->timesRedeemed;
    }

    public function setTimesRedeemed(int $times): self
    {
        $this->timesRedeemed = max(0, $times);
        return $this;
    }

    public function incrementTimesRedeemed(): self
    {
        $this->timesRedeemed++;
        return $this;
    }

    /* ======================
       CREATED
    ====================== */

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /* ======================
       BUSINESS LOGIC
    ====================== */

    public function isWithinDateWindow(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        if ($this->startsAt !== null && $now < $this->startsAt) {
            return false;
        }

        if ($this->endsAt !== null && $now > $this->endsAt) {
            return false;
        }

        return true;
    }

    public function hasRemainingRedemptions(): bool
    {
        if ($this->maxRedemptions === null) {
            return true;
        }

        return $this->timesRedeemed < $this->maxRedemptions;
    }
}