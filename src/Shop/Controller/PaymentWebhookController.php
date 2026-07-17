<?php

namespace App\Shop\Controller;

use App\Shop\Service\MollieService;
use App\Shop\Service\OrderService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentWebhookController extends AbstractController
{
    #[Route('/payment/webhook', name: 'payment_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        MollieService $mollie,
        OrderService $orderService,
        LoggerInterface $logger
    ): Response {
        $paymentId = $request->request->get('id');

        if (!$paymentId) {
            $logger->warning('Webhook zonder payment id');

            return new Response('', Response::HTTP_OK);
        }

        try {
            $payment = $mollie->getPayment($paymentId);
        } catch (\Throwable $e) {
            $logger->error('Mollie webhook fetch error', [
                'paymentId' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            // Tijdelijke fout: laat Mollie de webhook opnieuw proberen.
            return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $order = $orderService->findByMolliePaymentId($paymentId);

        if (!$order) {
            $logger->warning('Order niet gevonden voor Mollie payment', [
                'paymentId' => $paymentId,
            ]);

            // Payment bestaat wel, maar wij kunnen hem niet verwerken.
            // Een retry kan helpen als dit door timing/transaction race ontstaat.
            return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            if ($payment->isPaid()) {
                $logger->info('Start paid verwerking', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $paymentId,
                ]);

                $orderService->processPaidOrder($order);

                $logger->info('Order betaald via Mollie verwerkt', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $paymentId,
                ]);
            } elseif ($payment->isCanceled()) {
                $orderService->markAsCancelled($order);

                $logger->info('Orderbetaling geannuleerd', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $paymentId,
                ]);
            } elseif ($payment->isExpired()) {
                $orderService->markAsExpired($order);

                $logger->info('Orderbetaling verlopen', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $paymentId,
                ]);
            } elseif ($payment->isFailed()) {
                $orderService->markAsFailed($order);

                $logger->info('Orderbetaling mislukt', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $paymentId,
                ]);
            }
        } catch (\Throwable $e) {
            $logger->error('Mollie webhook verwerking mislukt', [
                'order' => $order->getOrderNumber(),
                'paymentId' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            // Belangrijk: geen 200 geven.
            // processPaidOrder() is idempotent, dus een retry is veilig.
            return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response('', Response::HTTP_OK);
    }
}