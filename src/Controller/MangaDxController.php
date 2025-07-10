<?php

namespace App\Controller;

use App\Service\MangaDxService;
use App\Service\MangaDxImportService;
use App\Repository\OeuvreRepository;
use App\Repository\ChapitreRepository;
use App\Entity\Chapitre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mangadx')]
class MangaDxController extends AbstractController
{
    public function __construct(
        private MangaDxService $mangaDxService,
        private MangaDxImportService $importService,
        private OeuvreRepository $oeuvreRepository,
        private ChapitreRepository $chapitreRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'mangadx_home', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer uniquement les œuvres de la base de données locale (max 100)
        
        $page = max(1, (int) $request->query->get('page', 1));
        $mangasPerPage = 24; // 24 mangas par page pour une belle grille
        $offset = ($page - 1) * $mangasPerPage;
        
        // Récupérer les œuvres avec pagination depuis la base locale
        $oeuvres = $this->oeuvreRepository->findBy([], ['id' => 'ASC'], $mangasPerPage, $offset);
        $totalOeuvres = $this->oeuvreRepository->count([]);
        
        $mangas = [];
        foreach ($oeuvres as $oeuvre) {
            // Formater les données pour l'affichage compatible avec le template
            $coverUrl = $oeuvre->getCouverture();
            
            $mangas[] = [
                'id' => $oeuvre->getMangadxId(),
                'title' => $oeuvre->getTitre(),
                'description' => $oeuvre->getResume(),
                'status' => $oeuvre->getStatut(),
                'year' => $oeuvre->getYear(),
                'demographic' => $oeuvre->getDemographic(),
                'contentRating' => $oeuvre->getContentRating(),
                'originalLanguage' => $oeuvre->getOriginalLanguage(),
                'lastVolume' => $oeuvre->getLastVolume(),
                'lastChapter' => $oeuvre->getLastChapter(),
                'cover_url' => $coverUrl,
                'authors' => $oeuvre->getAuteur() ? [$oeuvre->getAuteur()->getNom()] : [],
                'chaptersCount' => count($oeuvre->getChapitres()),
                'tags' => array_map(fn($tag) => $tag->getNom(), $oeuvre->getTags()->toArray())
            ];
        }

        // Calculer la pagination
        $totalPages = ceil($totalOeuvres / $mangasPerPage);
        
        // S'assurer qu'on ne dépasse pas les pages disponibles
        if ($page > $totalPages && $totalPages > 0) {
            throw $this->createNotFoundException('Page non trouvée');
        }

        return $this->render('mangadx/index.html.twig', [
            'mangas' => $mangas,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'mangasPerPage' => $mangasPerPage,
            'totalMangas' => $totalOeuvres,
            'hasNextPage' => $page < $totalPages,
            'hasPrevPage' => $page > 1,
        ]);
    }

    #[Route('/search', name: 'mangadx_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $mangas = [];

        if ($query) {
            $searchResults = $this->mangaDxService->searchManga($query, 100);
            foreach ($searchResults as $mangaData) {
                // Auto-sauvegarder chaque manga trouvé en BDD
                try {
                    $this->importService->importOrUpdateOeuvre($mangaData['id']);
                } catch (\Exception $e) {
                    // Si l'import échoue, on continue sans interrompre l'affichage
                }
                
                $mangas[] = $this->mangaDxService->formatMangaData($mangaData);
            }
        }

        return $this->render('mangadx/search.html.twig', [
            'mangas' => $mangas,
            'query' => $query,
        ]);
    }

    #[Route('/manga/{id}', name: 'mangadx_manga_show', methods: ['GET'])]
    public function showManga(string $id): Response
    {
        $mangaData = $this->mangaDxService->getMangaById($id);
        
        if (!$mangaData) {
            throw $this->createNotFoundException('Manga non trouvé');
        }

        // Auto-sauvegarder le manga affiché en BDD
        try {
            $this->importService->importOrUpdateOeuvre($id);
        } catch (\Exception $e) {
            // Si l'import échoue, on continue sans interrompre l'affichage
        }

        $manga = $this->mangaDxService->formatMangaData($mangaData);
        
        // Récupérer TOUS les chapitres
        $chaptersData = $this->mangaDxService->getAllMangaChapters($id);
        $chapters = [];
        foreach ($chaptersData as $chapterData) {
            $chapters[] = $this->formatChapterData($chapterData);
        }

        return $this->render('mangadx/manga_show.html.twig', [
            'manga' => $manga,
            'chapters' => $chapters,
        ]);
    }

    #[Route('/manga/{id}/add-to-collection', name: 'mangadx_add_to_collection', methods: ['POST'])]
    public function addToCollection(string $id): JsonResponse
    {
        try {
            // Importer l'œuvre depuis MangaDx vers la base de données
            $oeuvre = $this->importService->importOrUpdateOeuvre($id);
            
            if ($oeuvre) {
                return $this->json([
                    'success' => true,
                    'message' => '⭐ Œuvre ajoutée à vos favoris avec succès !',
                    'oeuvre' => [
                        'id' => $oeuvre->getId(),
                        'titre' => $oeuvre->getTitre(),
                        'auteur' => $oeuvre->getAuteur() ? $oeuvre->getAuteur()->getNom() : null,
                        'chapitres_count' => count($oeuvre->getChapitres())
                    ]
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'ajout aux favoris'
                ], 400);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/chapter/{id}', name: 'mangadx_chapter_show', methods: ['GET'])]
    public function showChapter(string $id): Response
    {
        $chapterData = $this->mangaDxService->getChapterById($id);
        
        if (!$chapterData) {
            throw $this->createNotFoundException('Chapitre non trouvé');
        }

        $chapter = $this->formatChapterData($chapterData);
        
        // Récupérer les pages depuis MangaDX
        $pagesUrls = $this->mangaDxService->getChapterPages($id);
        
        // Synchroniser avec la base de données locale
        $this->syncChapterToDatabase($id, $chapterData, $pagesUrls);
        
        $pages = [];
        foreach ($pagesUrls as $index => $url) {
            // Utiliser le proxy d'images pour éviter les problèmes CORS
            $proxyUrl = $this->generateUrl('image_proxy', ['url' => $url]);
            $pages[] = [
                'url' => $proxyUrl,
                'index' => $index + 1
            ];
        }
        $chapter['pages'] = $pages;

        // Récupérer les infos du manga
        $mangaId = null;
        foreach ($chapterData['relationships'] as $rel) {
            if ($rel['type'] === 'manga') {
                $mangaId = $rel['id'];
                break;
            }
        }

        $manga = null;
        if ($mangaId) {
            $mangaData = $this->mangaDxService->getMangaById($mangaId);
            if ($mangaData) {
                $manga = $this->mangaDxService->formatMangaData($mangaData);
            }
        }

        // Récupérer tous les chapitres pour la navigation
        $allChapters = [];
        if ($mangaId) {
            $chaptersData = $this->mangaDxService->getAllMangaChapters($mangaId);
            foreach ($chaptersData as $chapterData) {
                $allChapters[] = $this->formatChapterData($chapterData);
            }
        }

        // Trouver le chapitre précédent et suivant
        $currentIndex = null;
        foreach ($allChapters as $index => $ch) {
            if ($ch['id'] === $id) {
                $currentIndex = $index;
                break;
            }
        }

        $previousChapter = null;
        $nextChapter = null;
        if ($currentIndex !== null) {
            if ($currentIndex > 0) {
                $previousChapter = $allChapters[$currentIndex - 1];
            }
            if ($currentIndex < count($allChapters) - 1) {
                $nextChapter = $allChapters[$currentIndex + 1];
            }
        }

        return $this->render('mangadx/chapter_show.html.twig', [
            'chapter' => $chapter,
            'pages' => $pages,
            'manga' => $manga,
            'previousChapter' => $previousChapter,
            'nextChapter' => $nextChapter,
        ]);
    }

    #[Route('/test-image', name: 'test_image', methods: ['GET'])]
    public function testImage(): Response
    {
        return $this->render('test_image.html.twig');
    }

    #[Route('/api/chapter/{mangadxChapterId}/pages', name: 'mangadx_api_chapter_pages', methods: ['GET'])]
    public function getChapterPages(string $mangadxChapterId): JsonResponse
    {
        try {
            // Récupérer les pages depuis MangaDX
            $pagesUrls = $this->mangaDxService->getChapterPages($mangadxChapterId);
            
            return $this->json([
                'success' => true,
                'pages' => $pagesUrls,
                'count' => count($pagesUrls)
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    private function formatChapterData(array $chapterData): array
    {
        $attributes = $chapterData['attributes'] ?? [];
        
        return [
            'id' => $chapterData['id'],
            'title' => $attributes['title'] ?? 'Chapitre ' . ($attributes['chapter'] ?? '?'),
            'chapter' => $attributes['chapter'] ?? null,
            'volume' => $attributes['volume'] ?? null,
            'pages_count' => $attributes['pages'] ?? 0,
            'published_at' => $attributes['publishAt'] ?? null,
            'created_at' => $attributes['createdAt'] ?? null,
            'updated_at' => $attributes['updatedAt'] ?? null,
            'language' => $attributes['translatedLanguage'] ?? null,
            'language_flag' => $this->getLanguageFlag($attributes['translatedLanguage'] ?? null),
            'language_name' => $this->getLanguageName($attributes['translatedLanguage'] ?? null),
        ];
    }

    private function getLanguageFlag(?string $languageCode): string
    {
        return match($languageCode) {
            'en' => '🇺🇸',
            'fr' => '🇫🇷',
            'es' => '🇪🇸',
            'pt-br' => '🇧🇷',
            'pt' => '🇵🇹',
            'de' => '🇩🇪',
            'it' => '🇮🇹',
            'ru' => '🇷🇺',
            'ja' => '🇯🇵',
            'ko' => '🇰🇷',
            'zh' => '🇨🇳',
            'zh-hk' => '🇭🇰',
            'th' => '🇹🇭',
            'vi' => '🇻🇳',
            'id' => '🇮🇩',
            'tr' => '🇹🇷',
            'ar' => '🇸🇦',
            'pl' => '🇵🇱',
            'nl' => '🇳🇱',
            'sv' => '🇸🇪',
            'da' => '🇩🇰',
            'no' => '🇳🇴',
            'fi' => '🇫🇮',
            'uk' => '🇺🇦',
            'cs' => '🇨🇿',
            'hu' => '🇭🇺',
            'ro' => '🇷🇴',
            'bg' => '🇧🇬',
            'hr' => '🇭🇷',
            'sk' => '🇸🇰',
            'sl' => '🇸🇮',
            'lt' => '🇱🇹',
            'lv' => '🇱🇻',
            'et' => '🇪🇪',
            default => '🌐'
        };
    }

    private function getLanguageName(?string $languageCode): string
    {
        return match($languageCode) {
            'en' => 'Anglais',
            'fr' => 'Français',
            'es' => 'Espagnol',
            'pt-br' => 'Portugais (Brésil)',
            'pt' => 'Portugais',
            'de' => 'Allemand',
            'it' => 'Italien',
            'ru' => 'Russe',
            'ja' => 'Japonais',
            'ko' => 'Coréen',
            'zh' => 'Chinois',
            'zh-hk' => 'Chinois (Hong Kong)',
            'th' => 'Thaï',
            'vi' => 'Vietnamien',
            'id' => 'Indonésien',
            'tr' => 'Turc',
            'ar' => 'Arabe',
            'pl' => 'Polonais',
            'nl' => 'Néerlandais',
            'sv' => 'Suédois',
            'da' => 'Danois',
            'no' => 'Norvégien',
            'fi' => 'Finnois',
            'uk' => 'Ukrainien',
            'cs' => 'Tchèque',
            'hu' => 'Hongrois',
            'ro' => 'Roumain',
            'bg' => 'Bulgare',
            'hr' => 'Croate',
            'sk' => 'Slovaque',
            'sl' => 'Slovène',
            'lt' => 'Lituanien',
            'lv' => 'Letton',
            'et' => 'Estonien',
            default => 'Inconnu'
        };
    }

    /**
     * Synchronise un chapitre MangaDX avec la base de données locale
     */
    private function syncChapterToDatabase(string $mangadxChapterId, array $chapterData, array $pagesUrls): void
    {
        try {
            // Chercher si le chapitre existe déjà en base
            $chapitreLocal = $this->chapitreRepository->findOneByMangadxChapterId($mangadxChapterId);
            
            // Récupérer le manga ID depuis les relations
            $mangaId = null;
            foreach ($chapterData['relationships'] as $rel) {
                if ($rel['type'] === 'manga') {
                    $mangaId = $rel['id'];
                    break;
                }
            }
            
            if (!$mangaId) {
                return; // Pas de manga associé, on ne peut pas créer le chapitre
            }
            
            // Chercher l'œuvre locale correspondante
            $oeuvreLocale = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangaId]);
            
            if (!$oeuvreLocale) {
                return; // L'œuvre n'existe pas en local, on ne crée pas le chapitre
            }
            
            $attributes = $chapterData['attributes'] ?? [];
            
            if ($chapitreLocal) {
                // Ne synchroniser que si le chapitre local n'a pas déjà des pages 
                // ou si on a effectivement récupéré des pages de l'API
                $currentPages = $chapitreLocal->getPages() ?? [];
                $shouldSync = empty($currentPages) || (count($pagesUrls) > 0 && count($pagesUrls) != count($currentPages));
                
                if ($shouldSync && count($pagesUrls) > 0) {
                    $chapitreLocal->setPages($pagesUrls);
                    $chapitreLocal->setUpdatedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($chapitreLocal);
                    
                    $this->addFlash('success', sprintf(
                        'Pages du chapitre "%s" synchronisées (%d page(s))',
                        $chapitreLocal->getTitre(),
                        count($pagesUrls)
                    ));
                } else if (count($pagesUrls) == 0) {
                    // Ne pas afficher de message pour 0 pages pour éviter le spam
                    return;
                }
            } else {
                // Créer un nouveau chapitre seulement si on a des pages
                if (count($pagesUrls) > 0) {
                    $nouveauChapitre = new Chapitre();
                    $nouveauChapitre->setTitre($attributes['title'] ?? 'Chapitre ' . ($attributes['chapter'] ?? '?'));
                    $nouveauChapitre->setOeuvre($oeuvreLocale);
                    $nouveauChapitre->setPages($pagesUrls);
                    $nouveauChapitre->setMangadxChapterId($mangadxChapterId);
                    
                    // Déterminer l'ordre automatiquement
                    $dernierChapitre = $this->chapitreRepository->createQueryBuilder('c')
                        ->where('c.oeuvre = :oeuvre')
                        ->setParameter('oeuvre', $oeuvreLocale)
                        ->orderBy('c.ordre', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getOneOrNullResult();
                    
                    $ordre = $dernierChapitre ? $dernierChapitre->getOrdre() + 1 : 1;
                    $nouveauChapitre->setOrdre($ordre);
                    
                    $this->entityManager->persist($nouveauChapitre);
                    $this->addFlash('success', sprintf(
                        'Chapitre "%s" créé avec %d page(s)',
                        $nouveauChapitre->getTitre(),
                        count($pagesUrls)
                    ));
                }
            }
            
            $this->entityManager->flush();
            
        } catch (\Exception $e) {
            // En cas d'erreur, on continue sans bloquer l'affichage
            error_log('Erreur synchronisation chapitre: ' . $e->getMessage());
        }
    }
} 