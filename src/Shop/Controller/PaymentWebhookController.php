<?php

namespace App\Shop\Controller;

use App\Shop\Service\MollieService;
use App\Shop\Service\OrderMailer;
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
        OrderMailer $orderMailer,
        LoggerInterface $logger
    ): Response {
        $paymentId = $request->request->get('id');

        if (!$paymentId) {
            $logger->warning('Webhook zonder payment id');
            return new Response('', 200);
        }

        try {
            $payment = $mollie->getPayment($paymentId);
        } catch (\Throwable $e) {
            $logger->error('Mollie webhook fetch error', [
                'paymentId' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return new Response('', 200);
        }

        $order = $orderService->findByMolliePaymentId($paymentId);

        if (!$order) {
            $logger->warning('Order niet gevonden voor payment', [
                'paymentId' => $paymentId,
            ]);

            return new Response('', 200);
        }

        if ($order->isPaid()) {
            $logger->info('Webhook duplicate (already paid)', [
                'paymentId' => $paymentId,
                'order' => $order->getOrderNumber(),
            ]);

            return new Response('', 200);
        }

        if ($payment->isPaid()) {
            $logger->info('Start paid verwerking', [
                'order' => $order->getOrderNumber(),
                'paymentId' => $paymentId,
            ]);

            $orderService->markAsPaid($order);
            $orderService->decreaseStock($order);

            try {
                $logger->info('Start klantmail', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $paymentId,
                ]);

                $orderMailer->sendCustomerConfirmation($order);

                $logger->info('Klantmail verzonden', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $paymentId,
                ]);

                $orderMailer->sendAdminNotification($order);

                $logger->info('Adminmail verzonden', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $paymentId,
                ]);
            } catch (\Throwable $e) {
                $logger->error('Ordermail versturen mislukt', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $paymentId,
                    'error' => $e->getMessage(),
                ]);
            }

            $logger->info('Order betaald via Mollie', [
                'order' => $order->getOrderNumber(),
                'paymentId' => $paymentId,
            ]);
        } elseif ($payment->isCanceled() || $payment->isExpired()) {
            $orderService->markAsCancelled($order);

            $logger->info('Order geannuleerd/verlopen', [
                'order' => $order->getOrderNumber(),
                'paymentId' => $paymentId,
            ]);
        } elseif ($payment->isFailed()) {
            $orderService->markAsFailed($order);

            $logger->info('Order betaling mislukt', [
                'order' => $order->getOrderNumber(),
                'paymentId' => $paymentId,
            ]);
        }

        return new Response('', 200);
    }
}