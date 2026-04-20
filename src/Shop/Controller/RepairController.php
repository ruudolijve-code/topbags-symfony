<?php

namespace App\Shop\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RepairController extends AbstractController
{
    #[Route('/repair', name: 'repair_index')]
    public function index(): Response
    {
        return $this->render('repair/index.html.twig');
    }
}