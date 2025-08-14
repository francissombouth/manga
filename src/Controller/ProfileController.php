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
            $collectionsCount = $entityManager->getRepository('App\Entity\CollectionUser')
                ->count(['user' => $user]);
                
            $statutsCount = $entityManager->getRepository('App\Entity\Statut')
                ->count(['user' => $user]);
        } catch (\Exception $e) {
            // En cas d'erreur, utiliser des valeurs par défaut
            $collectionsCount = 0;
            $statutsCount = 0;
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'collectionsCount' => $collectionsCount,
            'statutsCount' => $statutsCount,
        ]);
    }
} 
