<?php

require_once 'vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();

// Test avec une œuvre spécifique
$mangadxId = '32d76d19-8a05-4db0-9fc2-e0b0648fe9d0'; // Solo Leveling

echo "=== Test: Examen complet des données ===\n";
try {
    // Récupérer les données de base
    $response = $client->request('GET', "https://api.mangadex.org/manga/{$mangadxId}", [
        'headers' => ['User-Agent' => 'MangaTheque/1.0 (Educational Project)']
    ]);

    if ($response->getStatusCode() === 200) {
        $data = $response->toArray();
        echo "Titre: " . ($data['data']['attributes']['title']['en'] ?? 'N/A') . "\n";
        
        echo "\n=== Attributs de l'œuvre ===\n";
        $attributes = $data['data']['attributes'];
        foreach ($attributes as $key => $value) {
            echo "{$key}: " . json_encode($value) . "\n";
        }
        
        echo "\n=== Relations ===\n";
        $relationships = $data['data']['relationships'] ?? [];
        foreach ($relationships as $relation) {
            echo "Type: " . $relation['type'] . " | ID: " . $relation['id'] . "\n";
        }
        
        // Chercher des informations sur les tags dans les attributs
        if (isset($attributes['tags'])) {
            echo "\n=== Tags dans les attributs ===\n";
            echo json_encode($attributes['tags'], JSON_PRETTY_PRINT) . "\n";
        }
        
        if (isset($attributes['genres'])) {
            echo "\n=== Genres dans les attributs ===\n";
            echo json_encode($attributes['genres'], JSON_PRETTY_PRINT) . "\n";
        }
        
    } else {
        echo "Erreur HTTP: " . $response->getStatusCode() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
} 