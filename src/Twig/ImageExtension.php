<?php

namespace App\Twig;

use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImageExtension extends AbstractExtension
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('smart_image_url', [$this, 'getSmartImageUrl']),
        ];
    }

    /**
     * Retourne l'URL d'image appropriée (directe ou via proxy)
     * Détecte automatiquement si l'image a besoin du proxy
     */
    public function getSmartImageUrl(?string $imageUrl): string
    {
        // Si pas d'URL, retourner l'URL du proxy sans paramètre (placeholder)
        if (!$imageUrl || empty(trim($imageUrl))) {
            return $this->router->generate('image_proxy');
        }

        // URLs qui ont besoin du proxy (sources externes avec restrictions CORS)
        $needProxyDomains = [
            'uploads.mangadx.org',
            'uploads.mangadex.org', 
            'api.mangadx.org',
            'api.mangadex.org',
            'mangadx.org',
            'mangadex.org',
            'letsenhance.io',
            'static.letsenhance.io'
        ];

        // URLs qui peuvent être utilisées directement (pas de restrictions CORS)
        $directDomains = [
            'ibb.co',
            'i.ibb.co',
            'imgur.com',
            'i.imgur.com'
        ];

        $parsedUrl = parse_url($imageUrl);
        $host = $parsedUrl['host'] ?? '';

        // Si c'est une URL relative ou locale, l'utiliser directement
        if (!$host || $this->isLocalUrl($imageUrl)) {
            return $imageUrl;
        }

        // Vérifier si le domaine nécessite le proxy
        foreach ($needProxyDomains as $domain) {
            if (str_ends_with($host, $domain) || str_ends_with($host, '.' . $domain)) {
                return $this->router->generate('image_proxy', ['url' => $imageUrl]);
            }
        }

        // Vérifier si le domaine peut être utilisé directement
        foreach ($directDomains as $domain) {
            if (str_ends_with($host, $domain) || str_ends_with($host, '.' . $domain)) {
                return $imageUrl;
            }
        }

        // Par défaut, pour les domaines inconnus, utiliser le proxy par sécurité
        return $this->router->generate('image_proxy', ['url' => $imageUrl]);
    }

    /**
     * Vérifie si une URL est locale/relative
     */
    private function isLocalUrl(string $url): bool
    {
        // URL relative
        if (str_starts_with($url, '/')) {
            return true;
        }

        // URL sans protocole ni domaine
        if (!str_contains($url, '://')) {
            return true;
        }

        return false;
    }
}
