<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        // Créer la base de données de test
        $this->createTestDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupTestDatabase();
    }

    private function createTestDatabase(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $schemaTool->createSchema($metadata);
    }

    private function cleanupTestDatabase(): void
    {
        $this->entityManager->close();
        $testDbPath = dirname(__DIR__, 2).'/var/test.db';
        if (file_exists($testDbPath)) {
            unlink($testDbPath);
        }
    }

    public function testLogin(): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Tester la page de connexion
        $this->client->request('GET', '/login');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        // Tester la connexion
        $this->client->submitForm('Se connecter', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertResponseRedirects();
    }

    public function testLogout(): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Se connecter d'abord
        $this->client->loginUser($user);

        // Tester la déconnexion
        $this->client->request('GET', '/logout');
        
        $this->assertResponseRedirects();
    }

    public function testRegistration(): void
    {
        // Tester la page d'inscription
        $this->client->request('GET', '/register');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        // Tester l'inscription
        $this->client->submitForm('S\'inscrire', [
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[password][first]' => 'password123',
            'registration_form[password][second]' => 'password123',
        ]);

        $this->assertResponseRedirects();
        
        // Vérifier que l'utilisateur a été créé
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'newuser@example.com']);
        $this->assertNotNull($user);
    }
} 