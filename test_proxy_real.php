<?php

// Test réel du proxy pour reproduire le problème imgbb

echo "=== TEST PROXY RÉEL ===\n";

$testUrl = 'https://i.ibb.co/WW6C0DDS/7b3ce8fc7a86a3949fd23d5032e73f44.jpg';
$encodedUrl = urlencode($testUrl);

// Construire l'URL du proxy local
$proxyUrl = "http://localhost:8000/proxy/image?url=" . $encodedUrl;

echo "URL testée: $testUrl\n";
echo "URL encodée: $encodedUrl\n";  
echo "URL du proxy: $proxyUrl\n\n";

// Tester avec cURL
echo "Test avec cURL...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $proxyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);

curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Content-Type: $contentType\n";

if ($error) {
    echo "Erreur cURL: $error\n";
} else {
    // Séparer les headers du body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo "Headers:\n$headers\n";
    
    if (strpos($contentType, 'image/svg') !== false) {
        echo "RÉSULTAT: Image placeholder SVG renvoyée (échec)\n";
        echo "Contenu SVG:\n" . substr($body, 0, 500) . "...\n";
    } elseif (strpos($contentType, 'image/') !== false) {
        echo "RÉSULTAT: Image réelle renvoyée (succès)\n";
        echo "Taille de l'image: " . strlen($body) . " octets\n";
    } else {
        echo "RÉSULTAT: Type de contenu inattendu\n";
        echo "Contenu: " . substr($body, 0, 500) . "...\n";
    }
}

echo "\n=== VÉRIFICATION DIRECTE DE L'URL ===\n";

// Test direct de l'URL imgbb
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$directResponse = curl_exec($ch);
$directHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$directContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$directError = curl_error($ch);

curl_close($ch);

echo "Test direct de l'URL imgbb:\n";
echo "Code HTTP: $directHttpCode\n";
echo "Content-Type: $directContentType\n";

if ($directError) {
    echo "Erreur: $directError\n";
} else {
    echo "RÉSULTAT: URL imgbb directement accessible\n";
}
