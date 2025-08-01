<?php

namespace App\Controller\Api;

use App\Entity\Commentaire;
use App\Entity\Oeuvre;
use App\Repository\OeuvreRepository;
use App\Repository\OeuvreNoteRepository;
use App\Repository\CommentaireLikeRepository;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/commentaires')]
class CommentaireController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OeuvreRepository $oeuvreRepository,
        private OeuvreNoteRepository $noteRepository,
        private CommentaireLikeRepository $likeRepository,
        private CommentaireRepository $commentaireRepository
    ) {
    }

    #[Route('/oeuvre/{id}', name: 'api_commentaires_list', methods: ['GET'])]
    public function getCommentaires(int $id): JsonResponse
    {
        $oeuvre = $this->oeuvreRepository->find($id);
        
        if (!$oeuvre) {
            return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$oeuvre instanceof \App\Entity\Oeuvre) {
            return $this->json(['message' => 'Type d\'œuvre invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer seulement les commentaires principaux (sans parent)
        $commentaires = array_filter($oeuvre->getCommentaires()->toArray(), fn($c) => $c->getParent() === null);
        usort($commentaires, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        
        // Récupérer les informations de notation séparées
        $averageNote = $this->noteRepository->getAverageNoteForOeuvre($oeuvre);
        $totalNotes = $this->noteRepository->countNotesForOeuvre($oeuvre);

        $user = $this->getUser();
        $commentairesData = [];
        foreach ($commentaires as $commentaire) {
            $commentairesData[] = $this->formatCommentaire($commentaire, $user instanceof \App\Entity\User ? $user : null);
        }

        return $this->json([
            'commentaires' => $commentairesData,
            'notes' => [
                'average' => $averageNote,
                'total' => $totalNotes
            ],
            'total' => count($commentaires)
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCommentaire(Commentaire $commentaire, ?\App\Entity\User $user): array
    {
        $likesCount = $this->likeRepository->countByCommentaire($commentaire);
        $isLikedByUser = false;
        
        if ($user) {
            $existingLike = $this->likeRepository->findByUserAndCommentaire($user, $commentaire);
            $isLikedByUser = $existingLike !== null;
        }

        // Formater les réponses
        $reponses = [];
        foreach ($commentaire->getReponses() as $reponse) {
            $reponses[] = $this->formatCommentaire($reponse, $user);
        }

        $createdAt = $commentaire->getCreatedAt();
        $auteur = $commentaire->getAuteur();

        return [
            'id' => $commentaire->getId(),
            'contenu' => $commentaire->getContenu(),
            'createdAt' => $createdAt ? $createdAt->format('d/m/Y à H:i') : 'Date inconnue',
            'auteur' => [
                'username' => $auteur ? $auteur->getNom() : 'Utilisateur inconnu',
                'initial' => $auteur && $auteur->getNom() ? strtoupper(substr($auteur->getNom(), 0, 1)) : '?'
            ],
            'likes' => [
                'count' => $likesCount,
                'isLikedByUser' => $isLikedByUser
            ],
            'reponses' => $reponses,
            'reponsesCount' => count($reponses),
            'isReponse' => $commentaire->isReponse(),
            'parentId' => $commentaire->getParent() ? $commentaire->getParent()->getId() : null
        ];
    }

    #[Route('/oeuvre/{id}', name: 'api_commentaires_create', methods: ['POST'])]
    public function createCommentaire(int $id, Request $request): JsonResponse
    {
        try {
            // Vérifier l'authentification
            $user = $this->getUser();
            if (!$user || !$user instanceof \App\Entity\User) {
                return $this->json(['message' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
            }

            $oeuvre = $this->oeuvreRepository->find($id);
            
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['contenu'])) {
                return $this->json(['message' => 'Contenu du commentaire requis'], Response::HTTP_BAD_REQUEST);
            }

            // Validation des données
            $contenu = trim($data['contenu']);

            if (empty($contenu)) {
                return $this->json(['message' => 'Le contenu du commentaire ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
            }

            $commentaire = new Commentaire();
            $commentaire->setContenu($contenu);
            $commentaire->setAuteur($user);
            $commentaire->setOeuvre($oeuvre);

            // Vérifier s'il s'agit d'une réponse
            if (isset($data['parentId']) && $data['parentId']) {
                $parentCommentaire = $this->entityManager->getRepository(Commentaire::class)->find($data['parentId']);
                if ($parentCommentaire instanceof \App\Entity\Commentaire && $parentCommentaire->getOeuvre() === $oeuvre) {
                    $commentaire->setParent($parentCommentaire);
                }
            }

            $this->entityManager->persist($commentaire);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Commentaire ajouté avec succès',
                'commentaire' => $this->formatCommentaire($commentaire, $user)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log('Erreur API commentaire: ' . $e->getMessage());
            
            return $this->json([
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage() // Temporaire pour le débogage
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{commentaireId}/repondre', name: 'api_commentaire_repondre', methods: ['POST'])]
    public function repondreCommentaire(int $commentaireId, Request $request): JsonResponse
    {
        try {
            // Vérifier l'authentification
            $user = $this->getUser();
            if (!$user || !$user instanceof \App\Entity\User) {
                return $this->json(['message' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
            }

            $parentCommentaire = $this->commentaireRepository->find($commentaireId);
            
            if (!$parentCommentaire || !$parentCommentaire instanceof \App\Entity\Commentaire) {
                return $this->json(['message' => 'Commentaire parent non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['contenu'])) {
                return $this->json(['message' => 'Contenu de la réponse requis'], Response::HTTP_BAD_REQUEST);
            }

            // Validation des données
            $contenu = trim($data['contenu']);

            if (empty($contenu)) {
                return $this->json(['message' => 'Le contenu de la réponse ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
            }

            $reponse = new Commentaire();
            $reponse->setContenu($contenu);
            $reponse->setAuteur($user);
            $reponse->setOeuvre($parentCommentaire->getOeuvre());
            $reponse->setParent($parentCommentaire);

            $this->entityManager->persist($reponse);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Réponse ajoutée avec succès',
                'commentaire' => $this->formatCommentaire($reponse, $user)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log('Erreur API réponse commentaire: ' . $e->getMessage());
            
            return $this->json([
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage() // Temporaire pour le débogage
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 