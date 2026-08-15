<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\VariantImagePathResolver;
use App\Shop\Service\CartAvailabilityGuard;
use App\Shop\Service\CartService;
use App\Shop\Service\Coupon\CouponService;
use App\Shop\Service\ShippingCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private VariantImagePathResolver $variantImagePathResolver
    ) {
    }

    #[Route('/cart', name: 'cart_show', methods: ['GET'])]
    public function show(
        CartService $cart,
        ProductVariantRepository $variantRepository,
        ShippingCalculator $shippingCalculator,
        CouponService $couponService
    ): Response {
        $rawItems = $cart->all();

        $cartItems = [];
        $subtotal = 0.0;
        $shippableSubtotal = 0.0;
        $hasCouponEligibleItems = false;

        foreach ($rawItems as $row) {
            if (!isset($row['sku'], $row['qty'])) {
                continue;
            }

            $variant = $variantRepository->findOneBy([
                'variantSku' => (string) $row['sku'],
                'isActive' => true,
            ]);

            if ($variant === null) {
                continue;
            }

            $product = $variant->getProduct();

            if ($product === null || !$product->isActive()) {
                continue;
            }

            $price = (float) $variant->getDisplayPrice();
            $qty = max(1, (int) $row['qty']);
            $lineTotal = $price * $qty;

            $productType = $product->getProductType();
            $requiresShipping = $productType->requiresShipping();
            $couponEligible = $productType->couponEligible();

            $subtotal += $lineTotal;

            if ($requiresShipping) {
                $shippableSubtotal += $lineTotal;
            }

            if ($couponEligible) {
                $hasCouponEligibleItems = true;
            }

            $cartItems[] = [
                'sku' => $variant->getVariantSku(),
                'qty' => $qty,
                'name' => $product->getName(),
                'brand' => $product->getBrand()?->getName(),
                'color' => $variant->getSupplierColorName(),
                'imageUrl' => $this->variantImagePathResolver->fromVariant($variant),

                'price' => $price,
                'regularPrice' => (float) $variant->getPrice(),
                'compareAtPrice' => $variant->getCompareAtPrice() !== null
                    ? (float) $variant->getCompareAtPrice()
                    : null,

                'saleActive' => $variant->isSaleActive(),
                'saleBadge' => $variant->getDiscountBadge(),

                'productContext' => $product->getProductContext(),
                'productType' => $productType->value,

                'requiresShipping' => $requiresShipping,
                'couponEligible' => $couponEligible,

                'lineTotal' => $lineTotal,
            ];
        }

        $couponCode = $cart->getCouponCode();
        $couponResult = null;
        $discountAmount = 0.0;

        /*
        * Als er geen enkel artikel in de winkelwagen zit waarop
        * coupons zijn toegestaan, verwijderen we een eventueel
        * eerder opgeslagen coupon direct uit de sessie.
        */
        if (!$hasCouponEligibleItems && $couponCode !== null) {
            $cart->clearCouponCode();
            $couponCode = null;
        }

        if ($couponCode !== null) {
            $couponResult = $couponService->validateForCartItems(
                $couponCode,
                $cartItems
            );

            if (!$couponResult->isValid()) {
                $cart->clearCouponCode();

                $couponCode = null;
                $couponResult = null;
            } else {
                $discountAmount = $couponResult->getDiscountAmount();
            }
        }

        /*
        * Alleen fysieke/verzendbare artikelen tellen mee
        * voor de verzendkosten en gratis-verzendgrens.
        */
        $shippingCost = $shippingCalculator->calculate(
            $shippableSubtotal
        );

        $requiresShipping = $shippableSubtotal > 0.0;

        $grandTotal = max(
            0.0,
            $subtotal - $discountAmount + $shippingCost
        );

        return $this->render('cart/show.html.twig', [
            'items' => $cartItems,

            'subtotal' => $subtotal,
            'shippableSubtotal' => $shippableSubtotal,
            'requiresShipping' => $requiresShipping,

            'shippingCost' => $shippingCost,
            'freeShippingFrom' => $shippingCalculator->getFreeFrom(),

            'couponCode' => $couponCode,
            'couponResult' => $couponResult,
            'hasCouponEligibleItems' => $hasCouponEligibleItems,
            'discountAmount' => $discountAmount,

            'grandTotal' => $grandTotal,
        ]);
    }

    #[Route('/cart/add', name: 'cart_add', methods: ['POST'])]
    public function add(
        Request $request,
        CartService $cart,
        ProductVariantRepository $variantRepository,
        CartAvailabilityGuard $cartAvailabilityGuard
    ): Response {
        $sku = (string) $request->request->get('variantSku', '');
        $qty = max(1, (int) $request->request->get('qty', 1));

        if ($sku === '') {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Missing variantSku',
            ], 400);
        }

        $variant = $variantRepository->findOneBy([
            'variantSku' => $sku,
            'isActive' => true,
        ]);

        if ($variant === null) {
            return $this->handleCartError(
                $request,
                'Dit product is niet meer beschikbaar.',
                404,
                'cart_show'
            );
        }

        $product = $variant->getProduct();

        if ($product === null || !$product->isActive()) {
            return $this->handleCartError(
                $request,
                'Dit product is niet meer beschikbaar.',
                404,
                'cart_show'
            );
        }

        $currentQty = 0;

        foreach ($cart->all() as $item) {
            if (($item['sku'] ?? '') === $sku) {
                $currentQty = (int) ($item['qty'] ?? 0);
                break;
            }
        }

        $requestedQty = $currentQty + $qty;

        /*
         * Voorlopig blijft CartAvailabilityGuard hier staan.
         * Bij de volgende voorraadstap laten we deze zelf rekening
         * houden met ProductType::tracksStock().
         */
        $errorMessage = $cartAvailabilityGuard->getErrorMessage(
            $variant,
            $requestedQty
        );

        if ($errorMessage !== null) {
            return $this->handleCartError(
                $request,
                $errorMessage,
                422,
                'product_show',
                [
                    'slug' => $product->getSlug(),
                    'colorSlug' => $variant->getSupplierColorSlug() ?: 'default',
                    'variantSku' => $variant->getVariantSku(),
                ]
            );
        }

        $cart->add($sku, $qty);

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse([
                'ok' => true,
                'cartCount' => $cart->countItems(),
                'message' => 'Product toegevoegd aan winkelwagen.',
            ]);
        }

        $this->addFlash(
            'success',
            'Product toegevoegd aan winkelwagen.'
        );

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/update', name: 'cart_update', methods: ['POST'])]
    public function update(
        Request $request,
        CartService $cart,
        ProductVariantRepository $variantRepository,
        CartAvailabilityGuard $cartAvailabilityGuard
    ): Response {
        $sku = (string) $request->request->get('variantSku', '');
        $qty = (int) $request->request->get('qty', 1);

        if ($sku === '') {
            $this->addFlash(
                'error',
                'Geen variant geselecteerd.'
            );

            return $this->redirectToRoute('cart_show');
        }

        if ($qty <= 0) {
            $cart->remove($sku);

            $this->addFlash(
                'success',
                'Product verwijderd uit je winkelwagen.'
            );

            return $this->redirectToRoute('cart_show');
        }

        $variant = $variantRepository->findOneBy([
            'variantSku' => $sku,
            'isActive' => true,
        ]);

        if ($variant === null) {
            $cart->remove($sku);

            $this->addFlash(
                'error',
                'Dit product is niet meer beschikbaar en is uit je winkelwagen verwijderd.'
            );

            return $this->redirectToRoute('cart_show');
        }

        $errorMessage = $cartAvailabilityGuard->getErrorMessage(
            $variant,
            $qty
        );

        if ($errorMessage !== null) {
            $this->addFlash(
                'error',
                $errorMessage
            );

            return $this->redirectToRoute('cart_show');
        }

        $cart->setQty($sku, $qty);

        $this->addFlash(
            'success',
            'Aantal bijgewerkt.'
        );

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/remove', name: 'cart_remove', methods: ['POST'])]
    public function remove(
        Request $request,
        CartService $cart
    ): Response {
        $sku = (string) $request->request->get('variantSku', '');

        if ($sku !== '') {
            $cart->remove($sku);

            $this->addFlash(
                'success',
                'Product verwijderd uit je winkelwagen.'
            );
        }

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/coupon/apply', name: 'cart_coupon_apply', methods: ['POST'])]
    public function applyCoupon(
        Request $request,
        CartService $cart,
        ProductVariantRepository $variantRepository,
        CouponService $couponService
    ): Response {
        $code = trim(
            (string) $request->request->get('couponCode', '')
        );

        $rawItems = $cart->all();
        $cartItems = [];

        foreach ($rawItems as $row) {
            if (!isset($row['sku'], $row['qty'])) {
                continue;
            }

            $variant = $variantRepository->findOneBy([
                'variantSku' => $row['sku'],
                'isActive' => true,
            ]);

            if ($variant === null) {
                continue;
            }

            $product = $variant->getProduct();

            if ($product === null || !$product->isActive()) {
                continue;
            }

            $qty = max(1, (int) $row['qty']);
            $price = (float) $variant->getDisplayPrice();
            $lineTotal = $price * $qty;

            $productType = $product->getProductType();

            $cartItems[] = [
                'sku' => $variant->getVariantSku(),
                'qty' => $qty,
                'price' => $price,
                'saleActive' => $variant->isSaleActive(),
                'productContext' => $product->getProductContext(),
                'productType' => $productType->value,
                'requiresShipping' => $productType->requiresShipping(),
                'couponEligible' => $productType->couponEligible(),
                'lineTotal' => $lineTotal,
            ];
        }

        /*
         * De CouponService gebruikt couponEligible in deze stap
         * nog niet. Dat pakken we bewust hierna apart aan.
         */
        $result = $couponService->validateForCartItems(
            $code,
            $cartItems
        );

        if (!$result->isValid()) {
            $cart->clearCouponCode();

            $this->addFlash(
                'error',
                $result->getMessage()
                    ?? 'Deze couponcode is niet geldig.'
            );

            return $this->redirectToRoute('cart_show');
        }

        $appliedCode = $result->getCoupon()?->getCode()
            ?? $code;

        $cart->setCouponCode($appliedCode);

        $this->addFlash(
            'success',
            sprintf(
                'Coupon "%s" is toegevoegd.',
                $result->getCoupon()?->getCode()
                    ?? mb_strtoupper($code)
            )
        );

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/coupon/remove', name: 'cart_coupon_remove', methods: ['POST'])]
    public function removeCoupon(
        CartService $cart
    ): Response {
        $cart->clearCouponCode();

        $this->addFlash(
            'success',
            'Coupon is verwijderd.'
        );

        return $this->redirectToRoute('cart_show');
    }

    private function handleCartError(
        Request $request,
        string $message,
        int $statusCode = 400,
        string $fallbackRoute = 'cart_show',
        array $fallbackRouteParameters = []
    ): Response {
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse([
                'ok' => false,
                'message' => $message,
            ], $statusCode);
        }

        $this->addFlash(
            'error',
            $message
        );

        return $this->redirectToRoute(
            $fallbackRoute,
            $fallbackRouteParameters
        );
    }
}