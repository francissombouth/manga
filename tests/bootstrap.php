<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    // Configuration spécifique pour les tests sans charger .env.test.local
    $_SERVER['APP_ENV'] = 'test';
    $_SERVER['DATABASE_URL'] = 'sqlite:///'.dirname(__DIR__).'/var/test.db';
    $_SERVER['APP_SECRET'] = 'test-secret-key-for-testing-only';
    $_SERVER['MAILER_DSN'] = 'null://null';
    $_SERVER['MESSENGER_TRANSPORT_DSN'] = 'sync://';
    $_SERVER['SYMFONY_DEPRECATIONS_HELPER'] = 'weak';
    $_SERVER['KERNEL_CLASS'] = 'App\Kernel';
}

// Configuration spécifique pour les tests
if ($_SERVER['APP_ENV'] === 'test') {
    // Créer le répertoire var s'il n'existe pas
    if (!is_dir(dirname(__DIR__).'/var')) {
        mkdir(dirname(__DIR__).'/var', 0777, true);
    }
    
    // Supprimer la base de test existante pour un environnement propre
    $testDbPath = dirname(__DIR__).'/var/test.db';
    if (file_exists($testDbPath)) {
        unlink($testDbPath);
    }
    
    // Configuration des permissions
    if ($_SERVER['APP_DEBUG'] ?? false) {
        umask(0000);
    }
}
