<?php

namespace App\Shop\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShopController extends AbstractController
{
    /**
     * Cart overzicht
     */
    #[Route('/cart', name: 'cart_index', methods: ['GET'])]
    public function cart(): Response
    { 
        return $this->render('cart/index.html.twig');
    }

    /**
     * Order bevestiging
     */
    #[Route('/order/complete', name: 'order_complete', methods: ['GET'])]
    public function complete(): Response
    {
        return $this->render('shop/order/complete.html.twig');
    }
}
