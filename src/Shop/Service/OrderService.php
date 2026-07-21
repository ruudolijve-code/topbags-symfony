<?php

namespace App\Shop\Service;

use App\Account\Entity\CustomerUser;
use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\AvailabilityService;
use App\Shop\Entity\Order;
use App\Shop\Entity\OrderItem;
use App\Shop\Repository\CouponRepository;
use App\Shop\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderService
{
    private const STORE_PICKUP_NAME = 'Holtkamp tassen & koffers';
    private const STORE_PICKUP_STREET = 'Wemenstraat 5-7';
    private const STORE_PICKUP_POSTAL_CODE = '7551 ET';
    private const STORE_PICKUP_CITY = 'Hengelo';
    private const STORE_PICKUP_COUNTRY = 'NL';

    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository,
        private ProductVariantRepository $variantRepository,
        private AvailabilityService $availabilityService,
        private CouponRepository $couponRepository,
        private OrderMailer $orderMailer
    ) {
    }

    /**
     * @param array<int, array{
     *   sku: string,
     *   name: string,
     *   price: float,
     *   qty: int
     * }> $cartItems
     */
    public function createOrder(
        array $cartItems,
        array $customerData,
        float $shippingCost = 0.0,
        float $discountAmount = 0.0,
        ?string $couponCode = null,
        ?CustomerUser $customerUser = null
    ): Order {
        $order = new Order();

        $order
            ->setOrderNumber($this->generateOrderNumber())
            ->setStatus(Order::STATUS_PENDING_PAYMENT)
            ->setCustomerEmail((string) ($customerData['email'] ?? ''))
            ->setCustomerPhone($customerData['phone'] ?? null)
            ->setCustomerUser($customerUser)
            ->setShippingAddress($customerData['address'] ?? [])
            ->setShippingCost($shippingCost)
            ->setDiscountAmount($discountAmount)
            ->setCouponCode($couponCode);

        $shippingMethod = $customerData['shipping']['method'] ?? Order::SHIPPING_METHOD_HOME;
        $order->setShippingMethod($shippingMethod);

        match ($shippingMethod) {
            Order::SHIPPING_METHOD_PICKUP => $this->applyPostNlPickupData(
                $order,
                $customerData['shipping']['pickup'] ?? []
            ),
            Order::SHIPPING_METHOD_STORE_PICKUP => $this->applyStorePickupData($order),
            default => null,
        };

        $subtotal = 0.0;

        foreach ($cartItems as $item) {
            $sku = trim((string) ($item['sku'] ?? ''));

            if ($sku === '') {
                continue;
            }

            $qty = max(1, (int) ($item['qty'] ?? 1));
            $price = (float) ($item['price'] ?? 0.0);
            $lineTotal = $price * $qty;

            $variant = $this->variantRepository->findOneBy([
                'variantSku' => $sku,
            ]);

            $product = $variant?->getProduct();

            $productName = $product?->getName() ?: (string) ($item['name'] ?? 'Onbekend product');
            $brandName = $product?->getBrand()?->getName();
            $supplierColorName = $variant?->getSupplierColorName();

            $orderItem = (new OrderItem())
                ->setProductName($productName)
                ->setBrandName($brandName)
                ->setSupplierColorName($supplierColorName)
                ->setVariantSku($sku)
                ->setPrice($price)
                ->setQty($qty)
                ->setLineTotal($lineTotal);

            $order->addItem($orderItem);

            $subtotal += $lineTotal;
        }

        $order->setSubtotal($subtotal);

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    public function attachMolliePaymentId(Order $order, string $paymentId): void
    {
        $order->setMolliePaymentId($paymentId);
        $this->em->flush();
    }

    public function findByMolliePaymentId(string $paymentId): ?Order
    {
        return $this->orderRepository->findOneBy([
            'molliePaymentId' => $paymentId,
        ]);
    }

    public function processPaidOrder(Order $order): void
    {
        // 1. Order als betaald markeren.
        if ($order->getPaidAt() === null) {
            $order->markAsPaid();
            $this->em->flush();
        }

        // 2. Coupon éénmalig verwerken.
        if ($order->getCouponProcessedAt() === null) {
            $couponCode = $order->getCouponCode();

            if ($couponCode !== null && $couponCode !== '') {
                $coupon = $this->couponRepository->findOneBy([
                    'code' => $couponCode,
                ]);

                if ($coupon !== null) {
                    $coupon->incrementTimesRedeemed();
                }
            }

            $order->setCouponProcessedAt(new \DateTimeImmutable());
            $this->em->flush();
        }

        // 3. Voorraad éénmalig afboeken.
        if ($order->getStockProcessedAt() === null) {
            foreach ($order->getItems() as $item) {
                $variant = $this->variantRepository->findOneBy([
                    'variantSku' => $item->getVariantSku(),
                    'isActive' => true,
                ]);

                if ($variant === null) {
                    continue;
                }

                $stock = $variant->getStock();

                if ($stock === null) {
                    continue;
                }

                $stock->decrease($item->getQty());
                $this->availabilityService->invalidate($variant);
            }

            $order->setStockProcessedAt(new \DateTimeImmutable());
            $this->em->flush();
        }

        // 4. Klantmail onafhankelijk verwerken.
        if ($order->getConfirmationEmailSentAt() === null) {
            $this->orderMailer->sendCustomerConfirmation($order);

            $order->setConfirmationEmailSentAt(new \DateTimeImmutable());
            $this->em->flush();
        }

        // 5. Adminmail onafhankelijk verwerken.
        if ($order->getAdminEmailSentAt() === null) {
            $this->orderMailer->sendAdminNotification($order);

            $order->setAdminEmailSentAt(new \DateTimeImmutable());
            $this->em->flush();
        }
    }

    public function markAsPaid(Order $order): void
    {
        if ($order->isPaid()) {
            return;
        }

        $order->markAsPaid();

        $couponCode = $order->getCouponCode();

        if ($couponCode !== null && $couponCode !== '') {
            $coupon = $this->couponRepository->findOneBy([
                'code' => $couponCode,
            ]);

            if ($coupon !== null) {
                $coupon->incrementTimesRedeemed();
            }
        }

        $this->em->flush();
    }

    public function markAsShipped(
        Order $order,
        ?string $trackingCode = null,
        ?string $trackingUrl = null
    ): void {
        if ($trackingCode !== null) {
            $order->setTrackingCode($trackingCode);
        }

        if ($trackingUrl !== null) {
            $order->setTrackingUrl($trackingUrl);
        }

        $order->markAsShipped();

        // Eerst vastleggen dat het pakket daadwerkelijk is verzonden.
        // Een eventuele mailfout mag de verzendstatus niet terugdraaien.
        $this->em->flush();

        // Alleen automatisch versturen als nog geen succesvolle
        // verzendbevestiging geregistreerd is.
        if ($order->getShipmentEmailSentAt() === null) {
            try {
                $this->sendShipmentNotification($order);
            } catch (\Throwable) {
                // De fout is al geregistreerd door sendShipmentNotification().
                // De order blijft terecht op 'shipped' staan.
            }
        }
    }

    public function updateTracking(
        Order $order,
        ?string $trackingCode = null,
        ?string $trackingUrl = null
    ): void {
        $order
            ->setTrackingCode($trackingCode)
            ->setTrackingUrl($trackingUrl);

        $this->em->flush();
    }

    public function markAsCancelled(Order $order): void
    {
        if ($order->getStatus() === Order::STATUS_CANCELLED) {
            return;
        }

        $order->markAsCancelled();
        $this->em->flush();
    }

    public function markAsExpired(Order $order): void
    {
        if ($order->getStatus() === Order::STATUS_EXPIRED) {
            return;
        }

        $order->markAsExpired();
        $this->em->flush();
    }

    public function markAsFailed(Order $order): void
    {
        if ($order->getStatus() === Order::STATUS_FAILED) {
            return;
        }

        $order->setStatus(Order::STATUS_FAILED);
        $this->em->flush();
    }

    public function decreaseStock(Order $order): void
    {
        foreach ($order->getItems() as $item) {
            $variant = $this->variantRepository->findOneBy([
                'variantSku' => $item->getVariantSku(),
                'isActive' => true,
            ]);

            if ($variant === null) {
                continue;
            }

            $stock = $variant->getStock();

            if ($stock === null) {
                continue;
            }

            $stock->decrease($item->getQty());
            $this->availabilityService->invalidate($variant);
        }

        $this->em->flush();
    }

    public function sendShipmentNotification(Order $order): void
    {
        try {
            $this->orderMailer->sendShipmentNotification($order);

            $order->markShipmentEmailAsSent(
                $order->getCustomerEmail()
            );

            $this->em->flush();
        } catch (\Throwable $e) {
            $order->markShipmentEmailAsFailed(
                $e->getMessage()
            );

            $this->em->flush();

            throw $e;
        }
    }

    /**
     * @param array{
     *   locationCode?: string,
     *   retailNetworkId?: string,
     *   name?: string,
     *   street?: string,
     *   houseNumber?: string,
     *   postalCode?: string,
     *   city?: string
     * } $pickup
     */
    private function applyPostNlPickupData(Order $order, array $pickup): void
    {
        $order
            ->setPickupLocationCode($pickup['locationCode'] ?? null)
            ->setPickupRetailNetworkId($pickup['retailNetworkId'] ?? null)
            ->setPickupPointName($pickup['name'] ?? null)
            ->setPickupStreet($pickup['street'] ?? null)
            ->setPickupHouseNumber($pickup['houseNumber'] ?? null)
            ->setPickupPostalCode($pickup['postalCode'] ?? null)
            ->setPickupCity($pickup['city'] ?? null);
    }

    private function applyStorePickupData(Order $order): void
    {
        $order
            ->setStorePickupName(self::STORE_PICKUP_NAME)
            ->setStorePickupStreet(self::STORE_PICKUP_STREET)
            ->setStorePickupPostalCode(self::STORE_PICKUP_POSTAL_CODE)
            ->setStorePickupCity(self::STORE_PICKUP_CITY)
            ->setStorePickupCountry(self::STORE_PICKUP_COUNTRY);
    }

    private function generateOrderNumber(): string
    {
        return 'TB-' . date('Ymd') . '-' . random_int(1000, 9999);
    }
}