<?php

namespace App\Shop\Controller;

use App\Shop\Repository\OrderRepository;
use App\Shop\Service\CartService;
use App\Shop\Service\MollieService;
use App\Shop\Service\OrderService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/order/{orderNumber}', name: 'order_success', methods: ['GET'])]
    public function success(
        string $orderNumber,
        OrderRepository $orderRepository,
        CartService $cart,
        MollieService $mollie,
        OrderService $orderService,
        LoggerInterface $logger
    ): Response {
        $order = $orderRepository->findOneBy([
            'orderNumber' => $orderNumber,
        ]);

        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        // 1. Normale situatie: betaling is al verwerkt.
        if ($order->isPaid()) {
            if ($cart->countItems() > 0) {
                $cart->clear();
            }

            return $this->render('checkout/success.html.twig', [
                'order' => $order,
            ]);
        }

        // 2. Fallback: controleer Mollie direct wanneer de klant terugkomt
        // voordat de webhook de betaling volledig heeft verwerkt.
        if ($order->getMolliePaymentId()) {
            try {
                $payment = $mollie->getPayment($order->getMolliePaymentId());

                if ($payment->isPaid()) {
                    try {
                        $orderService->processPaidOrder($order);
                    } catch (\Throwable $e) {
                        $logger->error('Fallback verwerking betaalde order mislukt', [
                            'order' => $order->getOrderNumber(),
                            'paymentId' => $order->getMolliePaymentId(),
                            'error' => $e->getMessage(),
                        ]);
                    }

                    if ($cart->countItems() > 0) {
                        $cart->clear();
                    }
                }
            } catch (\Throwable $e) {
                $logger->error('Fallback Mollie status ophalen mislukt', [
                    'order' => $order->getOrderNumber(),
                    'paymentId' => $order->getMolliePaymentId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
}