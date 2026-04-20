<?php

namespace App\Shop\Service\Coupon;

use App\Shop\Entity\Coupon;

final class CouponValidationResult
{
    public function __construct(
        private bool $valid,
        private ?Coupon $coupon = null,
        private ?string $message = null,
        private float $discountAmount = 0.0
    ) {
    }

    public static function valid(Coupon $coupon, float $discountAmount): self
    {
        return new self(
            valid: true,
            coupon: $coupon,
            message: null,
            discountAmount: $discountAmount
        );
    }

    public static function invalid(string $message): self
    {
        return new self(
            valid: false,
            coupon: null,
            message: $message,
            discountAmount: 0.0
        );
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }
}