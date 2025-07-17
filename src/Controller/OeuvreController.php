<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Repository\OeuvreRepository;
use App\Repository\OeuvreNoteRepository;
use App\Repository\TagRepository;
use App\Repository\CollectionUserRepository;
use App\Service\MangaDxService;
use App\Service\MangaDxImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;



#[Route('/oeuvres')]
class OeuvreController extends AbstractController
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository,
        private OeuvreNoteRepository $noteRepository,
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private MangaDxService $mangaDxService,
        private MangaDxImportService $importService,
        private CollectionUserRepository $collectionUserRepository
    ) {
    }

    #[Route('', name: 'app_oeuvre_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        // Récupérer les paramètres de recherche et de filtrage
        $tagId = $request->query->get('tag') ? $request->query->getInt('tag') : null;
        $search = $request->query->get('search', '');
        $auteurId = $request->query->get('auteur') ? $request->query->getInt('auteur') : null;
        $type = $request->query->get('type');
        
        // Si c'est une requête JSON/API, retourner du JSON
        if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json')) {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 12);

            // Utiliser la nouvelle méthode de recherche corrigée
            if (!empty($search) || $auteurId || $tagId) {
                $oeuvres = $this->oeuvreRepository->search(
                    query: !empty($search) ? $search : '',
                    auteurId: $auteurId ?: null,
                    tagId: $tagId ?: null
                );
                // Pagination manuelle pour la recherche
                $total = count($oeuvres);
                $oeuvres = array_slice($oeuvres, ($page - 1) * $limit, $limit);
            } else {
                // Pas de recherche, récupérer toutes les œuvres
                $oeuvres = $this->oeuvreRepository->findBy([], ['titre' => 'ASC'], $limit, ($page - 1) * $limit);
                $total = $this->oeuvreRepository->count([]);
            }

            return $this->json([
                'hydra:member' => $oeuvres,
                'hydra:totalItems' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ], Response::HTTP_OK, [], ['groups' => 'oeuvre:read']);
        }

        // Si on filtre par tag, récupérer uniquement les œuvres avec ce tag
        if ($tagId) {
            $oeuvres = $this->oeuvreRepository->findByTag($tagId);
            $totalOeuvres = $this->oeuvreRepository->countByTag($tagId);
            $totalChapitres = array_sum(array_map(fn($oeuvre) => $oeuvre->getChapitres()->count(), $oeuvres));
        } elseif (!empty($search) || $auteurId) {
            // Si on fait une recherche textuelle ou par auteur, utiliser la méthode de recherche
            $oeuvres = $this->oeuvreRepository->search(
                query: $search,
                auteurId: $auteurId ?: null,
                tagId: null
            );
            $totalOeuvres = count($oeuvres);
            $totalChapitres = array_sum(array_map(fn($oeuvre) => $oeuvre->getChapitres()->count(), $oeuvres));
        } else {
            // Récupérer toutes les œuvres sans limitation
            $oeuvres = $this->oeuvreRepository->findAllWithRelations();
            $totalOeuvres = count($oeuvres);
            $totalChapitres = array_sum(array_map(fn($oeuvre) => $oeuvre->getChapitres()->count(), $oeuvres));
        }

        // Calculer le nombre total d'auteurs uniques
        $totalAuteurs = $this->entityManager->createQuery(
            'SELECT COUNT(DISTINCT a.id) FROM App\Entity\Auteur a JOIN a.oeuvres o'
        )->getSingleScalarResult();

        // Récupérer tous les tags pour le dropdown de recherche
        $allTags = $this->tagRepository->findBy([], ['nom' => 'ASC']);
        
        // Récupérer les tags populaires (les plus utilisés)
        $popularTags = $this->tagRepository->findPopularTags(8);

        // Vérifier les favoris pour l'utilisateur connecté
        $oeuvreIsFavorite = [];
        $user = $this->getUser();
        if ($user) {
            foreach ($oeuvres as $oeuvre) {
                $favoriteCollection = $this->collectionUserRepository->findOneBy([
                    'user' => $user,
                    'oeuvre' => $oeuvre
                ]);
                $oeuvreIsFavorite[$oeuvre->getId()] = $favoriteCollection !== null;
            }
        }

        return $this->render('oeuvre/collection.html.twig', [
            'oeuvres' => $oeuvres,
            'totalOeuvres' => $totalOeuvres,
            'totalChapitres' => $totalChapitres,
            'totalAuteurs' => $totalAuteurs,
            'allTags' => $allTags,
            'popularTags' => $popularTags,
            'selectedTag' => $tagId ? $this->tagRepository->find($tagId) : null,
            'oeuvreIsFavorite' => $oeuvreIsFavorite,
        ]);
    }

    #[Route('/{id}', name: 'app_oeuvre_show', methods: ['GET'])]
    public function show(int $id, Request $request): Response
    {
        $oeuvre = $this->oeuvreRepository->find($id);

        if (!$oeuvre) {
            if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json')) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }
            throw $this->createNotFoundException('Œuvre non trouvée');
        }

        // Récupération des commentaires pour l'affichage initial
        $commentaires = $oeuvre->getCommentaires()->toArray();
        usort($commentaires, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        
        // Récupérer les chapitres triés par ordre
        $chapitresSorted = $oeuvre->getChapitresSorted();
        
        // Récupérer les données de notation
        $user = $this->getUser();
        $userNote = null;
        $isFavorite = false;
        
        if ($user) {
            $userNoteEntity = $this->noteRepository->findByUserAndOeuvre($user, $oeuvre);
            $userNote = $userNoteEntity ? $userNoteEntity->getNote() : null;
            
            // Vérifier si l'œuvre est en favoris
            $favoriteCollection = $this->collectionUserRepository->findOneBy([
                'user' => $user,
                'oeuvre' => $oeuvre
            ]);
            $isFavorite = $favoriteCollection !== null;
        }
        
        $onglet = $request->query->get('onglet', 'chapitres');

        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
            'chapitres' => $chapitresSorted,
            'commentaires' => $commentaires,
            'onglet' => $onglet,
            'userNote' => $userNote,
            'isFavorite' => $isFavorite,
        ]);
    }

    #[Route('', name: 'app_oeuvre_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $oeuvre = new Oeuvre();
        $oeuvre->setTitre($data['titre']);
        $oeuvre->setType($data['type']);
        $oeuvre->setResume($data['resume'] ?? null);
        $oeuvre->setCouverture($data['couverture'] ?? null);
        $oeuvre->setDatePublication($data['date_publication'] ? new \DateTime($data['date_publication']) : null);
        $oeuvre->setIsbn($data['isbn'] ?? null);

        if (isset($data['auteur_id'])) {
            $auteur = $this->entityManager->getReference('App\Entity\Auteur', $data['auteur_id']);
            $oeuvre->setAuteur($auteur);
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            foreach ($data['tags'] as $tagName) {
                $tag = $this->tagRepository->findOrCreate($tagName);
                $oeuvre->addTag($tag);
            }
        }

        $errors = $this->validator->validate($oeuvre);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->oeuvreRepository->save($oeuvre, true);

        return $this->json($oeuvre, Response::HTTP_CREATED, [], ['groups' => 'oeuvre:read']);
    }

    #[Route('/{id}', name: 'app_oeuvre_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $oeuvre = $this->oeuvreRepository->find($id);

        if (!$oeuvre) {
            return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['titre'])) {
            $oeuvre->setTitre($data['titre']);
        }
        if (isset($data['type'])) {
            $oeuvre->setType($data['type']);
        }
        if (isset($data['resume'])) {
            $oeuvre->setResume($data['resume']);
        }
        if (isset($data['couverture'])) {
            $oeuvre->setCouverture($data['couverture']);
        }
        if (isset($data['date_publication'])) {
            $oeuvre->setDatePublication(new \DateTime($data['date_publication']));
        }
        if (isset($data['isbn'])) {
            $oeuvre->setIsbn($data['isbn']);
        }
        if (isset($data['auteur_id'])) {
            $auteur = $this->entityManager->getReference('App\Entity\Auteur', $data['auteur_id']);
            $oeuvre->setAuteur($auteur);
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $oeuvre->getTags()->clear();
            foreach ($data['tags'] as $tagName) {
                $tag = $this->tagRepository->findOrCreate($tagName);
                $oeuvre->addTag($tag);
            }
        }

        $errors = $this->validator->validate($oeuvre);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Données invalides', 'errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->oeuvreRepository->save($oeuvre, true);

        return $this->json($oeuvre, Response::HTTP_OK, [], ['groups' => 'oeuvre:read']);
    }

    #[Route('/{id}', name: 'app_oeuvre_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $oeuvre = $this->oeuvreRepository->find($id);

        if (!$oeuvre) {
            return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->oeuvreRepository->remove($oeuvre, true);

        return $this->json(['message' => 'Œuvre supprimée avec succès']);
    }

    #[Route('/search', name: 'app_oeuvre_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $results = $this->oeuvreRepository->search($query);
        $total = count($results);
        $results = array_slice($results, ($page - 1) * $limit, $limit);

        return $this->json([
            'items' => $results,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ], Response::HTTP_OK, [], ['groups' => 'oeuvre:read']);
    }

    /**
     * Auto-alimente la base de données avec des œuvres populaires de l'API MangaDx
     */
    private function autoFeedFromApi(): void
    {
        try {
            // Récupérer 48 mangas populaires depuis l'API
            $popularMangasData = $this->mangaDxService->getPopularManga(48, 0);
            
            foreach ($popularMangasData as $mangaData) {
                try {
                    // Importer chaque manga dans la base de données
                    $this->importService->importOrUpdateOeuvre($mangaData['id']);
                } catch (\Exception $e) {
                    // Si l'import d'un manga échoue, on continue avec les autres
                    continue;
                }
            }
        } catch (\Exception $e) {
            // Si l'API ne répond pas, on ignore silencieusement
            // L'utilisateur verra simplement moins d'œuvres
        }
    }
} 