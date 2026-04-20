<?php

namespace App\Loyalty\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TravelmilesController extends AbstractController
{
    #[Route('/travelmiles', name: 'travelmiles_index')]
    public function index(): Response
    {
        return $this->render('travelmiles/index.html.twig');
    }
}