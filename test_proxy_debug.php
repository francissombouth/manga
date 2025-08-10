<?php

// Test de validation des URLs pour debug du proxy
function isMangaDxUrl(string $url): bool
{
    $allowedDomains = [
        'uploads.mangadx.org',
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

    echo "URL testée: $url\n";
    
    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'] ?? '';
    
    echo "Host extrait: '$host'\n";
    echo "Domaines autorisés:\n";
    foreach ($allowedDomains as $domain) {
        echo "  - $domain\n";
    }
    
    echo "Tests de validation:\n";
    
    foreach ($allowedDomains as $domain) {
        $test1 = str_ends_with($host, $domain);
        $test2 = str_ends_with($host, '.' . $domain);
        
        echo "  $domain: ends_with='$domain' -> " . ($test1 ? "✅" : "❌") . 
             " | ends_with='.$domain' -> " . ($test2 ? "✅" : "❌") . "\n";
        
        if ($test1 || $test2) {
            echo "✅ MATCH trouvé avec $domain\n";
            return true;
        }
    }
    
    echo "❌ Aucun match trouvé\n";
    return false;
}

// Test avec votre URL
$testUrl = 'https://i.ibb.co/WW6C0DDS/7b3ce8fc7a86a3949fd23d5032e73f44.jpg';
echo "=== TEST DEBUG PROXY ===\n";
$result = isMangaDxUrl($testUrl);
echo "\nRésultat final: " . ($result ? "AUTORISÉ" : "REJETÉ") . "\n";
