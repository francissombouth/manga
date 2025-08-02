<?php

namespace App\Tests\Controller;

use App\Entity\Oeuvre;
use App\Entity\Auteur;
use App\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class OeuvreControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
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

    public function testOeuvreListWithFilters(): void
    {
        // Créer des données de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);

        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setDescription('Description de test');
        $oeuvre->setAuteur($auteur);
        $this->entityManager->persist($oeuvre);
        $this->entityManager->flush();

        // Tester la liste des œuvres
        $this->client->request('GET', '/oeuvres');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test Manga');
    }

    public function testOeuvreShow(): void
    {
        // Créer une œuvre de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);

        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setDescription('Description de test');
        $oeuvre->setAuteur($auteur);
        $this->entityManager->persist($oeuvre);
        $this->entityManager->flush();

        // Tester l'affichage d'une œuvre
        $this->client->request('GET', '/oeuvres/'.$oeuvre->getId());
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test Manga');
    }

    public function testOeuvreCreate(): void
    {
        // Créer un auteur de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);
        $this->entityManager->flush();

        // Tester la création d'une œuvre
        $this->client->request('GET', '/oeuvres/new');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testOeuvreUpdate(): void
    {
        // Créer une œuvre de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);

        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setDescription('Description de test');
        $oeuvre->setAuteur($auteur);
        $this->entityManager->persist($oeuvre);
        $this->entityManager->flush();

        // Tester la modification d'une œuvre
        $this->client->request('GET', '/oeuvres/'.$oeuvre->getId().'/edit');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testOeuvreDelete(): void
    {
        // Créer une œuvre de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);

        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setDescription('Description de test');
        $oeuvre->setAuteur($auteur);
        $this->entityManager->persist($oeuvre);
        $this->entityManager->flush();

        $oeuvreId = $oeuvre->getId();

        // Tester la suppression d'une œuvre
        $this->client->request('DELETE', '/oeuvres/'.$oeuvreId);
        
        // Vérifier que l'œuvre a été supprimée
        $this->entityManager->clear();
        $deletedOeuvre = $this->entityManager->find(Oeuvre::class, $oeuvreId);
        $this->assertNull($deletedOeuvre);
    }
} 