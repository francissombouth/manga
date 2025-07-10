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

#[Route('/api/oeuvres/{oeuvreId}/notes')]
class OeuvreNoteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OeuvreRepository $oeuvreRepository,
        private OeuvreNoteRepository $noteRepository
    ) {
    }

    #[Route('', name: 'api_oeuvre_note_set', methods: ['POST'])]
    public function setNote(int $oeuvreId, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['message' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
            }

            $oeuvre = $this->oeuvreRepository->find($oeuvreId);
            if (!$oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['note'])) {
                return $this->json(['message' => 'Note manquante'], Response::HTTP_BAD_REQUEST);
            }

            $noteValue = (int)$data['note'];

            if ($noteValue < 1 || $noteValue > 5) {
                return $this->json(['message' => 'La note doit être comprise entre 1 et 5'], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si l'utilisateur a déjà noté cette œuvre
            $existingNote = $this->noteRepository->findByUserAndOeuvre($user, $oeuvre);

            if ($existingNote) {
                // Mettre à jour la note existante
                $existingNote->setNote($noteValue);
                $action = 'updated';
            } else {
                // Créer une nouvelle note
                $note = new OeuvreNote();
                $note->setUser($user);
                $note->setOeuvre($oeuvre);
                $note->setNote($noteValue);
                $this->entityManager->persist($note);
                $action = 'created';
            }

            $this->entityManager->flush();

            // Calculer la nouvelle moyenne
            $averageNote = $this->noteRepository->getAverageNoteForOeuvre($oeuvre);
            $totalNotes = $this->noteRepository->countNotesForOeuvre($oeuvre);

            return $this->json([
                'success' => true,
                'action' => $action,
                'userNote' => $noteValue,
                'averageNote' => $averageNote,
                'totalNotes' => $totalNotes
            ]);

        } catch (\Exception $e) {
            error_log('Erreur API note œuvre: ' . $e->getMessage());
            
            return $this->json([
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'api_oeuvre_notes_get', methods: ['GET'])]
    public function getNotes(int $oeuvreId): JsonResponse
    {
        try {
            $oeuvre = $this->oeuvreRepository->find($oeuvreId);
            if (!$oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $averageNote = $this->noteRepository->getAverageNoteForOeuvre($oeuvre);
            $totalNotes = $this->noteRepository->countNotesForOeuvre($oeuvre);
            $userNote = null;

            $user = $this->getUser();
            if ($user) {
                $userNoteEntity = $this->noteRepository->findByUserAndOeuvre($user, $oeuvre);
                $userNote = $userNoteEntity ? $userNoteEntity->getNote() : null;
            }

            return $this->json([
                'averageNote' => $averageNote,
                'totalNotes' => $totalNotes,
                'userNote' => $userNote
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la récupération des notes',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'api_oeuvre_note_delete', methods: ['DELETE'])]
    public function deleteNote(int $oeuvreId): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['message' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
            }

            $oeuvre = $this->oeuvreRepository->find($oeuvreId);
            if (!$oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $existingNote = $this->noteRepository->findByUserAndOeuvre($user, $oeuvre);
            if (!$existingNote) {
                return $this->json(['message' => 'Aucune note trouvée'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($existingNote);
            $this->entityManager->flush();

            // Recalculer la moyenne
            $averageNote = $this->noteRepository->getAverageNoteForOeuvre($oeuvre);
            $totalNotes = $this->noteRepository->countNotesForOeuvre($oeuvre);

            return $this->json([
                'success' => true,
                'action' => 'deleted',
                'averageNote' => $averageNote,
                'totalNotes' => $totalNotes
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la suppression de la note',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 