<?php

declare(strict_types=1);

namespace App\Loyalty\Service;

use App\Loyalty\Entity\TravelMilesMember;
use App\Loyalty\Entity\TravelMilesVoucher;
use App\Shop\Entity\Coupon;

final class TravelMilesVoucherFactory
{
    public function createWelcomeVoucherForMember(
        TravelMilesMember $member,
        string|float|int $amount = '10.00',
        ?string $campaign = 'Welkomstvoucher',
    ): TravelMilesVoucher {
        $voucher = new TravelMilesVoucher();

        $voucher
            ->setMember($member)
            ->setAmount($amount)
            ->setCurrency('EUR')
            ->setCampaign($campaign)
            ->setStatus(TravelMilesVoucher::STATUS_CREATED)
            ->setExpiresAt(new \DateTimeImmutable('+6 months'));

        $coupon = $this->createCouponForVoucher($voucher);

        $voucher->setCoupon($coupon);

        return $voucher;
    }

    public function createCouponForVoucher(TravelMilesVoucher $voucher): Coupon
    {
        $coupon = new Coupon();

        $coupon
            ->setCode($voucher->getCode())
            ->setName($this->buildCouponName($voucher))
            ->setDiscountType(Coupon::TYPE_FIXED_AMOUNT)
            ->setDiscountPercent(0)
            ->setDiscountAmount($voucher->getAmount())
            ->setIsActive(true)
            ->setStartsAt(new \DateTimeImmutable())
            ->setEndsAt($voucher->getExpiresAt())
            ->setMinimumOrderAmount(null)
            ->setMaxRedemptions(1);

        return $coupon;
    }

    public function syncCouponWithVoucher(TravelMilesVoucher $voucher): void
    {
        $coupon = $voucher->getCoupon();

        if (!$coupon instanceof Coupon) {
            $coupon = $this->createCouponForVoucher($voucher);
            $voucher->setCoupon($coupon);

            return;
        }

        $coupon
            ->setCode($voucher->getCode())
            ->setName($this->buildCouponName($voucher))
            ->setDiscountType(Coupon::TYPE_FIXED_AMOUNT)
            ->setDiscountPercent(0)
            ->setDiscountAmount($voucher->getAmount())
            ->setEndsAt($voucher->getExpiresAt())
            ->setMaxRedemptions(1);

        if ($voucher->getStatus() === TravelMilesVoucher::STATUS_CANCELLED) {
            $coupon->setIsActive(false);
        }
    }

    private function buildCouponName(TravelMilesVoucher $voucher): string
    {
        $campaign = $voucher->getCampaign() ?: 'Travelmiles voucher';
        $member = $voucher->getMember();

        if ($member instanceof TravelMilesMember) {
            $name = $member->getFullName();

            if ($name !== '') {
                return sprintf('%s - %s', $campaign, $name);
            }

            return sprintf('%s - %s', $campaign, $member->getEmail());
        }

        return $campaign;
    }
}