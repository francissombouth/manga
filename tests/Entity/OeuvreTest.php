<?php

namespace App\Tests\Entity;

use App\Entity\Oeuvre;
use App\Entity\Auteur;
use App\Entity\Tag;
use PHPUnit\Framework\TestCase;

class OeuvreTest extends TestCase
{
    private Oeuvre $oeuvre;

    protected function setUp(): void
    {
        $this->oeuvre = new Oeuvre();
    }

    public function testOeuvreCreation(): void
    {
        $this->assertInstanceOf(Oeuvre::class, $this->oeuvre);
    }

    public function testOeuvreSettersAndGetters(): void
    {
        $titre = 'Test Manga';
        $resume = 'Résumé de test';
        $couverture = 'https://example.com/image.jpg';
        $statut = 'En cours';

        $this->oeuvre->setTitre($titre);
        $this->oeuvre->setResume($resume);
        $this->oeuvre->setCouverture($couverture);
        $this->oeuvre->setStatut($statut);

        $this->assertEquals($titre, $this->oeuvre->getTitre());
        $this->assertEquals($resume, $this->oeuvre->getResume());
        $this->assertEquals($couverture, $this->oeuvre->getCouverture());
        $this->assertEquals($statut, $this->oeuvre->getStatut());
    }

    public function testOeuvreWithAuteur(): void
    {
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');

        $this->oeuvre->setAuteur($auteur);

        $this->assertSame($auteur, $this->oeuvre->getAuteur());
        $this->assertEquals('Test Auteur', $this->oeuvre->getAuteur()->getNom());
    }

    public function testOeuvreWithTags(): void
    {
        $tag1 = new Tag();
        $tag1->setNom('Action');

        $tag2 = new Tag();
        $tag2->setNom('Aventure');

        $this->oeuvre->addTag($tag1);
        $this->oeuvre->addTag($tag2);

        $this->assertCount(2, $this->oeuvre->getTags());
        $this->assertTrue($this->oeuvre->getTags()->contains($tag1));
        $this->assertTrue($this->oeuvre->getTags()->contains($tag2));

        $this->oeuvre->removeTag($tag1);
        $this->assertCount(1, $this->oeuvre->getTags());
        $this->assertFalse($this->oeuvre->getTags()->contains($tag1));
    }

    public function testOeuvreTimestamps(): void
    {
        // Les timestamps sont automatiquement définis dans le constructeur
        $this->assertNotNull($this->oeuvre->getCreatedAt());
        $this->assertNotNull($this->oeuvre->getUpdatedAt());
        
        // Test de mise à jour du timestamp
        $oldUpdatedAt = $this->oeuvre->getUpdatedAt();
        sleep(1); // Attendre 1 seconde pour s'assurer que le timestamp change
        $this->oeuvre->setUpdatedAt(new \DateTimeImmutable());
        
        $this->assertNotEquals($oldUpdatedAt, $this->oeuvre->getUpdatedAt());
    }
} 