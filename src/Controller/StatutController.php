<?php

namespace App\Controller;

use App\Entity\Statut;
use App\Repository\StatutRepository;
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

#[Route('/statuts')]
class StatutController extends AbstractController
{
    public function __construct(
        private StatutRepository $statutRepository,
        private OeuvreRepository $oeuvreRepository,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/user/{userId}', name: 'app_statut_list_by_user', methods: ['GET'])]
    public function listByUser(int $userId, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $statuts = $this->statutRepository->findByUser($userId);
        $total = count($statuts);
        $statuts = array_slice($statuts, ($page - 1) * $limit, $limit);

        return $this->json([
            'items' => $statuts,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ], Response::HTTP_OK, [], ['groups' => 'statut:read']);
    }

    #[Route('/oeuvre/{oeuvreId}', name: 'app_statut_list_by_oeuvre', methods: ['GET'])]
    public function listByOeuvre(int $oeuvreId, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $statuts = $this->statutRepository->findByOeuvre($oeuvreId);
        $total = count($statuts);
        $statuts = array_slice($statuts, ($page - 1) * $limit, $limit);

        return $this->json([
            'items' => $statuts,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ], Response::HTTP_OK, [], ['groups' => 'statut:read']);
    }

    #[Route('/{id}', name: 'app_statut_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $statut = $this->statutRepository->find($id);

        if (!$statut || !$statut instanceof \App\Entity\Statut) {
            return $this->json(['message' => 'Statut non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($statut, Response::HTTP_OK, [], ['groups' => 'statut:read']);
    }

    #[Route('', name: 'app_statut_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return $this->json(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $statut = new Statut();
        $statut->setNom($data['nom'] ?? '');
        $statut->setDescription($data['description'] ?? null);
        $statut->setUser($user);

        if (isset($data['oeuvre_id'])) {
            $oeuvre = $this->oeuvreRepository->find($data['oeuvre_id']);
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $statut->setOeuvre($oeuvre);
        }

        $errors = $this->validator->validate($statut);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->statutRepository->save($statut, true);

        return $this->json($statut, Response::HTTP_CREATED, [], ['groups' => 'statut:read']);
    }

    #[Route('/{id}', name: 'app_statut_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return $this->json(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $statut = $this->statutRepository->find($id);

        if (!$statut || !$statut instanceof \App\Entity\Statut) {
            return $this->json(['message' => 'Statut non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($statut->getUser() !== $user) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $statut->setNom($data['nom']);
        }
        if (isset($data['description'])) {
            $statut->setDescription($data['description']);
        }
        if (isset($data['oeuvre_id'])) {
            $oeuvre = $this->oeuvreRepository->find($data['oeuvre_id']);
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $statut->setOeuvre($oeuvre);
        }

        $errors = $this->validator->validate($statut);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->statutRepository->save($statut, true);

        return $this->json($statut, Response::HTTP_OK, [], ['groups' => 'statut:read']);
    }

    #[Route('/{id}', name: 'app_statut_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return $this->json(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $statut = $this->statutRepository->find($id);

        if (!$statut || !$statut instanceof \App\Entity\Statut) {
            return $this->json(['message' => 'Statut non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($statut->getUser() !== $user) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $this->statutRepository->remove($statut, true);

        return $this->json(['message' => 'Statut supprimé avec succès']);
    }

    #[Route('/user/{userId}/oeuvre/{oeuvreId}', name: 'app_statut_get_current', methods: ['GET'])]
    public function getCurrentStatus(int $userId, int $oeuvreId): JsonResponse
    {
        $statut = $this->statutRepository->findByUserAndOeuvre($userId, $oeuvreId);

        if (!$statut) {
            return $this->json(['message' => 'Aucun statut trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($statut, Response::HTTP_OK, [], ['groups' => 'statut:read']);
    }
} 