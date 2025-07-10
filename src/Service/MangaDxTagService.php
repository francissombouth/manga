<?php

namespace App\Service;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MangaDxTagService
{
    private const MANGADX_API_BASE = 'https://api.mangadex.org';
    
    private array $tagCache = [];
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private TagRepository $tagRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Récupère tous les tags de type "genre" depuis l'API MangaDx et les synchronise en base
     */
    public function syncAllTags(): array
    {
        try {
            $this->logger->info("🏷️ Début de la synchronisation des genres MangaDx");
            
            $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga/tag', [
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error("Erreur HTTP lors de la récupération des genres: " . $response->getStatusCode());
                return [];
            }

            $data = $response->toArray();
            $mangadxTags = $data['data'] ?? [];
            
            // Filtrer pour ne garder que les tags de type "genre"
            $mangadxTags = array_filter($mangadxTags, function($tag) {
                return $tag['attributes']['group'] === 'genre';
            });
            
            $this->logger->info("📊 Genres récupérés depuis MangaDx: " . count($mangadxTags));
            
            $syncedTags = [];
            $created = 0;
            $updated = 0;

            foreach ($mangadxTags as $mangadxTag) {
                $mangadxId = $mangadxTag['id'];
                $attributes = $mangadxTag['attributes'];
                
                // Récupérer le nom en français en priorité, sinon anglais
                $tagName = $attributes['name']['fr'] ?? $attributes['name']['en'] ?? null;
                
                if (!$tagName) {
                    continue;
                }

                // Chercher si le tag existe déjà
                $tag = $this->tagRepository->findOneBy(['nom' => $tagName]);
                
                if (!$tag) {
                    $tag = new Tag();
                    $tag->setNom($tagName);
                    $tag->setMangadxId($mangadxId); // Stocker l'ID MangaDex
                    $this->entityManager->persist($tag);
                    $created++;
                    $this->logger->info("✅ Nouveau genre créé: " . $tagName);
                } else {
                    if (!$tag->getMangadxId()) {
                        $tag->setMangadxId($mangadxId);
                    }
                    $updated++;
                    $this->logger->info("🔄 Genre existant: " . $tagName);
                }
                
                // Ajouter à notre cache avec l'ID MangaDx
                $this->tagCache[$mangadxId] = $tag;
                $syncedTags[] = $tag;
            }

            $this->entityManager->flush();
            
            $this->logger->info("🎯 Synchronisation terminée", [
                'total_genres' => count($mangadxTags),
                'created' => $created,
                'updated' => $updated,
                'synced' => count($syncedTags)
            ]);

            return $syncedTags;

        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la synchronisation des genres: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouve les genres pour une œuvre spécifique
     */
    public function findTagsForManga(string $mangadxId): array
    {
        try {
            $this->logger->info("🔍 Recherche des genres pour l'œuvre: " . $mangadxId);
            
            // Utiliser l'endpoint de recherche pour trouver cette œuvre et ses tags
            $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga/' . $mangadxId, [
                'query' => [
                    'includes[]' => 'tag'
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning("Erreur HTTP lors de la recherche de genres: " . $response->getStatusCode());
                return [];
            }

            $data = $response->toArray();
            $manga = $data['data'] ?? null;
            
            if (!$manga) {
                $this->logger->info("Aucun résultat trouvé pour l'œuvre: " . $mangadxId);
                return [];
            }

            $relationships = $manga['relationships'] ?? [];
            
            // Chercher les relations de type tag qui sont des genres
            $tagRelations = array_filter($relationships, function($rel) {
                return $rel['type'] === 'tag' && isset($rel['attributes']) && $rel['attributes']['group'] === 'genre';
            });
            
            $this->logger->info("📊 Genres trouvés dans les relations: " . count($tagRelations));
            
            $tags = [];
            foreach ($tagRelations as $tagRelation) {
                $mangadxTagId = $tagRelation['id'];
                
                // Si on a le tag dans notre cache
                if (isset($this->tagCache[$mangadxTagId])) {
                    $tags[] = $this->tagCache[$mangadxTagId];
                } else {
                    // Récupérer les détails du tag depuis l'API
                    $tag = $this->getTagByMangadxId($mangadxTagId);
                    if ($tag) {
                        $tags[] = $tag;
                    }
                }
            }
            
            $this->logger->info("✅ Genres récupérés pour l'œuvre", [
                'manga_id' => $mangadxId,
                'genres_count' => count($tags),
                'genre_names' => array_map(fn($tag) => $tag->getNom(), $tags)
            ]);

            return $tags;

        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la recherche de genres pour l'œuvre {$mangadxId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère un tag spécifique par son ID MangaDx
     */
    private function getTagByMangadxId(string $mangadxTagId): ?Tag
    {
        try {
            // Si on ne l'a pas encore fait, synchroniser tous les tags
            if (empty($this->tagCache)) {
                $this->syncAllTags();
            }
            
            // Chercher d'abord dans le cache
            if (isset($this->tagCache[$mangadxTagId])) {
                return $this->tagCache[$mangadxTagId];
            }

            // Sinon chercher en base de données
            $tag = $this->tagRepository->findOneBy(['mangadxId' => $mangadxTagId]);
            if ($tag) {
                $this->tagCache[$mangadxTagId] = $tag;
                return $tag;
            }

            // Si toujours pas trouvé, récupérer depuis l'API
            $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga/tag/' . $mangadxTagId);
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                $tagData = $data['data'];
                
                if ($tagData['attributes']['group'] === 'genre') {
                    $tagName = $tagData['attributes']['name']['fr'] ?? $tagData['attributes']['name']['en'] ?? null;
                    if ($tagName) {
                        $tag = new Tag();
                        $tag->setNom($tagName);
                        $tag->setMangadxId($mangadxTagId);
                        $this->entityManager->persist($tag);
                        $this->entityManager->flush();
                        
                        $this->tagCache[$mangadxTagId] = $tag;
                        return $tag;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération du genre {$mangadxTagId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Efface le cache des tags (utile pour forcer une resynchronisation)
     */
    public function clearCache(): void
    {
        $this->tagCache = [];
    }

    /**
     * Récupère la liste des tags populaires avec leurs IDs
     */
    public function getPopularTags(): array
    {
        return [
            'Action' => '391b0423-d847-456f-aff0-8b0cfc03066b',
            'Romance' => '423e2eae-a7a2-4a8b-ac03-a8351462d71d',
            'Comedy' => '4d32cc48-9f00-4cca-9b5a-a839f0764984',
            'Drama' => 'b9af3a63-f058-46de-a9a0-e0c13906197a',
            'Fantasy' => 'cdc58593-87dd-415e-bbc0-2ec27bf404cc',
            'School Life' => 'caaa44eb-cd40-4177-b930-79d3ef2afe87',
            'Slice of Life' => 'e5301a23-ebd9-49dd-a0cb-2add944c7fe9',
            'Supernatural' => 'eabc5b4c-6aff-42f3-b657-3e90cbd00b75',
            'Ecchi' => '9f5ceea7-dc8c-4ec8-9ff8-95eb7bc9e5b6',
            'Sports' => '69964a64-2f90-4d33-beeb-f3ed2875eb4c'
        ];
    }
} 