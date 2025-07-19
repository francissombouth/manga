<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        
        // Nettoyer la base de données avant chaque test
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->createQuery('DELETE FROM App\\Entity\\Chapitre')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\CollectionUser')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\CommentaireLike')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\Commentaire')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\OeuvreNote')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\Oeuvre')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\Auteur')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
        $entityManager->flush();
    }

    public function testLoginPage(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testLoginWithValidCredentials(): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Test User');
        $user->setPassword('$2y$13$hK.dvjXKJhK.dvjXKJhK.dvjXKJhK.dvjXKJhK.dvjXKJhK.dvjXKJhK.dvjXKJ');
        $user->setRoles(['ROLE_USER']);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'test@example.com',
            '_password' => 'password123'
        ]);

        $this->assertResponseRedirects();
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'invalid@example.com',
            '_password' => 'wrongpassword'
        ]);

        $this->assertResponseRedirects('/login');
    }

    public function testLogout(): void
    {
        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects();
    }
} 