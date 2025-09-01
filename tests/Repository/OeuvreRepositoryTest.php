<?php

namespace App\Tests\Repository;

use App\Entity\Oeuvre;
use App\Entity\Auteur;
use App\Repository\OeuvreRepository;
use App\Tests\TestKernel;
use Doctrine\ORM\EntityManagerInterface;

class OeuvreRepositoryTest extends TestKernel
{
    private EntityManagerInterface $entityManager;
    private OeuvreRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->entityManager->getRepository(Oeuvre::class);
    }

    public function testFindByType(): void
    {
        // Créer des données de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);

        $oeuvre1 = new Oeuvre();
        $oeuvre1->setTitre('Manga Test 1');
        $oeuvre1->setResume('Résumé 1');
        $oeuvre1->setType('Manga');
        $oeuvre1->setAuteur($auteur);
        $this->entityManager->persist($oeuvre1);

        $oeuvre2 = new Oeuvre();
        $oeuvre2->setTitre('Manhwa Test 1');
        $oeuvre2->setResume('Résumé 2');
        $oeuvre2->setType('Manhwa');
        $oeuvre2->setAuteur($auteur);
        $this->entityManager->persist($oeuvre2);

        $this->entityManager->flush();

        // Tester la recherche par type
        $mangas = $this->repository->findByType('Manga');
        $this->assertCount(1, $mangas);
        $this->assertEquals('Manga Test 1', $mangas[0]->getTitre());

        $manhwas = $this->repository->findByType('Manhwa');
        $this->assertCount(1, $manhwas);
        $this->assertEquals('Manhwa Test 1', $manhwas[0]->getTitre());
    }

    public function testFindByTitre(): void
    {
        // Créer des données de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);

        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga Unique');
        $oeuvre->setResume('Résumé unique');
        $oeuvre->setType('Manga');
        $oeuvre->setAuteur($auteur);
        $this->entityManager->persist($oeuvre);

        $this->entityManager->flush();

        // Tester la recherche par titre
        $result = $this->repository->findByTitre('Test Manga Unique');
        $this->assertCount(1, $result);
        $this->assertEquals('Test Manga Unique', $result[0]->getTitre());
    }

    public function testFindAllWithRelations(): void
    {
        // Créer des données de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);

        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setResume('Résumé de test');
        $oeuvre->setType('Manga');
        $oeuvre->setAuteur($auteur);
        $this->entityManager->persist($oeuvre);

        $this->entityManager->flush();

        // Tester la recherche avec relations
        $result = $this->repository->findAllWithRelations();
        $this->assertCount(1, $result);
        $this->assertEquals('Test Manga', $result[0]->getTitre());
        $this->assertNotNull($result[0]->getAuteur());
        $this->assertEquals('Test Auteur', $result[0]->getAuteur()->getNom());
    }

    protected function tearDown(): void
    {
        // Ne pas fermer l'EntityManager ici
        parent::tearDown();
    }
} 