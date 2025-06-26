<?php

namespace App\Controller;

use App\Entity\CollectionUser;
use App\Repository\CollectionUserRepository;
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

#[Route('/collections')]
class CollectionUserController extends AbstractController
{
    public function __construct(
        private CollectionUserRepository $collectionUserRepository,
        private OeuvreRepository $oeuvreRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/user/{userId}', name: 'app_collection_list_by_user', methods: ['GET'])]
    public function listByUser(int $userId, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $collections = $this->collectionUserRepository->findByUser($userId);
        $total = count($collections);
        $collections = array_slice($collections, ($page - 1) * $limit, $limit);

        return $this->json([
            'items' => $collections,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ], Response::HTTP_OK, [], ['groups' => 'collection:read']);
    }

    #[Route('/oeuvre/{oeuvreId}', name: 'app_collection_list_by_oeuvre', methods: ['GET'])]
    public function listByOeuvre(int $oeuvreId, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $collections = $this->collectionUserRepository->findByOeuvre($oeuvreId);
        $total = count($collections);
        $collections = array_slice($collections, ($page - 1) * $limit, $limit);

        return $this->json([
            'items' => $collections,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ], Response::HTTP_OK, [], ['groups' => 'collection:read']);
    }

    #[Route('/{id}', name: 'app_collection_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $collection = $this->collectionUserRepository->find($id);

        if (!$collection) {
            return $this->json(['message' => 'Collection non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($collection, Response::HTTP_OK, [], ['groups' => 'collection:read']);
    }

    #[Route('', name: 'app_collection_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $collection = new CollectionUser();
        $collection->setUser($this->getUser());
        $collection->setDateAjout(new \DateTime());

        if (isset($data['oeuvre_id'])) {
            $oeuvre = $this->oeuvreRepository->find($data['oeuvre_id']);
            if (!$oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $collection->setOeuvre($oeuvre);
        }

        $errors = $this->validator->validate($collection);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->collectionUserRepository->save($collection, true);

        return $this->json($collection, Response::HTTP_CREATED, [], ['groups' => 'collection:read']);
    }

    #[Route('/{id}', name: 'app_collection_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $collection = $this->collectionUserRepository->find($id);

        if (!$collection) {
            return $this->json(['message' => 'Collection non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if ($collection->getUser() !== $this->getUser()) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['oeuvre_id'])) {
            $oeuvre = $this->oeuvreRepository->find($data['oeuvre_id']);
            if (!$oeuvre) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $collection->setOeuvre($oeuvre);
        }

        $errors = $this->validator->validate($collection);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->collectionUserRepository->save($collection, true);

        return $this->json($collection, Response::HTTP_OK, [], ['groups' => 'collection:read']);
    }

    #[Route('/{id}', name: 'app_collection_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $collection = $this->collectionUserRepository->find($id);

        if (!$collection) {
            return $this->json(['message' => 'Collection non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if ($collection->getUser() !== $this->getUser()) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $this->collectionUserRepository->remove($collection, true);

        return $this->json(['message' => 'Collection supprimée avec succès']);
    }

    #[Route('/user/{userId}/oeuvre/{oeuvreId}', name: 'app_collection_get_by_user_and_oeuvre', methods: ['GET'])]
    public function getByUserAndOeuvre(int $userId, int $oeuvreId): JsonResponse
    {
        $collection = $this->collectionUserRepository->findByUserAndOeuvre($userId, $oeuvreId);

        if (!$collection) {
            return $this->json(['message' => 'Collection non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($collection, Response::HTTP_OK, [], ['groups' => 'collection:read']);
    }
} 