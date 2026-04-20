<?php

namespace App\Shop\Controller;

use App\Shop\Repository\OrderRepository;
use App\Shop\Service\OrderMailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MailTestController extends AbstractController
{
    #[Route('/test-order-mail/{orderNumber}', name: 'test_order_mail', methods: ['GET'])]
    public function test(
        string $orderNumber,
        OrderRepository $orderRepository,
        OrderMailer $orderMailer
    ): Response {
        $order = $orderRepository->findOneBy([
            'orderNumber' => $orderNumber,
        ]);

        if (!$order) {
            return new Response('Order niet gevonden', 404);
        }

        $orderMailer->sendCustomerConfirmation($order);
        $orderMailer->sendAdminNotification($order);

        return new Response('Ordermails verzonden voor ' . $order->getOrderNumber());
    }
}