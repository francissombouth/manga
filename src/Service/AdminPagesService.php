<?php

namespace App\Service;

use App\Entity\Chapitre;
use App\Entity\Oeuvre;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\MangaDxService;

class AdminPagesService
{
    private const MANGADX_API_BASE = 'https://api.mangadex.org';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private MangaDxService $mangaDxService
    ) {
    }

    /**
     * Récupère les pages d'un chapitre dynamiquement depuis l'API MangaDx
     * Utilise la même méthode que le catalogue
     */
    public function getChapitrePages(Chapitre $chapitre): array
    {
        // Si le chapitre a déjà des pages sauvegardées, on les retourne
        if (!empty($chapitre->getPages())) {
            return $chapitre->getPages();
        }

        // Si on a l'ID du chapitre MangaDex, on l'utilise directement
        $mangadxChapterId = $chapitre->getMangadxChapterId();
        if ($mangadxChapterId) {
            return $this->mangaDxService->getChapterPages($mangadxChapterId);
        }

        // Sinon, fallback sur l'ancienne méthode (par numéro d'ordre)
        if (!$chapitre->peutRecupererPagesDynamiques()) {
            return [];
        }
        $chapterId = $this->getChapterIdFromMangaDx($chapitre);
        if (!$chapterId) {
            return [];
        }
        return $this->mangaDxService->getChapterPages($chapterId);
    }

    /**
     * Récupère l'ID du chapitre depuis MangaDx
     */
    private function getChapterIdFromMangaDx(Chapitre $chapitre): ?string
    {
        try {
            $mangadxId = $chapitre->getOeuvre()->getMangadxId();
            $chapterNumber = $chapitre->getOrdre();

            // Récupérer les chapitres de l'œuvre depuis MangaDx
            $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga/' . $mangadxId . '/feed', [
                'query' => [
                    'translatedLanguage' => ['fr', 'en'],
                    'order' => ['chapter' => 'asc'],
                    'limit' => 100
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();
            $chapters = $data['data'] ?? [];

            // Chercher le chapitre correspondant
            foreach ($chapters as $chapterData) {
                $attributes = $chapterData['attributes'];
                $apiChapterNumber = (float) ($attributes['chapter'] ?? 0);
                
                if ($apiChapterNumber == $chapterNumber) {
                    return $chapterData['id'];
                }
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération de l'ID du chapitre: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les pages d'un chapitre depuis l'API MangaDx (même méthode que le catalogue)
     */
    private function getChapterPagesFromApi(string $chapterId): array
    {
        $maxRetries = 3;
        $baseDelay = 1;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->logger->info("Tentative {$attempt}/{$maxRetries} de récupération des pages pour le chapitre: {$chapterId}");
                
                $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . "/at-home/server/{$chapterId}", [
                    'headers' => [
                        'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                    ],
                    'timeout' => 30
                ]);
                
                $statusCode = $response->getStatusCode();
                
                if ($statusCode === 429) {
                    $this->logger->warning("Rate limit atteint, tentative {$attempt}/{$maxRetries}");
                    if ($attempt < $maxRetries) {
                        $delay = $baseDelay * pow(2, $attempt - 1);
                        sleep($delay);
                        continue;
                    }
                }
                
                $data = $response->toArray();

                if (!isset($data['result']) || $data['result'] !== 'ok') {
                    $this->logger->error("Réponse at-home invalide pour tentative {$attempt}");
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
                $quality = 'data';
                
                // Si pas de pages en qualité originale, essayer les pages en qualité réduite
                if (empty($pages) && isset($data['chapter']['dataSaver'])) {
                    $pages = $data['chapter']['dataSaver'];
                    $quality = 'data-saver';
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

                $pageUrls = [];
                foreach ($pages as $index => $page) {
                    $pageUrl = "{$baseUrl}/{$quality}/{$hash}/{$page}";
                    $pageUrls[] = $pageUrl;
                }

                $this->logger->info("Récupération réussie de " . count($pageUrls) . " pages en tentative {$attempt}");
                return $pageUrls;
                
            } catch (\Exception $e) {
                $this->logger->error("Erreur tentative {$attempt}/{$maxRetries} pour le chapitre {$chapterId}: " . $e->getMessage());
                
                if ($attempt < $maxRetries) {
                    $delay = $baseDelay * pow(2, $attempt - 1);
                    sleep($delay);
                }
            }
        }
        
        $this->logger->error("Échec de récupération des pages après {$maxRetries} tentatives pour le chapitre {$chapterId}");
        return [];
    }

    /**
     * Récupère les pages pour tous les chapitres d'une œuvre
     */
    public function getOeuvrePages(Oeuvre $oeuvre): array
    {
        $result = [];
        
        foreach ($oeuvre->getChapitres() as $chapitre) {
            $pages = $this->getChapitrePages($chapitre);
            $result[$chapitre->getId()] = [
                'chapitre' => $chapitre,
                'pages' => $pages,
                'pages_count' => count($pages)
            ];
        }
        
        return $result;
    }
} 