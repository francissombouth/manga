<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Repository\OeuvreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Rediriger vers les œuvres (qui seront alimentées par l'API automatiquement)
        return $this->redirectToRoute('app_oeuvre_list');
    }
} 