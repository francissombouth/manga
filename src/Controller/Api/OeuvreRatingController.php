<?php

namespace App\Controller\Api;

use App\Entity\OeuvreNote;
use App\Repository\OeuvreRepository;
use App\Repository\OeuvreNoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/oeuvres/{id}/rating')]
class OeuvreRatingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OeuvreRepository $oeuvreRepository,
        private OeuvreNoteRepository $noteRepository
    ) {
    }

    #[Route('', name: 'api_oeuvre_rating_get', methods: ['GET'])]
    public function getRating(int $id): JsonResponse
    {
        try {
            $oeuvre = $this->oeuvreRepository->find($id);
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $user = $this->getUser();
            $userRating = null;

            if ($user && $user instanceof \App\Entity\User) {
                $userNoteEntity = $this->noteRepository->findByUserAndOeuvre($user, $oeuvre);
                $userRating = $userNoteEntity ? $userNoteEntity->getNote() : null;
            }

            $averageRating = $this->noteRepository->getAverageNoteForOeuvre($oeuvre);
            $totalRatings = $this->noteRepository->countNotesForOeuvre($oeuvre);

            return $this->json([
                'rating' => $userRating,
                'average' => $averageRating,
                'totalRatings' => $totalRatings
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la récupération de la note',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'api_oeuvre_rating_post', methods: ['POST'])]
    public function setRating(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user instanceof \App\Entity\User) {
                return $this->json(['message' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
            }

            $oeuvre = $this->oeuvreRepository->find($id);
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['rating'])) {
                return $this->json(['message' => 'Note manquante'], Response::HTTP_BAD_REQUEST);
            }

            $ratingValue = (int)$data['rating'];

            if ($ratingValue < 1 || $ratingValue > 5) {
                return $this->json(['message' => 'La note doit être comprise entre 1 et 5'], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si l'utilisateur a déjà noté cette œuvre
            $existingNote = $this->noteRepository->findByUserAndOeuvre($user, $oeuvre);

            if ($existingNote) {
                // Mettre à jour la note existante
                $existingNote->setNote($ratingValue);
                $action = 'updated';
            } else {
                // Créer une nouvelle note
                $note = new OeuvreNote();
                $note->setUser($user);
                $note->setOeuvre($oeuvre);
                $note->setNote($ratingValue);
                $this->entityManager->persist($note);
                $action = 'created';
            }

            $this->entityManager->flush();

            // Calculer la nouvelle moyenne
            $averageRating = $this->noteRepository->getAverageNoteForOeuvre($oeuvre);
            $totalRatings = $this->noteRepository->countNotesForOeuvre($oeuvre);

            return $this->json([
                'success' => true,
                'action' => $action,
                'rating' => $ratingValue,
                'average' => $averageRating,
                'totalRatings' => $totalRatings
            ]);

        } catch (\Exception $e) {
            error_log('Erreur API rating œuvre: ' . $e->getMessage());
            
            return $this->json([
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'api_oeuvre_rating_delete', methods: ['DELETE'])]
    public function deleteRating(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user instanceof \App\Entity\User) {
                return $this->json(['message' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
            }

            $oeuvre = $this->oeuvreRepository->find($id);
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $existingNote = $this->noteRepository->findByUserAndOeuvre($user, $oeuvre);
            if (!$existingNote) {
                return $this->json(['message' => 'Aucune note trouvée'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($existingNote);
            $this->entityManager->flush();

            // Recalculer la moyenne
            $averageRating = $this->noteRepository->getAverageNoteForOeuvre($oeuvre);
            $totalRatings = $this->noteRepository->countNotesForOeuvre($oeuvre);

            return $this->json([
                'success' => true,
                'action' => 'deleted',
                'average' => $averageRating,
                'totalRatings' => $totalRatings
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la suppression de la note',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 