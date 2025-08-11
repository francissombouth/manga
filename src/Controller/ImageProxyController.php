<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ImageProxyController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    #[Route('/proxy/image', name: 'image_proxy', methods: ['GET'])]
    public function proxyImage(Request $request): Response
    {
        // Récupérer l'URL depuis les paramètres de requête avec l'objet Request
        $url = $request->query->get('url');
        $quality = $request->query->get('quality', 'auto'); // auto, high, medium, low
        
        if (!$url) {
            $this->logger->warning('Proxy image: URL manquante');
            return $this->createPlaceholderResponse();
        }

        // Décoder l'URL si elle est encodée
        $url = urldecode($url);
        
        // Générer une clé de cache basée sur l'URL et la qualité
        $cacheKey = md5($url . $quality);
        $cacheDir = $this->getParameter('kernel.cache_dir') . '/images';
        $cacheFile = $cacheDir . '/' . $cacheKey . '.img';
        
        // Vérifier si l'image est en cache et toujours valide (24h)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            $this->logger->info('Proxy image: cache hit pour ' . $url);
            return $this->createCachedImageResponse($cacheFile, $request);
        }
        
        $this->logger->info('Proxy image demandé pour: ' . $url);

        // Vérifier que l'URL provient bien de domaines autorisés
        if (!$this->isMangaDxUrl($url)) {
            $this->logger->warning('Proxy image: URL non autorisée', ['url' => $url]);
            return $this->createPlaceholderResponse();
        }

        $this->logger->info('Proxy image: URL autorisée, récupération en cours...');

        try {
            // Headers pour imiter une requête depuis MangaDx
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Referer' => 'https://mangadex.org/',
                'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,fr;q=0.8',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ];

            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
                'timeout' => 10,
                'max_redirects' => 3,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Image non trouvée');
            }

            $content = $response->getContent();
            $contentType = $response->getHeaders()['content-type'][0] ?? 'image/jpeg';

            // Sauvegarder en cache
            $this->saveToCache($cacheFile, $content, $contentType);
            
            // Créer la réponse avec l'image
            $imageResponse = new Response($content);
            $imageResponse->headers->set('Content-Type', $contentType);
            $imageResponse->headers->set('Cache-Control', 'public, max-age=86400'); // Cache 24h
            $imageResponse->headers->set('Access-Control-Allow-Origin', '*');
            $imageResponse->headers->set('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T'));
            $imageResponse->headers->set('ETag', '"' . md5($content) . '"');
            
            return $imageResponse;

        } catch (\Exception $e) {
            $this->logger->warning('Erreur proxy image: ' . $e->getMessage(), ['url' => $url]);
            return $this->createPlaceholderResponse();
        }
    }

    private function isMangaDxUrl(string $url): bool
    {
        $allowedDomains = [
            'uploads.mangadex.org',
            'mangadx.org',
            'mangadex.org',
            'api.mangadx.org',
            'api.mangadex.org',
            'mangadx.network',
            'mangadex.network',
            'letsenhance.io',
            'static.letsenhance.io',
            'ibb.co',
            'i.ibb.co'
        ];

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        foreach ($allowedDomains as $domain) {
            // Vérifier si l'host se termine par le domaine (pour inclure les sous-domaines)
            if (str_ends_with($host, $domain) || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    private function createPlaceholderResponse(): Response
    {
        // Créer une image placeholder SVG
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="280" height="400" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#1a1a2e;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#16213e;stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="100%" height="100%" fill="url(#grad1)"/>
  <text x="50%" y="45%" dominant-baseline="middle" text-anchor="middle" fill="#00d4ff" font-family="Inter, sans-serif" font-size="24" font-weight="bold">📖</text>
  <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="#a0aec0" font-family="Inter, sans-serif" font-size="14">Manga Cover</text>
  <text x="50%" y="65%" dominant-baseline="middle" text-anchor="middle" fill="#6c757d" font-family="Inter, sans-serif" font-size="12">Image non disponible</text>
</svg>';

        $response = new Response($svg);
        $response->headers->set('Content-Type', 'image/svg+xml');
        $response->headers->set('Cache-Control', 'public, max-age=86400'); // Cache 24h
        
        return $response;
    }
    
    /**
     * Sauvegarde l'image en cache local
     */
    private function saveToCache(string $cacheFile, string $content, string $contentType): void
    {
        try {
            $cacheDir = dirname($cacheFile);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            
            // Sauvegarder avec métadonnées
            $cacheData = [
                'content_type' => $contentType,
                'content' => base64_encode($content),
                'timestamp' => time(),
                'size' => strlen($content)
            ];
            
            file_put_contents($cacheFile, json_encode($cacheData));
            $this->logger->info('Image mise en cache: ' . basename($cacheFile));
        } catch (\Exception $e) {
            $this->logger->warning('Erreur mise en cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Crée une réponse depuis le cache
     */
    private function createCachedImageResponse(string $cacheFile, Request $request): Response
    {
        try {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            
            if (!$cacheData || !isset($cacheData['content'])) {
                throw new \Exception('Cache corrompu');
            }
            
            $content = base64_decode($cacheData['content']);
            $contentType = $cacheData['content_type'] ?? 'image/jpeg';
            $lastModified = gmdate('D, d M Y H:i:s \G\M\T', $cacheData['timestamp']);
            $etag = '"' . md5($content) . '"';
            
            // Vérifier si le client a déjà cette version (304 Not Modified)
            $clientEtag = $request->headers->get('If-None-Match');
            $clientModified = $request->headers->get('If-Modified-Since');
            
            if ($clientEtag === $etag || $clientModified === $lastModified) {
                return new Response(null, 304, [
                    'Cache-Control' => 'public, max-age=86400',
                    'ETag' => $etag,
                    'Last-Modified' => $lastModified,
                ]);
            }
            
            return new Response($content, 200, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'public, max-age=86400',
                'Access-Control-Allow-Origin' => '*',
                'Last-Modified' => $lastModified,
                'ETag' => $etag,
                'X-Cache' => 'HIT',
            ]);
            
        } catch (\Exception $e) {
            $this->logger->warning('Erreur lecture cache: ' . $e->getMessage());
            // Supprimer le cache corrompu
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            throw $e;
        }
    }
} 