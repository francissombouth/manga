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
        
        // Créer la base de données de test
        $this->createTestDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la base de données de test
        $this->cleanupTestDatabase();
    }

    private function createTestDatabase(): void
    {
        $container = static::getContainer();
        
        // Créer la base de données SQLite
        $entityManager = $container->get('doctrine')->getManager();
        $connection = $entityManager->getConnection();
        
        // Créer les tables
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $schemaTool->createSchema($metadata);
    }

    private function cleanupTestDatabase(): void
    {
        $container = static::getContainer();
        
        // Fermer les connexions
        $entityManager = $container->get('doctrine')->getManager();
        $entityManager->close();
        
        // Supprimer la base de test
        $testDbPath = dirname(__DIR__).'/var/test.db';
        if (file_exists($testDbPath)) {
            unlink($testDbPath);
        }
    }
} 