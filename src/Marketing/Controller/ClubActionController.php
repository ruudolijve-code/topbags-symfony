<?php

namespace App\Marketing\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class ClubActionController extends AbstractController
{
    #[Route('/clubactie', name: 'club_action', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('marketing/club_action.html.twig');
    }
}