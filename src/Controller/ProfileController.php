<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        try {
            // Charger explicitement les relations pour éviter les problèmes de lazy loading
            $favorisCount = $entityManager->getRepository('App\Entity\CollectionUser')
                ->count(['user' => $user]);
                
            $enCoursCount = $entityManager->getRepository('App\Entity\Statut')
                ->count(['user' => $user, 'nom' => 'En cours']);
                
            $termineeCount = $entityManager->getRepository('App\Entity\Statut')
                ->count(['user' => $user, 'nom' => 'Terminée']);
        } catch (\Exception $e) {
            // En cas d'erreur, utiliser des valeurs par défaut
            $favorisCount = 0;
            $enCoursCount = 0;
            $termineeCount = 0;
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'favorisCount' => $favorisCount,
            'enCoursCount' => $enCoursCount,
            'termineeCount' => $termineeCount,
        ]);
    }
} 
