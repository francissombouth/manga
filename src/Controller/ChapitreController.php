<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Repository\ChapitreRepository;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/chapitres')]
class ChapitreController extends AbstractController
{
    public function __construct(
        private ChapitreRepository $chapitreRepository,
        private OeuvreRepository $oeuvreRepository,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/oeuvre/{oeuvreId}', name: 'app_chapitre_list', methods: ['GET'])]
    public function list(int $oeuvreId, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $chapitres = $this->chapitreRepository->findByOeuvre($oeuvreId);
        $total = count($chapitres);
        $chapitres = array_slice($chapitres, ($page - 1) * $limit, $limit);

        return $this->json([
            'items' => $chapitres,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ], Response::HTTP_OK, [], ['groups' => 'chapitre:read']);
    }

    #[Route('/{id}', name: 'app_chapitre_show', methods: ['GET'])]
    public function show(int $id, Request $request): Response
    {
        $chapitre = $this->chapitreRepository->find($id);

        if (!$chapitre || !$chapitre instanceof \App\Entity\Chapitre) {
            // Si c'est une requête AJAX ou avec Accept: application/json, retourner JSON
            if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', '') ?? '', 'application/json')) {
                return $this->json(['message' => 'Chapitre non trouvé'], Response::HTTP_NOT_FOUND);
            }
            throw $this->createNotFoundException('Chapitre non trouvé');
        }

        // Si c'est une requête AJAX ou avec Accept: application/json, retourner JSON
        if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', '') ?? '', 'application/json')) {
            return $this->json($chapitre, Response::HTTP_OK, [], ['groups' => 'chapitre:read']);
        }

        // Sinon, afficher la vue HTML
        return $this->render('chapitre/show.html.twig', [
            'chapitre' => $chapitre,
        ]);
    }

    #[Route('', name: 'app_chapitre_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $chapitre = new Chapitre();
        $chapitre->setTitre($data['titre'] ?? '');
        $chapitre->setOrdre($data['ordre'] ?? $data['numero'] ?? 1);
        $chapitre->setResume($data['resume'] ?? null);
        $chapitre->setPages($data['pages'] ?? []);

        if (isset($data['oeuvre_id'])) {
            $oeuvre = $this->oeuvreRepository->find($data['oeuvre_id']);
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $chapitre->setOeuvre($oeuvre);
        }

        $errors = $this->validator->validate($chapitre);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->chapitreRepository->save($chapitre, true);

        return $this->json($chapitre, Response::HTTP_CREATED, [], ['groups' => 'chapitre:read']);
    }

    #[Route('/{id}', name: 'app_chapitre_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $chapitre = $this->chapitreRepository->find($id);

        if (!$chapitre || !$chapitre instanceof \App\Entity\Chapitre) {
            return $this->json(['message' => 'Chapitre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['titre'])) {
            $chapitre->setTitre($data['titre']);
        }
        if (isset($data['ordre']) || isset($data['numero'])) {
            $chapitre->setOrdre($data['ordre'] ?? $data['numero']);
        }
        if (isset($data['resume'])) {
            $chapitre->setResume($data['resume']);
        }
        if (isset($data['pages'])) {
            $chapitre->setPages($data['pages']);
        }
        if (isset($data['oeuvre_id'])) {
            $oeuvre = $this->oeuvreRepository->find($data['oeuvre_id']);
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $chapitre->setOeuvre($oeuvre);
        }

        $errors = $this->validator->validate($chapitre);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->chapitreRepository->save($chapitre, true);

        return $this->json($chapitre, Response::HTTP_OK, [], ['groups' => 'chapitre:read']);
    }

    #[Route('/{id}', name: 'app_chapitre_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $chapitre = $this->chapitreRepository->find($id);

        if (!$chapitre || !$chapitre instanceof \App\Entity\Chapitre) {
            return $this->json(['message' => 'Chapitre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->chapitreRepository->remove($chapitre, true);

        return $this->json(['message' => 'Chapitre supprimé avec succès']);
    }

    #[Route('/{id}/next', name: 'app_chapitre_next', methods: ['GET'])]
    public function next(int $id): JsonResponse
    {
        $chapitre = $this->chapitreRepository->find($id);

        if (!$chapitre || !$chapitre instanceof \App\Entity\Chapitre) {
            return $this->json(['message' => 'Chapitre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $nextChapitre = $this->chapitreRepository->findNextChapitre($chapitre);

        if (!$nextChapitre) {
            return $this->json(['message' => 'Aucun chapitre suivant'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($nextChapitre, Response::HTTP_OK, [], ['groups' => 'chapitre:read']);
    }

    #[Route('/{id}/previous', name: 'app_chapitre_previous', methods: ['GET'])]
    public function previous(int $id): JsonResponse
    {
        $chapitre = $this->chapitreRepository->find($id);

        if (!$chapitre || !$chapitre instanceof \App\Entity\Chapitre) {
            return $this->json(['message' => 'Chapitre non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $previousChapitre = $this->chapitreRepository->findPreviousChapitre($chapitre);

        if (!$previousChapitre) {
            return $this->json(['message' => 'Aucun chapitre précédent'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($previousChapitre, Response::HTTP_OK, [], ['groups' => 'chapitre:read']);
    }
} 