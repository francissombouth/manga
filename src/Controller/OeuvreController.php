<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Repository\OeuvreRepository;
use App\Repository\TagRepository;
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
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private MangaDxService $mangaDxService,
        private MangaDxImportService $importService
    ) {
    }

    #[Route('', name: 'app_oeuvre_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        // Si c'est une requête JSON/API, retourner du JSON
        if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json')) {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 10);
            $type = $request->query->get('type');
            $auteurId = $request->query->getInt('auteur_id');
            $tagId = $request->query->getInt('tag_id');

            $criteria = [];
            if ($type) {
                $criteria['type'] = $type;
            }
            if ($auteurId) {
                $criteria['auteur'] = $auteurId;
            }

            $oeuvres = $this->oeuvreRepository->findBy($criteria, ['titre' => 'ASC'], $limit, ($page - 1) * $limit);
            $total = $this->oeuvreRepository->count($criteria);

            return $this->json([
                'items' => $oeuvres,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ], Response::HTTP_OK, [], ['groups' => 'oeuvre:read']);
        }

        // Sinon, afficher la vue HTML
        $oeuvres = $this->oeuvreRepository->findAllWithAuteurAndChapitres();
        
        // Si on a moins de 50 œuvres, auto-alimenter depuis l'API
        if (count($oeuvres) < 50) {
            $this->autoFeedFromApi();
            // Recharger les œuvres après l'import
            $oeuvres = $this->oeuvreRepository->findAllWithAuteurAndChapitres();
        }
        
        // Calculer les statistiques pour chaque œuvre
        $oeuvresWithStats = [];
        $dateLimit = new \DateTimeImmutable('-7 days');
        
        foreach ($oeuvres as $oeuvre) {
            $chapitres = $oeuvre->getChapitres();
            $hasNewChapter = false;
            $latestChapterDate = null;
            
            foreach ($chapitres as $chapitre) {
                if ($chapitre->getCreatedAt() > $dateLimit) {
                    $hasNewChapter = true;
                }
                if (!$latestChapterDate || $chapitre->getCreatedAt() > $latestChapterDate) {
                    $latestChapterDate = $chapitre->getCreatedAt();
                }
            }
            
            $oeuvresWithStats[] = [
                'oeuvre' => $oeuvre,
                'chapitres_count' => $chapitres->count(),
                'has_new_chapter' => $hasNewChapter,
                'latest_chapter_date' => $latestChapterDate
            ];
        }
        
        // Trier par date de dernier chapitre (plus récent en premier)
        usort($oeuvresWithStats, function($a, $b) {
            $dateA = $a['latest_chapter_date'] ?? new \DateTimeImmutable('1970-01-01');
            $dateB = $b['latest_chapter_date'] ?? new \DateTimeImmutable('1970-01-01');
            return $dateB <=> $dateA;
        });

        return $this->render('oeuvre/collection.html.twig', [
            'oeuvres_with_stats' => $oeuvresWithStats,
            'total_oeuvres' => count($oeuvres),
            'total_chapitres' => array_sum(array_column($oeuvresWithStats, 'chapitres_count')),
        ]);
    }

    #[Route('/{id}', name: 'app_oeuvre_show', methods: ['GET'])]
    public function show(int $id, Request $request): Response
    {
        $oeuvre = $this->oeuvreRepository->find($id);

        if (!$oeuvre) {
            // Si c'est une requête AJAX ou avec Accept: application/json, retourner JSON
            if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json')) {
                return $this->json(['message' => 'Œuvre non trouvée'], Response::HTTP_NOT_FOUND);
            }
            throw $this->createNotFoundException('Œuvre non trouvée');
        }

        // Si c'est une requête AJAX ou avec Accept: application/json, retourner JSON
        if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json')) {
            return $this->json($oeuvre, Response::HTTP_OK, [], ['groups' => 'oeuvre:read']);
        }

        // Sinon, afficher la vue HTML
        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
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