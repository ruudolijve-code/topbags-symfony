<?php

namespace App\Content\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ServicePageController extends AbstractController
{
    #[Route('/ruilen-retourneren', name: 'service_returns', methods: ['GET'])]
    public function returns(): Response
    {
        return $this->render('service/returns.html.twig');
    }

    #[Route('/levering-verzending', name: 'service_shipping', methods: ['GET'])]
    public function shipping(): Response
    {
        return $this->render('service/shipping.html.twig');
    }

    #[Route('/betaalmethoden', name: 'service_payment_methods', methods: ['GET'])]
    public function paymentMethods(): Response
    {
        return $this->render('service/payment_methods.html.twig');
    }

    #[Route('/garantie-reparatie', name: 'service_warranty', methods: ['GET'])]
    public function warranty(): Response
    {
        return $this->render('service/warranty.html.twig');
    }

    #[Route('/service/reparatie', name: 'service_repair', methods: ['GET'])]
    public function repair(): Response
    {
        return $this->redirectToRoute('service_warranty', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/algemene-voorwaarden', name: 'service_terms', methods: ['GET'])]
    public function terms(): Response
    {
        return $this->render('service/terms.html.twig');
    }

    #[Route('/service/privacy', name: 'service_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('service/privacy.html.twig');
    }

    #[Route('/service/cookies', name: 'service_cookies', methods: ['GET'])]
    public function cookies(): Response
    {
        return $this->render('service/cookies.html.twig');
    }

    #[Route('/winkel', name: 'service_store', methods: ['GET'])]
    public function store(): Response
    {
        return $this->render('service/store.html.twig');
    }
}