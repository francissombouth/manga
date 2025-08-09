<?php

// Test de l'URL spécifique trouvée en base
$imageUrl = 'https://i.ibb.co/s9td3jYW/7b3ce8fc7a86a3949fd23d5032e73f44.jpg';

echo "🔍 Test de l'URL spécifique en base\n";
echo "==================================\n";
echo "URL: $imageUrl\n\n";

// Test direct de l'URL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $imageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // Juste les headers
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "Code HTTP: $httpCode\n";
echo "Type de contenu: $contentType\n";

if (curl_errno($ch)) {
    echo "❌ Erreur cURL: " . curl_error($ch) . "\n";
} else {
    if ($httpCode === 200) {
        echo "✅ L'URL fonctionne parfaitement !\n";
    } else {
        echo "❌ L'URL ne fonctionne pas (code $httpCode)\n";
    }
}

curl_close($ch);

// Maintenant testons le format JSON comme il est stocké
echo "\n🔍 Test du format JSON\n";
echo "====================\n";

$jsonData = '{"1":"https://i.ibb.co/s9td3jYW/7b3ce8fc7a86a3949fd23d5032e73f44.jpg"}';
$pagesArray = json_decode($jsonData, true);

echo "JSON original: $jsonData\n";
echo "Après json_decode:\n";
var_dump($pagesArray);

echo "\nItération comme dans Twig:\n";
foreach ($pagesArray as $key => $page) {
    echo "Clé: $key, Valeur: $page\n";
}
