<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CoverService
{
    private const GOOGLE_BOOKS_API = 'https://www.googleapis.com/books/v1/volumes';
    private const PLACEHOLDER_URL = '/images/placeholder-book.jpg';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ParameterBagInterface $params
    ) {}

    /**
     * Recherche et télécharge une image de couverture pour une œuvre
     */
    public function searchAndDownloadCover(string $titre, ?string $auteur = null): ?string
    {
        try {
            $coverUrl = $this->searchCoverUrl($titre, $auteur);
            
            if ($coverUrl) {
                return $this->downloadCover($coverUrl, $titre);
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche de couverture: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Recherche l'URL d'une couverture via Google Books API
     */
    private function searchCoverUrl(string $titre, ?string $auteur = null): ?string
    {
        try {
            // Construire la requête de recherche
            $query = $titre;
            if ($auteur) {
                $query .= ' ' . $auteur;
            }

            $response = $this->httpClient->request('GET', self::GOOGLE_BOOKS_API, [
                'query' => [
                    'q' => $query,
                    'maxResults' => 5,
                    'fields' => 'items(volumeInfo(title,authors,imageLinks))'
                ],
                'headers' => [
                    'User-Agent' => 'BiblioTheque/1.0 (Educational Project)'
                ]
            ]);

            $data = $response->toArray();
            
            if (!isset($data['items']) || empty($data['items'])) {
                $this->logger->info("Aucun résultat trouvé pour: {$query}");
                return null;
            }

            // Chercher la meilleure correspondance
            foreach ($data['items'] as $item) {
                $volumeInfo = $item['volumeInfo'] ?? [];
                
                // Vérifier si le titre correspond approximativement
                $bookTitle = $volumeInfo['title'] ?? '';
                if ($this->titleMatches($titre, $bookTitle)) {
                    $imageLinks = $volumeInfo['imageLinks'] ?? [];
                    
                    // Préférer la grande image, sinon la petite
                    if (isset($imageLinks['large'])) {
                        return $imageLinks['large'];
                    } elseif (isset($imageLinks['medium'])) {
                        return $imageLinks['medium'];
                    } elseif (isset($imageLinks['small'])) {
                        return $imageLinks['small'];
                    } elseif (isset($imageLinks['thumbnail'])) {
                        return $imageLinks['thumbnail'];
                    }
                }
            }

            $this->logger->info("Aucune image de couverture trouvée pour: {$query}");
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche Google Books: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si deux titres correspondent approximativement
     */
    private function titleMatches(string $title1, string $title2): bool
    {
        $title1 = strtolower(trim($title1));
        $title2 = strtolower(trim($title2));
        
        // Correspondance exacte
        if ($title1 === $title2) {
            return true;
        }
        
        // Correspondance partielle (un titre contient l'autre)
        if (strlen($title1) > 3 && str_contains($title2, $title1)) {
            return true;
        }
        
        if (strlen($title2) > 3 && str_contains($title1, $title2)) {
            return true;
        }
        
        // Similarité élevée
        similar_text($title1, $title2, $percent);
        return $percent > 80;
    }

    /**
     * Télécharge et sauvegarde une image de couverture
     */
    private function downloadCover(string $imageUrl, string $titre): ?string
    {
        try {
            // Créer le répertoire de destination s'il n'existe pas
            $uploadsDir = $this->params->get('kernel.project_dir') . '/public/uploads/covers';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Télécharger l'image
            $response = $this->httpClient->request('GET', $imageUrl, [
                'timeout' => 30
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error("Erreur HTTP {$response->getStatusCode()} lors du téléchargement de l'image");
                return null;
            }

            $content = $response->getContent();
            
            // Générer un nom de fichier unique
            $extension = $this->getImageExtension($imageUrl, $content);
            $filename = $this->generateFilename($titre) . '.' . $extension;
            $filepath = $uploadsDir . '/' . $filename;

            // Sauvegarder le fichier
            file_put_contents($filepath, $content);

            $this->logger->info("Image de couverture téléchargée: {$filename}");
            
            return '/uploads/covers/' . $filename;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du téléchargement de l\'image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Détermine l'extension du fichier image
     */
    private function getImageExtension(string $url, string $content): string
    {
        // D'abord essayer de détecter depuis le contenu
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($content);
        
        switch ($mimeType) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            case 'image/webp':
                return 'webp';
        }
        
        // Fallback sur l'URL
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        return $extension ?: 'jpg';
    }

    /**
     * Génère un nom de fichier sécurisé à partir du titre
     */
    private function generateFilename(string $titre): string
    {
        // Nettoyer le titre pour en faire un nom de fichier valide
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $titre);
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');
        
        // Limiter la longueur
        $filename = substr($filename, 0, 100);
        
        // Ajouter un timestamp pour éviter les collisions
        return $filename . '_' . time();
    }

    /**
     * Retourne l'URL du placeholder par défaut
     */
    public function getPlaceholderUrl(): string
    {
        return self::PLACEHOLDER_URL;
    }

    /**
     * Supprime un fichier de couverture
     */
    public function deleteCover(string $coverPath): bool
    {
        try {
            $fullPath = $this->params->get('kernel.project_dir') . '/public' . $coverPath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression de la couverture: ' . $e->getMessage());
        }
        
        return false;
    }
} 