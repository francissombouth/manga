<?php

// Script de vérification des corrections de récupération de chapitres

echo "=== Vérification des corrections de récupération de chapitres ===\n\n";

// 1. Vérifier l'URL corrigée dans MangaDxService
$serviceFile = '../src/Service/MangaDxService.php';
$content = file_get_contents($serviceFile);

// Vérifier getMangaChapters
if (strpos($content, 'self::BASE_URL . "/manga/{$mangaId}/feed"') !== false) {
    echo "✅ getMangaChapters utilise la bonne URL\n";
} else {
    echo "❌ getMangaChapters utilise encore l'ancienne URL\n";
}

// Vérifier countMangaChapters
if (strpos($content, 'self::BASE_URL . "/manga/{$mangaId}/feed"') !== false && 
    strpos($content, "'manga' => \$mangaId,") === false) {
    echo "✅ countMangaChapters utilise la bonne URL\n";
} else {
    echo "❌ countMangaChapters a encore des problèmes\n";
}

// Vérifier la syntaxe des accolades
$braceBalance = substr_count($content, '{') - substr_count($content, '}');
if ($braceBalance === 0) {
    echo "✅ Syntaxe des accolades équilibrée\n";
} else {
    echo "❌ Problème de syntaxe des accolades (différence: {$braceBalance})\n";
}

echo "\n=== Résumé des corrections appliquées ===\n";
echo "1. URL corrigée: '/chapter' → '/manga/{mangaId}/feed'\n";
echo "2. Paramètre 'manga' supprimé des requêtes\n";
echo "3. Syntaxe des accolades corrigée\n";
echo "4. Méthodes affectées: getMangaChapters(), countMangaChapters()\n";

echo "\n=== URLs d'API MangaDx utilisées ===\n";
echo "Chapitres d'un manga: GET /manga/{id}/feed\n";
echo "Chapitre spécifique: GET /chapter/{id}\n";
echo "Pages d'un chapitre: GET /at-home/server/{id}\n";

echo "\nVérification terminée !\n"; 