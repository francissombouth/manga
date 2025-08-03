<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Créer le répertoire var s'il n'existe pas
if (!is_dir(dirname(__DIR__).'/var')) {
    mkdir(dirname(__DIR__).'/var', 0777, true);
}

// Supprimer l'ancienne base de test si elle existe
$testDbPath = dirname(__DIR__).'/var/test.db';
if (file_exists($testDbPath)) {
    unlink($testDbPath);
}

// Configuration de l'environnement de test
$_SERVER['APP_ENV'] = 'test';
$_SERVER['DATABASE_URL'] = 'sqlite:///%kernel.project_dir%/var/test.db';
$_SERVER['APP_SECRET'] = 'test-secret-key-for-testing-only';
$_SERVER['MAILER_DSN'] = 'null://null';
$_SERVER['MESSENGER_TRANSPORT_DSN'] = 'sync://';
$_SERVER['KERNEL_CLASS'] = 'App\Kernel';
$_SERVER['CORS_ALLOW_ORIGIN'] = 'http://localhost:3000';

// Désactiver les déprécations pour les tests
$_SERVER['SYMFONY_DEPRECATIONS_HELPER'] = 'weak';

// Configuration spécifique pour les tests
if ($_SERVER['APP_ENV'] === 'test') {
    // Configuration des permissions
    if ($_SERVER['APP_DEBUG'] ?? false) {
        umask(0000);
    }
}
