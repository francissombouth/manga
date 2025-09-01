<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPage(): void
    {
        $client = static::createClient();
        
        // Tester la page de connexion
        $client->request('GET', '/login');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testLogout(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        // Créer la base de données de test
        $this->createTestDatabase($entityManager);
        
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Test User');
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);
        $entityManager->persist($user);
        $entityManager->flush();

        // Se connecter d'abord
        $client->loginUser($user);

        // Tester la déconnexion
        $client->request('GET', '/logout');
        
        $this->assertResponseRedirects();
    }

    public function testRegistrationPage(): void
    {
        $client = static::createClient();
        
        // Tester la page d'inscription
        $client->request('GET', '/register');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    private function createTestDatabase(EntityManagerInterface $entityManager): void
    {
        // Supprimer toutes les tables existantes
        $connection = $entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();
        
        foreach ($tables as $table) {
            $connection->executeStatement('DROP TABLE IF EXISTS ' . $table);
        }
        
        // Créer le schéma
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $schemaTool->createSchema($metadata);
    }
} 