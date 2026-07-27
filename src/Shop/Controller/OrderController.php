<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Shop\Entity\Order;
use App\Shop\Repository\OrderRepository;
use App\Shop\Service\CartService;
use App\Shop\Service\MollieService;
use App\Shop\Service\OrderService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    private const GOOGLE_CUSTOMER_REVIEWS_MERCHANT_ID = 112478191;

    #[Route('/order/{orderNumber}', name: 'order_success', methods: ['GET'])]
    public function success(
        string $orderNumber,
        Request $request,
        OrderRepository $orderRepository,
        CartService $cart,
        MollieService $mollie,
        OrderService $orderService,
        LoggerInterface $logger
    ): Response {
        $order = $orderRepository->findOneBy([
            'orderNumber' => $orderNumber,
        ]);

        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found.');
        }

        /*
         * Fallback: controleer Mollie wanneer de klant terugkomt
         * voordat de webhook de betaling heeft verwerkt.
         */
        if (!$order->isPaid() && $order->getMolliePaymentId()) {
            try {
                $payment = $mollie->getPayment(
                    $order->getMolliePaymentId()
                );

                if ($payment->isPaid()) {
                    try {
                        $orderService->processPaidOrder($order);
                    } catch (\Throwable $e) {
                        $logger->error(
                            'Fallback verwerking betaalde order mislukt',
                            [
                                'order' => $order->getOrderNumber(),
                                'paymentId' => $order->getMolliePaymentId(),
                                'error' => $e->getMessage(),
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                $logger->error(
                    'Fallback Mollie status ophalen mislukt',
                    [
                        'order' => $order->getOrderNumber(),
                        'paymentId' => $order->getMolliePaymentId(),
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        /*
         * Wis de winkelwagen pas wanneer de order daadwerkelijk betaald is.
         */
        if ($order->isPaid() && $cart->countItems() > 0) {
            $cart->clear();
        }

        /*
         * Deze sessievlag wordt in CheckoutController gezet voordat
         * de klant naar Mollie wordt doorgestuurd.
         */
        $checkoutSessionKey = sprintf(
            'completed_checkout_order_%s',
            $order->getOrderNumber()
        );

        /*
         * De vlag wordt alleen verwijderd als de betaling verwerkt is.
         * Daardoor blijft hij bestaan wanneer Mollie nog niet als betaald
         * terugkomt en de klant de pagina opnieuw moet laden.
         */
        $showCheckoutTracking =
            $order->isPaid()
            && $request->getSession()->remove($checkoutSessionKey) === true;

        $shippingAddress = $order->getShippingAddress();

        $deliveryCountry = $this->resolveDeliveryCountry(
            $shippingAddress['countryCode']
                ?? $shippingAddress['country']
                ?? null
        );

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
            'showCheckoutTracking' => $showCheckoutTracking,
            'googleCustomerReviewsMerchantId' =>
                self::GOOGLE_CUSTOMER_REVIEWS_MERCHANT_ID,
            'googleCustomerReviewsDeliveryCountry' =>
                $deliveryCountry,
            'googleCustomerReviewsEstimatedDeliveryDate' =>
                $this->calculateEstimatedDeliveryDate($order)->format('Y-m-d'),
        ]);
    }

    private function resolveDeliveryCountry(?string $country): string
    {
        $country = mb_strtolower(
            trim((string) $country)
        );

        return match ($country) {
            'be',
            'belgië',
            'belgie',
            'belgium' => 'BE',

            'de',
            'duitsland',
            'duitsland ',
            'deutschland',
            'germany' => 'DE',

            default => 'NL',
        };
    }

    private function calculateEstimatedDeliveryDate(
        Order $order
    ): \DateTimeImmutable {
        /*
         * Voor winkelafhaling gebruiken we drie werkdagen.
         * Voor verzending voorlopig tien werkdagen, zodat Google
         * de enquête niet verstuurt voordat een leverancierartikel
         * bij de klant is aangekomen.
         */
        $numberOfWeekdays = $order->isStorePickup() ? 3 : 10;

        $date = new \DateTimeImmutable('today');
        $addedWeekdays = 0;

        while ($addedWeekdays < $numberOfWeekdays) {
            $date = $date->modify('+1 day');

            if ((int) $date->format('N') <= 5) {
                ++$addedWeekdays;
            }
        }

        return $date;
    }
}