<?php

namespace App\Tests\Controller;

use App\Entity\Oeuvre;
use App\Entity\User;
use App\Repository\OeuvreRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OeuvreControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $oeuvreRepository;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->oeuvreRepository = static::getContainer()->get(OeuvreRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }

    public function testOeuvreListPage(): void
    {
        $this->client->request('GET', '/oeuvres');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
        $this->assertSelectorTextContains('h1', 'Catalogue');
    }

    public function testOeuvreShowPage(): void
    {
        // Créer un auteur de test
        $auteur = new \App\Entity\Auteur();
        $auteur->setNom('Auteur Test');
        $this->entityManager->persist($auteur);

        // Créer une œuvre de test
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setResume('Résumé de test');
        $oeuvre->setCouverture('https://example.com/image.jpg');
        $oeuvre->setStatut('En cours');
        $oeuvre->setType('manga');
        $oeuvre->setAuteur($auteur);

        $this->entityManager->persist($oeuvre);
        $this->entityManager->flush();

        $this->client->request('GET', '/oeuvres/' . $oeuvre->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test Manga');
    }

    public function testOeuvreShowPageNotFound(): void
    {
        $this->client->request('GET', '/oeuvres/999999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testOeuvreSearch(): void
    {
        $this->client->request('GET', '/oeuvres', [
            'search' => 'test'
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testOeuvreListWithPagination(): void
    {
        $this->client->request('GET', '/oeuvres', [
            'page' => 1
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testOeuvreListWithFilters(): void
    {
        $this->client->request('GET', '/oeuvres', [
            'statut' => 'En cours',
            'genre' => 'Action'
        ]);

        $this->assertResponseIsSuccessful();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
} 