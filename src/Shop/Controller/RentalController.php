<?php

namespace App\Shop\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RentalController extends AbstractController
{
    #[Route('/koffer-huren', name: 'rental_index')]
    public function index(): Response
    {
        return $this->render('rental/index.html.twig');
    }
}