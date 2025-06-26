<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class MangaDxService
{
    private const BASE_URL = 'https://api.mangadex.org';
    
    // Filtres de contenu : exclure le contenu érotique et pornographique
    // Autorisé : 'safe' (tout public), 'suggestive' (contenu suggestif léger)
    // Exclu : 'erotica' (contenu érotique), 'pornographic' (contenu pornographique)
    private const ALLOWED_CONTENT_RATINGS = ['safe', 'suggestive'];
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    /**
     * Recherche des mangas par titre
     */
    public function searchManga(string $title, int $limit = 50): array
    {
        try {
            // Limiter selon les contraintes de l'API MangaDx (maximum 100)
            $limit = min($limit, 100);
            
            $response = $this->httpClient->request('GET', self::BASE_URL . '/manga', [
                'query' => [
                    'title' => $title,
                    'limit' => $limit,
                    'includes' => ['author', 'artist', 'cover_art'],
                    'hasAvailableChapters' => true,
                    'order' => ['relevance' => 'desc'],
                    'contentRating' => self::ALLOWED_CONTENT_RATINGS
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            $data = $response->toArray();
            return $data['data'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche MangaDx: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les mangas populaires avec pagination
     */
    public function getPopularManga(int $limit = 50, int $offset = 0): array
    {
        try {
            // Limiter selon les contraintes de l'API MangaDx (maximum 100)
            $limit = min($limit, 100);
            
            // Vérifier que offset + limit <= 10000
            if ($offset + $limit > 10000) {
                $limit = 10000 - $offset;
                if ($limit <= 0) {
                    return [];
                }
            }
            
            $response = $this->httpClient->request('GET', self::BASE_URL . '/manga', [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'includes' => ['author', 'artist', 'cover_art'],
                    'hasAvailableChapters' => true,
                    'order' => ['followedCount' => 'desc'],
                    'availableTranslatedLanguage' => ['en', 'fr'],
                    'contentRating' => self::ALLOWED_CONTENT_RATINGS
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            $data = $response->toArray();
            return $data['data'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des mangas populaires: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les mangas populaires avec pagination automatique (toutes les pages)
     */
    public function getAllPopularManga(int $maxMangas = 1000): array
    {
        $allMangas = [];
        $offset = 0;
        $limit = 100; // Maximum par requête
        $maxOffset = 10000; // Limite de l'API
        
        $this->logger->info("Début de la récupération des mangas populaires (max: {$maxMangas})");
        
        do {
            // Vérifier qu'on ne dépasse pas la limite de l'API
            if ($offset >= $maxOffset) {
                $this->logger->warning("Limite d'offset API atteinte ({$maxOffset}), arrêt de la récupération");
                break;
            }
            
            // Vérifier qu'on ne dépasse pas le nombre max demandé
            if (count($allMangas) >= $maxMangas) {
                $this->logger->info("Nombre maximum de mangas atteint ({$maxMangas})");
                break;
            }
            
            $this->logger->info("Récupération des mangas {$offset} à " . ($offset + $limit - 1));
            
            $mangas = $this->getPopularManga($limit, $offset);
            
            if (empty($mangas)) {
                $this->logger->info("Aucun manga supplémentaire trouvé, fin de la récupération");
                break;
            }
            
            $allMangas = array_merge($allMangas, $mangas);
            $offset += $limit;
            
            $this->logger->info("Mangas récupérés: " . count($mangas) . ", Total: " . count($allMangas));
            
            // Petite pause pour éviter le rate limiting
            if (count($mangas) === $limit) {
                usleep(100000); // 100ms de pause
            }
            
        } while (count($mangas) === $limit && count($allMangas) < $maxMangas);
        
        // Limiter au nombre max demandé
        if (count($allMangas) > $maxMangas) {
            $allMangas = array_slice($allMangas, 0, $maxMangas);
        }
        
        $this->logger->info("Récupération terminée. Total de mangas: " . count($allMangas));
        return $allMangas;
    }

    /**
     * Récupère un manga par son ID
     */
    public function getMangaById(string $id): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . "/manga/{$id}", [
                'query' => [
                    'includes' => ['author', 'artist', 'cover_art']
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            $data = $response->toArray();
            return $data['data'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du manga: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les chapitres d'un manga (une page)
     */
    public function getMangaChapters(string $mangaId, int $limit = 100, int $offset = 0): array
    {
        try {
            // Limiter selon les contraintes de l'API MangaDx
            $limit = min($limit, 100); // Maximum 100 selon la documentation
            
            // Vérifier que offset + limit <= 10000
            if ($offset + $limit > 10000) {
                $limit = 10000 - $offset;
                if ($limit <= 0) {
                    return [];
                }
            }
            
            $response = $this->httpClient->request('GET', self::BASE_URL . "/manga/{$mangaId}/feed", [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'includes' => ['scanlation_group', 'user'],
                    'order' => ['chapter' => 'asc'],
                    'translatedLanguage' => ['en', 'fr'],
                    'contentRating' => self::ALLOWED_CONTENT_RATINGS
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            $data = $response->toArray();
            return $data['data'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des chapitres: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère TOUS les chapitres d'un manga avec pagination automatique
     */
    public function getAllMangaChapters(string $mangaId): array
    {
        $allChapters = [];
        $offset = 0;
        $limit = 100; // Maximum par requête
        $maxOffset = 10000; // Limite de l'API
        
        $this->logger->info("Début de la récupération de tous les chapitres pour le manga: {$mangaId}");
        
        do {
            // Vérifier qu'on ne dépasse pas la limite de l'API
            if ($offset >= $maxOffset) {
                $this->logger->warning("Limite d'offset API atteinte ({$maxOffset}), arrêt de la récupération");
                break;
            }
            
            $this->logger->info("Récupération des chapitres {$offset} à " . ($offset + $limit - 1));
            
            $chapters = $this->getMangaChapters($mangaId, $limit, $offset);
            
            if (empty($chapters)) {
                $this->logger->info("Aucun chapitre supplémentaire trouvé, fin de la récupération");
                break;
            }
            
            $allChapters = array_merge($allChapters, $chapters);
            $offset += $limit;
            
            $this->logger->info("Chapitres récupérés: " . count($chapters) . ", Total: " . count($allChapters));
            
            // Petite pause pour éviter le rate limiting
            if (count($chapters) === $limit) {
                usleep(200000); // 200ms de pause
            }
            
        } while (count($chapters) === $limit); // Continuer tant qu'on récupère le nombre max
        
        $this->logger->info("Récupération terminée. Total de chapitres: " . count($allChapters));
        return $allChapters;
    }

    /**
     * Récupère un chapitre par son ID
     */
    public function getChapterById(string $id): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . "/chapter/{$id}", [
                'query' => [
                    'includes' => ['manga', 'scanlation_group', 'user']
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            $data = $response->toArray();
            return $data['data'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du chapitre: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les URLs des pages d'un chapitre avec retry et fallback
     */
    public function getChapterPages(string $chapterId): array
    {
        $maxRetries = 3;
        $baseDelay = 2; // Délai de base augmenté à 2 secondes
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->logger->info("Tentative {$attempt}/{$maxRetries} de récupération des pages pour le chapitre: {$chapterId}");
                
                $response = $this->httpClient->request('GET', self::BASE_URL . "/at-home/server/{$chapterId}", [
                    'headers' => [
                        'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                    ],
                    'timeout' => 30 // Timeout plus long pour les serveurs lents
                ]);
                
                $statusCode = $response->getStatusCode();
                $this->logger->info("Code de statut de la réponse at-home: {$statusCode}");
                
                if ($statusCode === 429) {
                    $this->logger->warning("Rate limit atteint, tentative {$attempt}/{$maxRetries}");
                    if ($attempt < $maxRetries) {
                        $delay = $baseDelay * pow(3, $attempt - 1); // Backoff plus agressif (3x au lieu de 2x)
                        $this->logger->info("Attente de {$delay} secondes avant la prochaine tentative");
                        sleep($delay);
                        continue;
                    }
                }
                
                $data = $response->toArray();

                if (!isset($data['result']) || $data['result'] !== 'ok') {
                    $this->logger->error("Réponse at-home invalide pour tentative {$attempt}: " . json_encode($data));
                    if ($attempt < $maxRetries) {
                        sleep($baseDelay * $attempt);
                        continue;
                    }
                    return [];
                }

                if (!isset($data['baseUrl']) || !isset($data['chapter']['hash'])) {
                    $this->logger->error("Données essentielles manquantes dans la réponse at-home");
                    if ($attempt < $maxRetries) {
                        sleep($baseDelay * $attempt);
                        continue;
                    }
                    return [];
                }

                $baseUrl = $data['baseUrl'];
                $hash = $data['chapter']['hash'];
                
                // Essayer d'abord la qualité originale
                $pages = $data['chapter']['data'] ?? [];
                $quality = 'data'; // Qualité originale
                
                // Si pas de pages en qualité originale, essayer les pages en qualité réduite
                if (empty($pages) && isset($data['chapter']['dataSaver'])) {
                    $pages = $data['chapter']['dataSaver'];
                    $quality = 'data-saver'; // Qualité réduite
                    $this->logger->info("Utilisation des pages en qualité réduite, nombre: " . count($pages));
                }

                if (empty($pages)) {
                    $this->logger->error("Aucune page trouvée pour ce chapitre, tentative {$attempt}");
                    if ($attempt < $maxRetries) {
                        sleep($baseDelay * $attempt);
                        continue;
                    }
                    return [];
                }

                $this->logger->info("BaseUrl: {$baseUrl}, Hash: {$hash}, Qualité: {$quality}, Nombre de pages: " . count($pages));

                $pageUrls = [];
                foreach ($pages as $index => $page) {
                    $pageUrl = "{$baseUrl}/{$quality}/{$hash}/{$page}";
                    $pageUrls[] = $pageUrl;
                }

                $this->logger->info("Récupération réussie de " . count($pageUrls) . " pages en tentative {$attempt}");
                
                // Pause systématique pour éviter le rate limiting sur les requêtes suivantes
                if (count($pageUrls) > 0) {
                    usleep(500000); // 500ms de pause après une requête réussie
                }
                
                return $pageUrls;
                
            } catch (\Exception $e) {
                $this->logger->error("Erreur tentative {$attempt}/{$maxRetries} pour le chapitre {$chapterId}: " . $e->getMessage());
                
                if ($attempt < $maxRetries) {
                    $delay = $baseDelay * pow(3, $attempt - 1);
                    $this->logger->info("Attente de {$delay} secondes avant la prochaine tentative");
                    sleep($delay);
                } else {
                    $this->logger->error('Stack trace: ' . $e->getTraceAsString());
                }
            }
        }
        
        $this->logger->error("Échec de récupération des pages après {$maxRetries} tentatives pour le chapitre {$chapterId}");
        return [];
    }

    /**
     * Récupère l'URL de la couverture d'un manga
     */
    public function getCoverUrl(string $mangaId, string $filename): string
    {
        return "https://uploads.mangadex.org/covers/{$mangaId}/{$filename}";
    }

    /**
     * Retourne les niveaux de contenu autorisés
     */
    public function getAllowedContentRatings(): array
    {
        return self::ALLOWED_CONTENT_RATINGS;
    }

    /**
     * Retourne une description du filtrage de contenu appliqué
     */
    public function getContentFilterDescription(): string
    {
        return 'Contenu autorisé : Tout public et Suggestif léger. Contenu érotique et pornographique exclu.';
    }

    /**
     * Compte le nombre total de chapitres disponibles pour un manga (sans les récupérer)
     */
    public function countMangaChapters(string $mangaId): int
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . "/manga/{$mangaId}/feed", [
                'query' => [
                    'limit' => 1, // On veut juste le total
                    'translatedLanguage' => ['en', 'fr'],
                    'contentRating' => self::ALLOWED_CONTENT_RATINGS
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            $data = $response->toArray();
            return $data['total'] ?? 0;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du comptage des chapitres: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Extrait les informations importantes d'un manga MangaDx
     */
    public function formatMangaData(array $mangaData): array
    {
        $attributes = $mangaData['attributes'] ?? [];
        $relationships = $mangaData['relationships'] ?? [];

        // Titre
        $title = $attributes['title']['en'] ?? $attributes['title']['ja-ro'] ?? array_values($attributes['title'])[0] ?? 'Titre inconnu';
        
        // Description
        $description = $attributes['description']['en'] ?? $attributes['description']['fr'] ?? array_values($attributes['description'])[0] ?? '';
        
        // Tags/Genres
        $tags = [];
        foreach ($relationships as $rel) {
            if ($rel['type'] === 'tag') {
                $tags[] = $rel['attributes']['name']['en'] ?? $rel['attributes']['name']['fr'] ?? 'Tag inconnu';
            }
        }

        // Auteur
        $authors = [];
        foreach ($relationships as $rel) {
            if ($rel['type'] === 'author') {
                $authors[] = $rel['attributes']['name'] ?? 'Auteur inconnu';
            }
        }

        // Couverture
        $coverUrl = null;
        foreach ($relationships as $rel) {
            if ($rel['type'] === 'cover_art') {
                $filename = $rel['attributes']['fileName'] ?? null;
                if ($filename) {
                    $coverUrl = $this->getCoverUrl($mangaData['id'], $filename);
                }
                break;
            }
        }

        return [
            'id' => $mangaData['id'],
            'title' => $title,
            'description' => $description,
            'tags' => $tags,
            'authors' => $authors,
            'cover_url' => $coverUrl,
            'status' => $attributes['status'] ?? 'unknown',
            'year' => $attributes['year'] ?? null,
            'content_rating' => $attributes['contentRating'] ?? 'safe'
        ];
    }
} 