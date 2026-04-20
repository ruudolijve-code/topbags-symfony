<?php

namespace App\Shop\Controller;

use App\Shop\Repository\OrderRepository;
use App\Shop\Service\CartService;
use App\Shop\Service\MollieService;
use App\Shop\Service\OrderService;
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
        OrderService $orderService
    ): Response {
        $order = $orderRepository->findOneBy([
            'orderNumber' => $orderNumber,
        ]);

        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        // 1. Normale situatie: webhook was al klaar
        if ($order->isPaid()) {
            if ($cart->countItems() > 0) {
                $cart->clear();
            }

            return $this->render('checkout/success.html.twig', [
                'order' => $order,
            ]);
        }

        // 2. Fallback: Mollie direct controleren bij terugkomst
        if ($order->getMolliePaymentId()) {
            $payment = $mollie->getPayment($order->getMolliePaymentId());

            if ($payment->isPaid()) {
                $orderService->markAsPaid($order);

                if ($cart->countItems() > 0) {
                    $cart->clear();
                }
            }
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
}