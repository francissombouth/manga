<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImgBBService
{
    private const IMGBB_API_URL = 'https://api.imgbb.com/1/upload';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ParameterBagInterface $params
    ) {}

    /**
     * Upload une image vers ImgBB et retourne l'URL
     */
    public function uploadImage(UploadedFile $file): ?array
    {
        try {
            // Vérifier le type de fichier
            if (!$this->isValidImageType($file)) {
                throw new \Exception('Type de fichier non supporté. Utilisez JPG, PNG, GIF ou WEBP.');
            }

            // Vérifier la taille (max 32MB pour ImgBB)
            if ($file->getSize() > 32 * 1024 * 1024) {
                throw new \Exception('Fichier trop volumineux. Taille maximum : 32MB.');
            }

            // Récupérer la clé API depuis les variables d'environnement
            $apiKey = $this->params->get('app.imgbb_api_key');
            if (!$apiKey) {
                throw new \Exception('Clé API ImgBB non configurée. Ajoutez IMGBB_API_KEY dans votre fichier .env');
            }

            // Lire le contenu du fichier
            $imageContent = file_get_contents($file->getPathname());
            if ($imageContent === false) {
                throw new \Exception('Impossible de lire le fichier.');
            }

            // Encoder en base64
            $base64Image = base64_encode($imageContent);

            // Préparer les données pour l'API
            $data = [
                'key' => $apiKey,
                'image' => $base64Image,
                'name' => $file->getClientOriginalName(),
            ];

            // Faire la requête à l'API ImgBB
            $response = $this->httpClient->request('POST', self::IMGBB_API_URL, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($data),
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Erreur HTTP: ' . $response->getStatusCode());
            }

            $result = json_decode($response->getContent(), true);

            if (!$result['success']) {
                throw new \Exception('Erreur ImgBB: ' . ($result['error']['message'] ?? 'Erreur inconnue'));
            }

            $this->logger->info('Image uploadée avec succès vers ImgBB', [
                'filename' => $file->getClientOriginalName(),
                'imgbb_id' => $result['data']['id'],
                'url' => $result['data']['url']
            ]);

            return [
                'success' => true,
                'url' => $result['data']['url'],
                'delete_url' => $result['data']['delete_url'],
                'id' => $result['data']['id'],
                'title' => $result['data']['title'],
                'size' => $result['data']['size'],
                'width' => $result['data']['width'],
                'height' => $result['data']['height'],
                'filename' => $file->getClientOriginalName()
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'upload vers ImgBB: ' . $e->getMessage(), [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload plusieurs images
     */
    public function uploadMultipleImages(array $files): array
    {
        $results = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $results[] = $this->uploadImage($file);
            }
        }

        return $results;
    }

    /**
     * Vérifie si le type de fichier est supporté
     */
    private function isValidImageType(UploadedFile $file): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        return in_array($file->getMimeType(), $allowedMimeTypes);
    }

    /**
     * Supprime une image depuis ImgBB (si nécessaire)
     */
    public function deleteImage(string $deleteUrl): bool
    {
        try {
            $response = $this->httpClient->request('GET', $deleteUrl, [
                'timeout' => 10
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression d\'image ImgBB: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retourne les informations sur l'API
     */
    public function getApiInfo(): array
    {
        $apiKey = $this->params->get('app.imgbb_api_key');
        
        return [
            'name' => 'ImgBB',
            'max_file_size' => '32MB',
            'supported_formats' => ['JPG', 'PNG', 'GIF', 'WEBP'],
            'api_url' => self::IMGBB_API_URL,
            'api_key_configured' => !empty($apiKey)
        ];
    }
} 