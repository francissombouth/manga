<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/tags')]
class TagController extends AbstractController
{
    public function __construct(
        private TagRepository $tagRepository,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'app_tag_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $search = (string) $request->query->get('search', '');

        $tags = $this->tagRepository->findByNom($search);
        $total = count($tags);
        $tags = array_slice($tags, ($page - 1) * $limit, $limit);

        return $this->json([
            'items' => $tags,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ], Response::HTTP_OK, [], ['groups' => 'tag:read']);
    }

    #[Route('/{id}', name: 'app_tag_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $tag = $this->tagRepository->find($id);

        if (!$tag || !$tag instanceof \App\Entity\Tag) {
            return $this->json(['message' => 'Tag non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($tag, Response::HTTP_OK, [], ['groups' => 'tag:read']);
    }

    #[Route('', name: 'app_tag_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $tag = new Tag();
        $tag->setNom($data['nom'] ?? '');

        $errors = $this->validator->validate($tag);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->tagRepository->save($tag, true);

        return $this->json($tag, Response::HTTP_CREATED, [], ['groups' => 'tag:read']);
    }

    #[Route('/{id}', name: 'app_tag_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $tag = $this->tagRepository->find($id);

        if (!$tag || !$tag instanceof \App\Entity\Tag) {
            return $this->json(['message' => 'Tag non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $tag->setNom($data['nom']);
        }

        $errors = $this->validator->validate($tag);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->tagRepository->save($tag, true);

        return $this->json($tag, Response::HTTP_OK, [], ['groups' => 'tag:read']);
    }

    #[Route('/{id}', name: 'app_tag_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $tag = $this->tagRepository->find($id);

        if (!$tag || !$tag instanceof \App\Entity\Tag) {
            return $this->json(['message' => 'Tag non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->tagRepository->remove($tag, true);

        return $this->json(['message' => 'Tag supprimé avec succès']);
    }

    #[Route('/find-or-create', name: 'app_tag_find_or_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function findOrCreate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom'])) {
            return $this->json(['message' => 'Le nom du tag est requis'], Response::HTTP_BAD_REQUEST);
        }

        $tag = $this->tagRepository->findOrCreate($data['nom']);

        return $this->json($tag, Response::HTTP_OK, [], ['groups' => 'tag:read']);
    }
} 