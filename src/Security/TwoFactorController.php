<?php

namespace App\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TwoFactorController extends AbstractController
{
    #[Route('/2fa', name: '2fa_login', methods: ['GET'])]
    public function form(): Response
    {
        return $this->render('security/2fa_form.html.twig');
    }

    #[Route('/2fa_check', name: '2fa_login_check', methods: ['POST'])]
    public function check(): never
    {
        throw new \LogicException('This code should never be reached.');
    }
}