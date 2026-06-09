<?php

namespace App\Shop\Entity;

use App\Shop\Repository\CouponRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name: 'coupon')]
class Coupon
{
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    public const APPLIES_TO_ALL = 'all';
    public const APPLIES_TO_BAGS = 'bags';
    public const APPLIES_TO_SHOP = 'shop';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\Column(length: 30, options: ['default' => self::TYPE_PERCENTAGE])]
    private string $discountType = self::TYPE_PERCENTAGE;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $discountPercent = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $discountAmount = null;

    #[ORM\Column(length: 30, options: ['default' => self::APPLIES_TO_ALL])]
    private string $appliesToContext = self::APPLIES_TO_ALL;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $minimumOrderAmount = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxRedemptions = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $timesRedeemed = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->timesRedeemed = 0;
        $this->isActive = true;
        $this->discountType = self::TYPE_PERCENTAGE;
        $this->appliesToContext = self::APPLIES_TO_ALL;
    }

    public function __toString(): string
    {
        return $this->code !== '' ? $this->code : 'Coupon';
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
       DISCOUNT TYPE
    ====================== */

    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    public function setDiscountType(string $discountType): self
    {
        if (!in_array($discountType, [self::TYPE_PERCENTAGE, self::TYPE_FIXED_AMOUNT], true)) {
            throw new \InvalidArgumentException('Invalid discount type.');
        }

        $this->discountType = $discountType;

        return $this;
    }

    public function isPercentageDiscount(): bool
    {
        return $this->discountType === self::TYPE_PERCENTAGE;
    }

    public function isFixedAmountDiscount(): bool
    {
        return $this->discountType === self::TYPE_FIXED_AMOUNT;
    }

    /* ======================
       PERCENTAGE DISCOUNT
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
       FIXED AMOUNT DISCOUNT
    ====================== */

    public function getDiscountAmount(): ?string
    {
        return $this->discountAmount;
    }

    public function getDiscountAmountAsFloat(): float
    {
        return $this->discountAmount !== null
            ? (float) $this->discountAmount
            : 0.0;
    }

    public function setDiscountAmount(string|float|int|null $amount): self
    {
        if ($amount === null || $amount === '') {
            $this->discountAmount = null;

            return $this;
        }

        $value = (float) $amount;

        if ($value < 0) {
            throw new \InvalidArgumentException('Discount amount cannot be negative.');
        }

        $this->discountAmount = number_format($value, 2, '.', '');

        return $this;
    }

    public function getFormattedDiscount(): string
    {
        if ($this->isFixedAmountDiscount()) {
            return '€' . number_format($this->getDiscountAmountAsFloat(), 2, ',', '.');
        }

        return number_format($this->getDiscountPercentAsFloat(), 2, ',', '.') . '%';
    }

    /* ======================
       APPLIES TO CONTEXT
    ====================== */

    public function getAppliesToContext(): string
    {
        return $this->appliesToContext;
    }

    public function setAppliesToContext(?string $appliesToContext): self
    {
        $appliesToContext ??= self::APPLIES_TO_ALL;

        if (!in_array($appliesToContext, [
            self::APPLIES_TO_ALL,
            self::APPLIES_TO_BAGS,
            self::APPLIES_TO_SHOP,
        ], true)) {
            throw new \InvalidArgumentException('Invalid coupon context.');
        }

        $this->appliesToContext = $appliesToContext;

        return $this;
    }

    public function appliesToAll(): bool
    {
        return $this->appliesToContext === self::APPLIES_TO_ALL;
    }

    public function appliesToBags(): bool
    {
        return $this->appliesToContext === self::APPLIES_TO_BAGS;
    }

    public function appliesToShop(): bool
    {
        return $this->appliesToContext === self::APPLIES_TO_SHOP;
    }

    public function appliesToProductContext(?string $productContext): bool
    {
        if ($this->appliesToAll()) {
            return true;
        }

        if ($productContext === null || $productContext === '') {
            return false;
        }

        return $this->appliesToContext === $productContext;
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

    public function canBeUsedForAmount(float $orderAmount, ?\DateTimeImmutable $now = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if (!$this->isWithinDateWindow($now)) {
            return false;
        }

        if (!$this->hasRemainingRedemptions()) {
            return false;
        }

        $minimumOrderAmount = $this->getMinimumOrderAmountAsFloat();

        if ($minimumOrderAmount !== null && $orderAmount < $minimumOrderAmount) {
            return false;
        }

        return true;
    }

    public function calculateDiscountAmount(float $orderAmount): float
    {
        if ($orderAmount <= 0) {
            return 0.0;
        }

        if ($this->isFixedAmountDiscount()) {
            return min($orderAmount, $this->getDiscountAmountAsFloat());
        }

        $discount = $orderAmount * ($this->getDiscountPercentAsFloat() / 100);

        return min($orderAmount, round($discount, 2));
    }
}