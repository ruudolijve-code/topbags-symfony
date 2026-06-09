<?php

declare(strict_types=1);

namespace App\Shop\Service\Coupon;

use App\Shop\Entity\Coupon;
use App\Shop\Repository\CouponRepository;

final class CouponService
{
    public function __construct(
        private readonly CouponRepository $couponRepository,
    ) {
    }

    public function validate(string $code, float $subtotal): CouponValidationResult
    {
        $couponResult = $this->findAndValidateCoupon($code);

        if (!$couponResult->isValid()) {
            return $couponResult;
        }

        $coupon = $couponResult->getCoupon();

        if (!$coupon instanceof Coupon) {
            return CouponValidationResult::invalid('Deze couponcode is niet geldig.');
        }

        $minimumOrderAmount = $coupon->getMinimumOrderAmountAsFloat();

        if ($minimumOrderAmount !== null && $subtotal < $minimumOrderAmount) {
            return CouponValidationResult::invalid(sprintf(
                'Deze coupon is geldig vanaf een bestelbedrag van € %s.',
                number_format($minimumOrderAmount, 2, ',', '.')
            ));
        }

        $discountAmount = $this->calculateDiscountAmount($coupon, $subtotal);

        if ($discountAmount <= 0) {
            return CouponValidationResult::invalid('Deze coupon levert geen korting op.');
        }

        return CouponValidationResult::valid($coupon, $discountAmount);
    }

    public function validateForCartItems(string $code, iterable $cartItems): CouponValidationResult
    {
        $couponResult = $this->findAndValidateCoupon($code);

        if (!$couponResult->isValid()) {
            return $couponResult;
        }

        $coupon = $couponResult->getCoupon();

        if (!$coupon instanceof Coupon) {
            return CouponValidationResult::invalid('Deze couponcode is niet geldig.');
        }

        $subtotal = $this->calculateCartSubtotal($cartItems);
        $discountableSubtotal = $this->calculateDiscountableSubtotal($coupon, $cartItems);

        $minimumOrderAmount = $coupon->getMinimumOrderAmountAsFloat();

        if ($minimumOrderAmount !== null && $subtotal < $minimumOrderAmount) {
            return CouponValidationResult::invalid(sprintf(
                'Deze coupon is geldig vanaf een bestelbedrag van € %s.',
                number_format($minimumOrderAmount, 2, ',', '.')
            ));
        }

        if ($discountableSubtotal <= 0) {
            if ($coupon->appliesToBags()) {
                return CouponValidationResult::invalid('Deze couponcode is alleen geldig op de tassencollectie.');
            }

            if ($coupon->appliesToShop()) {
                return CouponValidationResult::invalid('Deze couponcode is alleen geldig op koffers, reistassen en reisbagage.');
            }

            return CouponValidationResult::invalid('Deze coupon levert geen korting op.');
        }

        $discountAmount = $this->calculateDiscountAmount($coupon, $discountableSubtotal);

        if ($discountAmount <= 0) {
            return CouponValidationResult::invalid('Deze coupon levert geen korting op.');
        }

        return CouponValidationResult::valid($coupon, $discountAmount);
    }

    public function calculateDiscountAmount(Coupon $coupon, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        return round($coupon->calculateDiscountAmount($subtotal), 2);
    }

    public function calculateDiscountableSubtotal(Coupon $coupon, iterable $cartItems): float
    {
        $subtotal = 0.0;

        foreach ($cartItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $productContext = $item['productContext'] ?? null;

            if (!$coupon->appliesToProductContext($productContext)) {
                continue;
            }

            if (($item['saleActive'] ?? false) === true) {
                continue;
            }

            $subtotal += (float) ($item['lineTotal'] ?? 0.0);
        }

        return round($subtotal, 2);
    }

    private function findAndValidateCoupon(string $code): CouponValidationResult
    {
        $normalizedCode = mb_strtoupper(trim($code));

        if ($normalizedCode === '') {
            return CouponValidationResult::invalid('Vul een couponcode in.');
        }

        $coupon = $this->couponRepository->findOneByCode($normalizedCode);

        if (!$coupon instanceof Coupon) {
            return CouponValidationResult::invalid('Deze couponcode is niet geldig.');
        }

        if (!$coupon->isActive()) {
            return CouponValidationResult::invalid('Deze couponcode is niet actief.');
        }

        if (!$coupon->isWithinDateWindow()) {
            return CouponValidationResult::invalid('Deze couponcode is niet (meer) geldig.');
        }

        if (!$coupon->hasRemainingRedemptions()) {
            return CouponValidationResult::invalid('Deze couponcode kan niet meer worden gebruikt.');
        }

        return CouponValidationResult::valid($coupon, 0.0);
    }

    private function calculateCartSubtotal(iterable $cartItems): float
    {
        $subtotal = 0.0;

        foreach ($cartItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $subtotal += (float) ($item['lineTotal'] ?? 0.0);
        }

        return round($subtotal, 2);
    }
}