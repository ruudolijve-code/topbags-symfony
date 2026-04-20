<?php

namespace App\Shop\Service\Coupon;

use App\Shop\Entity\Coupon;
use App\Shop\Repository\CouponRepository;

final class CouponService
{
    public function __construct(
        private CouponRepository $couponRepository
    ) {
    }

    public function validate(string $code, float $subtotal): CouponValidationResult
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

    public function calculateDiscountAmount(Coupon $coupon, float $subtotal): float
    {
        $percent = $coupon->getDiscountPercentAsFloat();

        if ($percent <= 0 || $subtotal <= 0) {
            return 0.0;
        }

        $discount = $subtotal * ($percent / 100);

        return round($discount, 2);
    }
}