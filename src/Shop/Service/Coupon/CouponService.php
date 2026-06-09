<?php

declare(strict_types=1);

namespace App\Shop\Service\Coupon;

use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
use App\Shop\Entity\Coupon;
use App\Shop\Repository\CouponRepository;

final class CouponService
{
    public function __construct(
        private readonly CouponRepository $couponRepository,
    ) {
    }

    /**
     * Oude methode: blijft bestaan voor coupons die over de hele order mogen gelden.
     *
     * Let op:
     * Deze methode kent geen cartregels en kan dus geen onderscheid maken tussen
     * tassen, koffers, travel of sale-artikelen.
     */
    public function validate(string $code, float $subtotal): CouponValidationResult
    {
        $couponResult = $this->findAndValidateCoupon($code);

        if (!$couponResult instanceof CouponValidationResult || !$couponResult->isValid()) {
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

    /**
     * Nieuwe methode: gebruik deze voor de winkelwagen.
     *
     * Deze berekent eerst welk deel van de cart korting mag krijgen:
     * - TASSEN20 / appliesToContext=bags: alleen producten met context bags
     * - shop-coupon: alleen producten met context shop
     * - all: hele cart
     *
     * Reeds afgeprijsde artikelen worden uitgesloten als de variant sale actief is.
     */
    public function validateForCartItems(string $code, iterable $cartItems): CouponValidationResult
    {
        $couponResult = $this->findAndValidateCoupon($code);

        if (!$couponResult instanceof CouponValidationResult || !$couponResult->isValid()) {
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
                return CouponValidationResult::invalid('Deze couponcode is alleen geldig op travel, koffers en reisbagage.');
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
            $variant = $this->getVariantFromCartItem($item);

            if (!$variant instanceof ProductVariant) {
                continue;
            }

            $product = $variant->getProduct();

            if (!$product instanceof Product) {
                continue;
            }

            if (!$coupon->appliesToProductContext($product->getContext())) {
                continue;
            }

            /*
             * Voorwaarde uit nieuwsbrief:
             * Niet geldig op reeds afgeprijsde artikelen en lopende acties.
             */
            if (method_exists($variant, 'isSaleActive') && $variant->isSaleActive()) {
                continue;
            }

            $subtotal += $this->getCartItemLineTotal($item);
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
            $subtotal += $this->getCartItemLineTotal($item);
        }

        return round($subtotal, 2);
    }

    private function getVariantFromCartItem(object $item): ?ProductVariant
    {
        if (method_exists($item, 'getProductVariant')) {
            $variant = $item->getProductVariant();

            return $variant instanceof ProductVariant ? $variant : null;
        }

        if (method_exists($item, 'getVariant')) {
            $variant = $item->getVariant();

            return $variant instanceof ProductVariant ? $variant : null;
        }

        return null;
    }

    private function getCartItemLineTotal(object $item): float
    {
        if (method_exists($item, 'getLineTotal')) {
            return (float) $item->getLineTotal();
        }

        if (method_exists($item, 'getTotal')) {
            return (float) $item->getTotal();
        }

        if (method_exists($item, 'getUnitPrice') && method_exists($item, 'getQuantity')) {
            return (float) $item->getUnitPrice() * (int) $item->getQuantity();
        }

        if (method_exists($item, 'getPrice') && method_exists($item, 'getQuantity')) {
            return (float) $item->getPrice() * (int) $item->getQuantity();
        }

        return 0.0;
    }
}