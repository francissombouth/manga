<?php

namespace App\Service;

use App\Entity\Oeuvre;
use App\Entity\Chapitre;
use App\Entity\Auteur;
use App\Entity\Tag;
use App\Repository\OeuvreRepository;
use App\Repository\AuteurRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class MangaDxImportService
{
    private const MANGADX_API_BASE = 'https://api.mangadex.org';

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private OeuvreRepository $oeuvreRepository,
        private AuteurRepository $auteurRepository,
        private TagRepository $tagRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Importe une œuvre depuis MangaDx par son ID
     */
    public function importOeuvre(string $mangadxId): ?Oeuvre
    {
        // Vérifier si l'œuvre existe déjà
        $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangadxId]);
        if ($existingOeuvre) {
            throw new \Exception('Cette œuvre existe déjà dans la base de données');
        }

        try {
            // Récupérer les données de l'œuvre
            $mangaData = $this->fetchMangaData($mangadxId);
            if (!$mangaData) {
                throw new \Exception('Impossible de récupérer les données de l\'œuvre depuis MangaDx');
            }

            // Créer l'œuvre
            $oeuvre = $this->createOeuvreFromData($mangaData, $mangadxId);
            
            // Récupérer et créer les chapitres
            $this->importChapitres($oeuvre, $mangadxId);

            $this->entityManager->flush();

            return $oeuvre;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Récupère les données d'un manga depuis l'API MangaDx
     */
    private function fetchMangaData(string $mangadxId): ?array
    {
        try {
            $this->logger->info("Tentative de récupération des données pour l'œuvre: {$mangadxId}");
            
            $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga/' . $mangadxId, [
                'query' => [
                    'includes' => ['author', 'artist', 'tag', 'cover_art']
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ],
                'timeout' => 10 // Timeout plus court pour éviter les blocages
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info("Statut de la réponse: {$statusCode}");

            if ($statusCode !== 200) {
                $this->logger->error("Erreur HTTP {$statusCode} lors de la récupération de l'œuvre {$mangadxId}");
                return null;
            }

            $data = $response->toArray();
            
            // Validation des données reçues
            if (!isset($data['data']) || !isset($data['data']['attributes'])) {
                $this->logger->error("Structure de données invalide pour l'œuvre {$mangadxId}");
                return null;
            }
            
            $mangaData = $data['data'];
            $attributes = $mangaData['attributes'];
            
            // Log des informations importantes récupérées
            $this->logger->info("Données récupérées avec succès pour l'œuvre {$mangadxId}", [
                'title' => $attributes['title']['en'] ?? $attributes['title']['fr'] ?? 'Titre inconnu',
                'status' => $attributes['status'] ?? 'Statut inconnu',
                'demographic' => $attributes['publicationDemographic'] ?? 'Non défini',
                'content_rating' => $attributes['contentRating'] ?? 'Non défini',
                'year' => $attributes['year'] ?? 'Non défini',
                'relationships_count' => count($mangaData['relationships'] ?? []),
                'tags_count' => count(array_filter($mangaData['relationships'] ?? [], function($rel) {
                    return $rel['type'] === 'tag';
                }))
            ]);
            
            return $mangaData;

        } catch (\Exception $e) {
            $this->logger->error("Exception lors de la récupération de l'œuvre {$mangadxId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée une œuvre depuis les données MangaDx
     */
    private function createOeuvreFromData(array $mangaData, string $mangadxId): Oeuvre
    {
        $attributes = $mangaData['attributes'];
        
        $oeuvre = new Oeuvre();
        $oeuvre->setMangadxId($mangadxId);
        
        // === TITRE ET TITRES ALTERNATIFS ===
        $title = $attributes['title']['fr'] ?? $attributes['title']['en'] ?? array_values($attributes['title'])[0] ?? 'Titre inconnu';
        $oeuvre->setTitre($title);
        
        // Sauvegarder tous les titres alternatifs
        if (isset($attributes['altTitles']) && is_array($attributes['altTitles'])) {
            $alternativeTitles = [];
            foreach ($attributes['altTitles'] as $altTitle) {
                if (is_array($altTitle)) {
                    foreach ($altTitle as $lang => $titleText) {
                        $alternativeTitles[$lang] = $titleText;
                    }
                }
            }
            $oeuvre->setAlternativeTitles($alternativeTitles);
        }
        
        // === INFORMATIONS DE BASE ===
        $oeuvre->setType($attributes['publicationDemographic'] ?? 'Manga');
        
        // Description en français en priorité, sinon anglais
        $description = $attributes['description']['fr'] ?? $attributes['description']['en'] ?? null;
        if ($description) {
            $oeuvre->setResume(strip_tags($description));
        }
        
        // === DONNÉES MANGADX SPÉCIFIQUES ===
        $oeuvre->setStatut($attributes['status'] ?? null);
        $oeuvre->setOriginalLanguage($attributes['originalLanguage'] ?? null);
        $oeuvre->setDemographic($attributes['publicationDemographic'] ?? null);
        $oeuvre->setContentRating($attributes['contentRating'] ?? null);
        $oeuvre->setLastVolume($attributes['lastVolume'] ?? null);
        $oeuvre->setLastChapter($attributes['lastChapter'] ?? null);
        
        // === ANNÉE DE PUBLICATION ===
        if (isset($attributes['year']) && is_numeric($attributes['year'])) {
            $oeuvre->setYear((int) $attributes['year']);
            $oeuvre->setDatePublication(new \DateTime($attributes['year'] . '-01-01'));
        }
        
        // === DATES DE CRÉATION/MISE À JOUR ===
        if (isset($attributes['createdAt'])) {
            try {
                // Les dates de l'API sont au format ISO 8601
                $createdAt = new \DateTimeImmutable($attributes['createdAt']);
                // On ne peut pas modifier createdAt car il est défini dans le constructeur
                // mais on pourrait l'utiliser pour d'autres purposes
            } catch (\Exception $e) {
                // Ignorer si le format de date est invalide
            }
        }

        // === TRAITER LES RELATIONS ===
        if (isset($mangaData['relationships'])) {
            $this->processAuthor($oeuvre, $mangaData['relationships']);
            $this->processTags($oeuvre, $mangaData['relationships']);
            $this->processCoverArt($oeuvre, $mangaData['relationships']);
            $this->processArtist($oeuvre, $mangaData['relationships']);
        }

        $this->entityManager->persist($oeuvre);
        
        $this->logger->info("Œuvre créée avec toutes les données MangaDx", [
            'title' => $oeuvre->getTitre(),
            'status' => $oeuvre->getStatut(),
            'demographic' => $oeuvre->getDemographic(),
            'content_rating' => $oeuvre->getContentRating(),
            'original_language' => $oeuvre->getOriginalLanguage(),
            'year' => $oeuvre->getYear(),
            'alternative_titles_count' => count($oeuvre->getAlternativeTitles() ?? [])
        ]);
        
        return $oeuvre;
    }

    /**
     * Traite l'auteur depuis les relations MangaDx
     */
    private function processAuthor(Oeuvre $oeuvre, array $relationships): void
    {
        foreach ($relationships as $relation) {
            if ($relation['type'] === 'author') {
                $authorId = $relation['id'];
                
                // Utiliser les données expandues si disponibles
                if (isset($relation['attributes'])) {
                    $authorName = $relation['attributes']['name'];
                    $authorBio = $relation['attributes']['biography']['en'] ?? null;
                    
                    // Chercher si l'auteur existe déjà
                    $auteur = $this->auteurRepository->findOneBy(['nom' => $authorName]);
                    
                    if (!$auteur) {
                        $auteur = new Auteur();
                        $auteur->setNom($authorName);
                        $auteur->setBiographie($authorBio);
                        $this->entityManager->persist($auteur);
                        $this->logger->info("Nouvel auteur créé: {$authorName}");
                    }
                    
                    $oeuvre->setAuteur($auteur);
                    break; // Prendre seulement le premier auteur
                } else {
                    // Fallback: récupérer les données de l'auteur via API
                    try {
                        $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/author/' . $authorId);
                        if ($response->getStatusCode() === 200) {
                            $authorData = $response->toArray();
                            $authorAttributes = $authorData['data']['attributes'];
                            
                            // Chercher si l'auteur existe déjà
                            $auteur = $this->auteurRepository->findOneBy(['nom' => $authorAttributes['name']]);
                            
                            if (!$auteur) {
                                $auteur = new Auteur();
                                $auteur->setNom($authorAttributes['name']);
                                $auteur->setBiographie($authorAttributes['biography']['en'] ?? null);
                                $this->entityManager->persist($auteur);
                                $this->logger->info("Nouvel auteur créé via API: {$authorAttributes['name']}");
                            }
                            
                            $oeuvre->setAuteur($auteur);
                            break; // Prendre seulement le premier auteur
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning("Erreur lors de la récupération de l'auteur {$authorId}: " . $e->getMessage());
                        // Continuer sans auteur si erreur
                    }
                }
            }
        }
    }

    /**
     * Traite l'artiste depuis les relations MangaDx (distinct de l'auteur)
     */
    private function processArtist(Oeuvre $oeuvre, array $relationships): void
    {
        foreach ($relationships as $relation) {
            if ($relation['type'] === 'artist') {
                $artistId = $relation['id'];
                
                // Si l'artiste est différent de l'auteur, on pourrait l'ajouter comme co-auteur
                // ou créer un nouveau champ, mais pour l'instant on l'ignore car l'entité Oeuvre
                // n'a qu'un seul champ auteur
                
                // Utiliser les données expandues si disponibles
                if (isset($relation['attributes'])) {
                    $artistName = $relation['attributes']['name'];
                    $this->logger->info("Artiste trouvé: {$artistName} pour l'œuvre {$oeuvre->getTitre()}");
                } else {
                    $this->logger->info("Artiste ID trouvé: {$artistId} pour l'œuvre {$oeuvre->getTitre()}");
                }
                
                // Pour l'instant, ne rien faire car notre modèle ne supporte qu'un auteur
                // Dans le futur, on pourrait ajouter un champ "artiste" ou permettre plusieurs auteurs
            }
        }
    }

    /**
     * Traite les tags depuis les relations MangaDx
     */
    private function processTags(Oeuvre $oeuvre, array $relationships): void
    {
        foreach ($relationships as $relation) {
            if ($relation['type'] === 'tag' && isset($relation['attributes'])) {
                $tagName = $relation['attributes']['name']['en'] ?? null;
                
                if ($tagName) {
                    // Chercher si le tag existe déjà
                    $tag = $this->tagRepository->findOneBy(['nom' => $tagName]);
                    
                    if (!$tag) {
                        $tag = new Tag();
                        $tag->setNom($tagName);
                        $this->entityManager->persist($tag);
                    }
                    
                    $oeuvre->addTag($tag);
                }
            }
        }
    }

    /**
     * Traite la couverture depuis les relations MangaDx
     */
    private function processCoverArt(Oeuvre $oeuvre, array $relationships): void
    {
        foreach ($relationships as $relation) {
            if ($relation['type'] === 'cover_art') {
                $coverId = $relation['id'];
                
                try {
                    $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/cover/' . $coverId);
                    if ($response->getStatusCode() === 200) {
                        $coverData = $response->toArray();
                        $fileName = $coverData['data']['attributes']['fileName'];
                        
                        // Construire l'URL de la couverture
                        $coverUrl = "https://uploads.mangadx.org/covers/{$oeuvre->getMangadxId()}/{$fileName}";
                        $oeuvre->setCouverture($coverUrl);
                        break;
                    }
                } catch (\Exception $e) {
                    // Continuer sans couverture si erreur
                }
            }
        }
    }

    /**
     * Importe les chapitres d'une œuvre
     */
    private function importChapitres(Oeuvre $oeuvre, string $mangadxId): void
    {
        try {
            // Récupérer la liste des chapitres
            $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga/' . $mangadxId . '/feed', [
                'query' => [
                    'translatedLanguage' => ['fr', 'en'],
                    'order' => ['chapter' => 'asc'],
                    'limit' => 100
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return;
            }

            $chaptersData = $response->toArray();
            $chaptersToCreate = [];
            
            // Traiter les chapitres
            foreach ($chaptersData['data'] as $chapterData) {
                $attributes = $chapterData['attributes'];
                
                // Ignorer si pas de numéro de chapitre
                if (!isset($attributes['chapter']) || $attributes['chapter'] === null) {
                    continue;
                }
                
                $chapterNumber = (float) $attributes['chapter'];
                
                // Éviter les doublons (prendre le français en priorité)
                $key = (string) $chapterNumber;
                if (!isset($chaptersToCreate[$key]) || 
                    ($attributes['translatedLanguage'] === 'fr' && 
                     isset($chaptersToCreate[$key]) && 
                     $chaptersToCreate[$key]['translatedLanguage'] !== 'fr')) {
                    
                    $chaptersToCreate[$key] = [
                        'id' => $chapterData['id'],
                        'chapter' => $chapterNumber,
                        'title' => $attributes['title'] ?? "Chapitre {$chapterNumber}",
                        'translatedLanguage' => $attributes['translatedLanguage']
                    ];
                }
            }

            // Créer les chapitres en base
            $ordre = 1;
            foreach ($chaptersToCreate as $chapterInfo) {
                $chapitre = new Chapitre();
                $chapitre->setTitre($chapterInfo['title']);
                $chapitre->setOrdre($ordre++);
                $chapitre->setOeuvre($oeuvre);
                $chapitre->setResume("Chapitre importé depuis MangaDx");
                $chapitre->setMangadxChapterId($chapterInfo['id']);
                
                // Ne pas récupérer les pages lors de l'import pour éviter le rate limiting
                // Les pages seront récupérées dynamiquement quand nécessaire
                $chapitre->setPages([]);
                
                $this->logger->info("Chapitre {$chapterInfo['title']} importé avec mangadxChapterId: {$chapterInfo['id']}");
                
                $this->entityManager->persist($chapitre);
            }

        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'import des chapitres: " . $e->getMessage());
        }
    }

    /**
     * Met à jour une œuvre existante depuis MangaDx
     */
    public function updateOeuvre(string $mangadxId): ?Oeuvre
    {
        $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangadxId]);
        if (!$existingOeuvre) {
            throw new \Exception('Cette œuvre n\'existe pas dans la base de données');
        }

        try {
            // Récupérer les données mises à jour
            $mangaData = $this->fetchMangaData($mangadxId);
            if (!$mangaData) {
                throw new \Exception('Impossible de récupérer les données depuis MangaDx');
            }

            // Mettre à jour les champs qui peuvent changer
            $attributes = $mangaData['attributes'];
            
            // Mettre à jour la description si elle a changé
            $description = $attributes['description']['fr'] ?? $attributes['description']['en'] ?? null;
            if ($description) {
                $existingOeuvre->setResume(strip_tags($description));
            }

            // Mettre à jour les tags (synchronisation)
            if (isset($mangaData['relationships'])) {
                $this->syncTags($existingOeuvre, $mangaData['relationships']);
                $this->processCoverArt($existingOeuvre, $mangaData['relationships']);
            }

            // Synchroniser les nouveaux chapitres
            $this->syncChapitres($existingOeuvre, $mangadxId);

            $this->entityManager->flush();

            return $existingOeuvre;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Synchronise les tags sans supprimer les tags ajoutés manuellement
     */
    private function syncTags(Oeuvre $oeuvre, array $relationships): void
    {
        $apiTags = [];
        
        // Récupérer les tags de l'API
        foreach ($relationships as $relation) {
            if ($relation['type'] === 'tag' && isset($relation['attributes'])) {
                $tagName = $relation['attributes']['name']['en'] ?? null;
                if ($tagName) {
                    $apiTags[] = $tagName;
                }
            }
        }

        // Ajouter les nouveaux tags de l'API s'ils n'existent pas
        foreach ($apiTags as $tagName) {
            $hasTag = false;
            foreach ($oeuvre->getTags() as $existingTag) {
                if ($existingTag->getNom() === $tagName) {
                    $hasTag = true;
                    break;
                }
            }
            
            if (!$hasTag) {
                $tag = $this->tagRepository->findOneBy(['nom' => $tagName]);
                if (!$tag) {
                    $tag = new Tag();
                    $tag->setNom($tagName);
                    $this->entityManager->persist($tag);
                }
                $oeuvre->addTag($tag);
            }
        }
    }

    /**
     * Synchronise les chapitres (ajoute seulement les nouveaux)
     */
    private function syncChapitres(Oeuvre $oeuvre, string $mangadxId): void
    {
        try {
            $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga/' . $mangadxId . '/feed', [
                'query' => [
                    'translatedLanguage' => ['fr', 'en'],
                    'order' => ['chapter' => 'asc'],
                    'limit' => 100
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return;
            }

            $data = $response->toArray();
            $chaptersData = $data['data'] ?? [];

            // Récupérer les chapitres existants par leur numéro d'ordre
            $existingChapters = [];
            foreach ($oeuvre->getChapitres() as $chapitre) {
                $existingChapters[$chapitre->getOrdre()] = true;
            }

            // Ajouter seulement les nouveaux chapitres
            foreach ($chaptersData as $chapterData) {
                $attributes = $chapterData['attributes'];
                $chapterNumber = (int) ($attributes['chapter'] ?? 0);
                
                if (!isset($existingChapters[$chapterNumber]) && $chapterNumber > 0) {
                    $chapitre = new Chapitre();
                    $chapitre->setTitre($attributes['title'] ?? 'Chapitre ' . $chapterNumber);
                    $chapitre->setOrdre($chapterNumber);
                    $chapitre->setOeuvre($oeuvre);
                    
                    // Le résumé peut venir des attributs si disponible
                    if (isset($attributes['description'])) {
                        $chapitre->setResume($attributes['description']);
                    }
                    
                    $this->entityManager->persist($chapitre);
                }
            }

        } catch (\Exception $e) {
            // Continuer sans les chapitres en cas d'erreur
        }
    }

    /**
     * Importe ou met à jour une œuvre (méthode combinée)
     */
    public function importOrUpdateOeuvre(string $mangadxId): ?Oeuvre
    {
        $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangadxId]);
        
        if ($existingOeuvre) {
            return $this->updateOeuvre($mangadxId);
        } else {
            return $this->importOeuvre($mangadxId);
        }
    }

    /**
     * Récupère les URLs des pages d'un chapitre depuis l'API MangaDx
     */
    private function getChapterPages(string $chapterId): array
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
} 