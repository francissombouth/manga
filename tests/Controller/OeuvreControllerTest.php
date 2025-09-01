<?php

namespace App\Tests\Controller;

use App\Entity\Auteur;
use App\Entity\Oeuvre;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class OeuvreControllerTest extends WebTestCase
{
    public function testOeuvreListPage(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Créer la base de données de test
        $this->createTestDatabase($entityManager);
        
        // Créer des données de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $entityManager->persist($auteur);

        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setResume('Résumé de test');
        $oeuvre->setType('Manga');
        $oeuvre->setAuteur($auteur);
        $entityManager->persist($oeuvre);
        $entityManager->flush();

        // Tester la liste des œuvres
        $client->request('GET', '/oeuvres');
        
        // Vérifier que la page répond (même si elle peut être vide)
        $this->assertResponseIsSuccessful();
    }

    public function testOeuvreShowPage(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Créer la base de données de test
        $this->createTestDatabase($entityManager);
        
        // Créer une œuvre de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $entityManager->persist($auteur);

        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setResume('Résumé de test');
        $oeuvre->setType('Manga');
        $oeuvre->setAuteur($auteur);
        $entityManager->persist($oeuvre);
        $entityManager->flush();

        $oeuvreId = $oeuvre->getId();
        
        // Vérifier que l'ID existe
        $this->assertNotNull($oeuvreId, 'L\'ID de l\'œuvre ne devrait pas être null');

        // Tester l'affichage d'une œuvre
        $client->request('GET', '/oeuvres/'.$oeuvreId);
        
        // Vérifier que la page répond (même si elle peut être vide)
        $this->assertResponseIsSuccessful();
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