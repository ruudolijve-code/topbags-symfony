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

    #[Route('/garantie-reparatie', name: 'service_warranty', methods: ['GET'])]
    public function warranty(): Response
    {
        return $this->render('service/warranty.html.twig');
    }

    #[Route('/service/reparatie', name: 'service_repair')]
    public function repair(): Response
    {
        return $this->render('service/repair.html.twig');
    }

    #[Route('/service/privacy', name: 'service_privacy')]
    public function privacy(): Response
    {
        return $this->render('service/privacy.html.twig');
    }

    #[Route('/service/cookies', name: 'service_cookies')]
    public function cookies(): Response
    {
        return $this->render('service/cookies.html.twig');
    }
}