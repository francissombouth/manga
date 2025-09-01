<?php

namespace App\Controller\Api;

use App\Entity\CommentaireLike;
use App\Repository\CommentaireRepository;
use App\Repository\CommentaireLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/commentaires/{commentaireId}/likes')]
class CommentaireLikeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CommentaireRepository $commentaireRepository,
        private CommentaireLikeRepository $likeRepository
    ) {
    }

    #[Route('', name: 'api_commentaire_like_toggle', methods: ['POST'])]
    public function toggleLike(int $commentaireId): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user instanceof \App\Entity\User) {
                return $this->json(['message' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
            }

            $commentaire = $this->commentaireRepository->find($commentaireId);
            if (!$commentaire || !$commentaire instanceof \App\Entity\Commentaire) {
                return $this->json(['message' => 'Commentaire non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier si l'utilisateur a déjà liké ce commentaire
            $existingLike = $this->likeRepository->findByUserAndCommentaire($user, $commentaire);

            if ($existingLike) {
                // Supprimer le like (unlike)
                $this->entityManager->remove($existingLike);
                $action = 'unliked';
            } else {
                // Ajouter un like
                $like = new CommentaireLike();
                $like->setUser($user);
                $like->setCommentaire($commentaire);
                $this->entityManager->persist($like);
                $action = 'liked';
            }

            $this->entityManager->flush();

            // Compter les likes après la modification
            $likesCount = $this->likeRepository->countByCommentaire($commentaire);
            $isLiked = $action === 'liked';

            return $this->json([
                'success' => true,
                'action' => $action,
                'likesCount' => $likesCount,
                'isLiked' => $isLiked
            ]);

        } catch (\Exception $e) {
            error_log('Erreur API like commentaire: ' . $e->getMessage());
            
            return $this->json([
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/count', name: 'api_commentaire_likes_count', methods: ['GET'])]
    public function getLikesCount(int $commentaireId): JsonResponse
    {
        try {
            $commentaire = $this->commentaireRepository->find($commentaireId);
            if (!$commentaire || !$commentaire instanceof \App\Entity\Commentaire) {
                return $this->json(['message' => 'Commentaire non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $likesCount = $this->likeRepository->countByCommentaire($commentaire);
            $isLiked = false;

            $user = $this->getUser();
            if ($user && $user instanceof \App\Entity\User) {
                $existingLike = $this->likeRepository->findByUserAndCommentaire($user, $commentaire);
                $isLiked = $existingLike !== null;
            }

            return $this->json([
                'likesCount' => $likesCount,
                'isLiked' => $isLiked
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la récupération des likes',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 