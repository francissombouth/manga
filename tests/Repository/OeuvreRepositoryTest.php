<?php

namespace App\Tests\Repository;

use App\Entity\Auteur;
use App\Entity\Oeuvre;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OeuvreRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private OeuvreRepository $oeuvreRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->oeuvreRepository = $this->entityManager->getRepository(Oeuvre::class);
        
        // Nettoyer la base de données avant chaque test (ordre : chapitres -> collection_user -> commentaire_like -> commentaires -> oeuvre_note -> oeuvres -> auteurs)
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Chapitre')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\CollectionUser')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\CommentaireLike')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Commentaire')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\OeuvreNote')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Oeuvre')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Auteur')->execute();
        $this->entityManager->flush();
    }

    public function testFindByType(): void
    {
        // Créer un auteur de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $auteur->setPrenom('Test');
        $this->entityManager->persist($auteur);
        $this->entityManager->flush();

        // Créer des œuvres de test avec différents types
        $oeuvre1 = new Oeuvre();
        $oeuvre1->setTitre('Manga Test 1');
        $oeuvre1->setResume('Description du manga 1');
        $oeuvre1->setType('manga');
        $oeuvre1->setAuteur($auteur);
        $oeuvre1->setCouverture('cover1.jpg');
        $this->entityManager->persist($oeuvre1);

        $oeuvre2 = new Oeuvre();
        $oeuvre2->setTitre('Anime Test 1');
        $oeuvre2->setResume('Description de l\'anime 1');
        $oeuvre2->setType('anime');
        $oeuvre2->setAuteur($auteur);
        $oeuvre2->setCouverture('cover2.jpg');
        $this->entityManager->persist($oeuvre2);

        $oeuvre3 = new Oeuvre();
        $oeuvre3->setTitre('Manga Test 2');
        $oeuvre3->setResume('Description du manga 2');
        $oeuvre3->setType('manga');
        $oeuvre3->setAuteur($auteur);
        $oeuvre3->setCouverture('cover3.jpg');
        $this->entityManager->persist($oeuvre3);

        $this->entityManager->flush();

        // Tester la méthode findByType
        $mangas = $this->oeuvreRepository->findByType('manga');
        $animes = $this->oeuvreRepository->findByType('anime');

        $this->assertCount(2, $mangas);
        $this->assertCount(1, $animes);
        
        $this->assertEquals('Manga Test 1', $mangas[0]->getTitre());
        $this->assertEquals('Manga Test 2', $mangas[1]->getTitre());
        $this->assertEquals('Anime Test 1', $animes[0]->getTitre());
    }

    public function testFindByTitre(): void
    {
        // Créer un auteur de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $auteur->setPrenom('Test');
        $this->entityManager->persist($auteur);
        $this->entityManager->flush();

        // Créer des œuvres de test
        $oeuvre1 = new Oeuvre();
        $oeuvre1->setTitre('Naruto');
        $oeuvre1->setResume('Description de Naruto');
        $oeuvre1->setType('manga');
        $oeuvre1->setAuteur($auteur);
        $oeuvre1->setCouverture('naruto.jpg');
        $this->entityManager->persist($oeuvre1);

        $oeuvre2 = new Oeuvre();
        $oeuvre2->setTitre('Naruto Shippuden');
        $oeuvre2->setResume('Description de Naruto Shippuden');
        $oeuvre2->setType('anime');
        $oeuvre2->setAuteur($auteur);
        $oeuvre2->setCouverture('naruto-shippuden.jpg');
        $this->entityManager->persist($oeuvre2);

        $this->entityManager->flush();

        // Tester la méthode findByTitre
        $resultats = $this->oeuvreRepository->findByTitre('Naruto');

        $this->assertCount(2, $resultats);
        $this->assertEquals('Naruto', $resultats[0]->getTitre());
        $this->assertEquals('Naruto Shippuden', $resultats[1]->getTitre());
    }

    public function testFindAllWithRelations(): void
    {
        // Créer un auteur de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $auteur->setPrenom('Test');
        $this->entityManager->persist($auteur);
        $this->entityManager->flush();

        // Créer des œuvres de test
        $oeuvre1 = new Oeuvre();
        $oeuvre1->setTitre('Oeuvre A');
        $oeuvre1->setResume('Description A');
        $oeuvre1->setType('manga');
        $oeuvre1->setAuteur($auteur);
        $oeuvre1->setCouverture('a.jpg');
        $this->entityManager->persist($oeuvre1);

        $oeuvre2 = new Oeuvre();
        $oeuvre2->setTitre('Oeuvre B');
        $oeuvre2->setResume('Description B');
        $oeuvre2->setType('manga');
        $oeuvre2->setAuteur($auteur);
        $oeuvre2->setCouverture('b.jpg');
        $this->entityManager->persist($oeuvre2);

        $this->entityManager->flush();

        // Tester la méthode findAllWithRelations
        $resultats = $this->oeuvreRepository->findAllWithRelations(1, 0, 'titre', 'ASC');

        $this->assertCount(1, $resultats);
        $this->assertEquals('Oeuvre A', $resultats[0]->getTitre());
        $this->assertNotNull($resultats[0]->getAuteur());
        $this->assertEquals('Test Auteur', $resultats[0]->getAuteur()->getNom());
    }
} 