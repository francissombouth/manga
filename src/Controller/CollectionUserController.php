<?php

namespace App\Controller;

use App\Entity\CollectionUser;
use App\Entity\Oeuvre;
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

    #[Route('/favoris', name: 'app_favoris')]
    #[IsGranted('ROLE_USER')]
    public function favoris(): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            throw new \InvalidArgumentException('Utilisateur non authentifié');
        }
        
        $collections = $this->collectionUserRepository->findBy(['user' => $user], ['dateAjout' => 'DESC']);

        $oeuvres = [];
        $auteursUniques = [];
        foreach ($collections as $collection) {
            $oeuvre = $collection->getOeuvre();
            if ($oeuvre) {
                $oeuvres[] = $oeuvre;
                
                // Compter les auteurs uniques
                $auteur = $oeuvre->getAuteur();
                if ($auteur) {
                    $auteurId = $auteur->getId();
                    if ($auteurId) {
                        $auteursUniques[$auteurId] = $auteur;
                    }
                }
            }
        }

        return $this->render('collection/favoris.html.twig', [
            'oeuvres' => $oeuvres,
            'collections' => $collections,
            'auteursUniques' => count($auteursUniques)
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_collection_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function ajouterFavoris(Oeuvre $oeuvre): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        // Vérifier si l'œuvre est déjà dans les favoris
        $existingCollection = $this->collectionUserRepository->findOneBy([
            'user' => $user,
            'oeuvre' => $oeuvre
        ]);

        if ($existingCollection) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cette œuvre est déjà dans vos favoris'
            ], 400);
        }

        // Créer une nouvelle collection
        $collection = new CollectionUser();
        $collection->setUser($user);
        $collection->setOeuvre($oeuvre);

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Œuvre ajoutée aux favoris',
            'isFavorite' => true
        ]);
    }

    #[Route('/retirer/{id}', name: 'app_collection_remove', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function retirerFavoris(Oeuvre $oeuvre): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $collection = $this->collectionUserRepository->findOneBy([
            'user' => $user,
            'oeuvre' => $oeuvre
        ]);

        if (!$collection) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cette œuvre n\'est pas dans vos favoris'
            ], 400);
        }

        $this->entityManager->remove($collection);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Œuvre retirée des favoris',
            'isFavorite' => false
        ]);
    }

    #[Route('/toggle/{id}', name: 'app_collection_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleFavoris(Oeuvre $oeuvre): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $collection = $this->collectionUserRepository->findOneBy([
            'user' => $user,
            'oeuvre' => $oeuvre
        ]);

        if ($collection) {
            // Retirer des favoris
            $this->entityManager->remove($collection);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Œuvre retirée des favoris',
                'isFavorite' => false
            ]);
        } else {
            // Ajouter aux favoris
            $collection = new CollectionUser();
            $collection->setUser($user);
            $collection->setOeuvre($oeuvre);

            $this->entityManager->persist($collection);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Œuvre ajoutée aux favoris',
                'isFavorite' => true
            ]);
        }
    }

    #[Route('/verifier/{id}', name: 'app_collection_check')]
    #[IsGranted('ROLE_USER')]
    public function verifierFavoris(Oeuvre $oeuvre): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return new JsonResponse([
                'isFavorite' => false
            ]);
        }

        $collection = $this->collectionUserRepository->findOneBy([
            'user' => $user,
            'oeuvre' => $oeuvre
        ]);

        return new JsonResponse([
            'isFavorite' => $collection !== null
        ]);
    }

    #[Route('/{id}', name: 'app_collection_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $collection = $this->collectionUserRepository->find($id);

        if (!$collection || !$collection instanceof \App\Entity\CollectionUser) {
            return $this->json(['message' => 'Collection non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($collection, Response::HTTP_OK, [], ['groups' => 'collection:read']);
    }

    #[Route('', name: 'app_collection_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return $this->json(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $collection = new CollectionUser();
        $collection->setUser($user);
        $collection->setDateAjout(new \DateTime());

        if (isset($data['oeuvre_id'])) {
            $oeuvre = $this->oeuvreRepository->find($data['oeuvre_id']);
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
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
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            return $this->json(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $collection = $this->collectionUserRepository->find($id);

        if (!$collection || !$collection instanceof \App\Entity\CollectionUser) {
            return $this->json(['message' => 'Collection non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if ($collection->getUser() !== $user) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['oeuvre_id'])) {
            $oeuvre = $this->oeuvreRepository->find($data['oeuvre_id']);
            if (!$oeuvre || !$oeuvre instanceof \App\Entity\Oeuvre) {
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