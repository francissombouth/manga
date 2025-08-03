<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestKernel extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected static function createKernel(array $options = []): \App\Kernel
    {
        $kernel = parent::createKernel($options);
        $kernel->boot();
        return $kernel;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupTestDatabase();
    }

    private function createTestDatabase(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        // Supprimer toutes les tables existantes
        $connection = $entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();
        
        foreach ($tables as $table) {
            $connection->executeStatement('DROP TABLE IF EXISTS ' . $table);
        }
        
        // Créer le schéma complet avec toutes les entités
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $schemaTool->createSchema($metadata);
        
        // Vérifier que les tables principales sont créées
        $tablesAfter = $schemaManager->listTableNames();
        if (empty($tablesAfter)) {
            throw new \RuntimeException('Aucune table n\'a été créée dans la base de données de test');
        }
    }

    private function cleanupTestDatabase(): void
    {
        $testDbPath = dirname(__DIR__).'/var/test.db';
        if (file_exists($testDbPath)) {
            unlink($testDbPath);
        }
    }
} 