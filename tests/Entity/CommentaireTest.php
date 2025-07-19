<?php

namespace App\Tests\Entity;

use App\Entity\Commentaire;
use App\Entity\Oeuvre;
use App\Entity\User;
use App\Entity\Auteur;
use PHPUnit\Framework\TestCase;

class CommentaireTest extends TestCase
{
    private Commentaire $commentaire;
    private User $user;
    private Oeuvre $oeuvre;
    private Auteur $auteur;

    protected function setUp(): void
    {
        $this->commentaire = new Commentaire();
        
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setNom('Test User');
        $this->user->setPassword('password');
        $this->user->setRoles(['ROLE_USER']);

        $this->auteur = new Auteur();
        $this->auteur->setNom('Test Auteur');
        $this->auteur->setPrenom('Test');

        $this->oeuvre = new Oeuvre();
        $this->oeuvre->setTitre('Test Oeuvre');
        $this->oeuvre->setResume('Description de test');
        $this->oeuvre->setType('manga');
        $this->oeuvre->setAuteur($this->auteur);
        $this->oeuvre->setCouverture('test.jpg');
    }

    public function testCommentaireCreation(): void
    {
        $this->assertInstanceOf(Commentaire::class, $this->commentaire);
        $this->assertNull($this->commentaire->getId());
    }

    public function testSetAndGetContenu(): void
    {
        $contenu = 'Test commentaire content';
        $this->commentaire->setContenu($contenu);
        
        $this->assertEquals($contenu, $this->commentaire->getContenu());
    }

    public function testSetAndGetAuteur(): void
    {
        $this->commentaire->setAuteur($this->user);
        
        $this->assertEquals($this->user, $this->commentaire->getAuteur());
    }

    public function testSetAndGetOeuvre(): void
    {
        $this->commentaire->setOeuvre($this->oeuvre);
        
        $this->assertEquals($this->oeuvre, $this->commentaire->getOeuvre());
    }

    public function testSetAndGetParent(): void
    {
        $parentCommentaire = new Commentaire();
        $parentCommentaire->setContenu('Parent commentaire');
        $parentCommentaire->setAuteur($this->user);
        $parentCommentaire->setOeuvre($this->oeuvre);

        $this->commentaire->setParent($parentCommentaire);
        
        $this->assertEquals($parentCommentaire, $this->commentaire->getParent());
    }

    public function testGetReponses(): void
    {
        $reponse1 = new Commentaire();
        $reponse1->setContenu('Réponse 1');
        $reponse1->setAuteur($this->user);
        $reponse1->setOeuvre($this->oeuvre);
        $reponse1->setParent($this->commentaire);

        $reponse2 = new Commentaire();
        $reponse2->setContenu('Réponse 2');
        $reponse2->setAuteur($this->user);
        $reponse2->setOeuvre($this->oeuvre);
        $reponse2->setParent($this->commentaire);

        $this->commentaire->addReponse($reponse1);
        $this->commentaire->addReponse($reponse2);

        $reponses = $this->commentaire->getReponses();
        
        $this->assertCount(2, $reponses);
        $this->assertTrue($reponses->contains($reponse1));
        $this->assertTrue($reponses->contains($reponse2));
    }

    public function testRemoveReponse(): void
    {
        $reponse = new Commentaire();
        $reponse->setContenu('Réponse à supprimer');
        $reponse->setAuteur($this->user);
        $reponse->setOeuvre($this->oeuvre);
        $reponse->setParent($this->commentaire);

        $this->commentaire->addReponse($reponse);
        $this->assertCount(1, $this->commentaire->getReponses());

        $this->commentaire->removeReponse($reponse);
        $this->assertCount(0, $this->commentaire->getReponses());
    }

    public function testIsReponse(): void
    {
        // Commentaire principal (pas une réponse)
        $this->assertFalse($this->commentaire->isReponse());

        // Commentaire avec parent (est une réponse)
        $parentCommentaire = new Commentaire();
        $this->commentaire->setParent($parentCommentaire);
        
        $this->assertTrue($this->commentaire->isReponse());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->commentaire->getCreatedAt());
    }

    public function testGetUpdatedAt(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->commentaire->getUpdatedAt());
    }

    public function testUpdateTimestamp(): void
    {
        $originalUpdatedAt = $this->commentaire->getUpdatedAt();
        
        // Attendre un peu pour que le timestamp change
        sleep(1);
        
        $this->commentaire->updateTimestamp();
        
        $this->assertGreaterThan($originalUpdatedAt, $this->commentaire->getUpdatedAt());
    }

    public function testCommentaireWithAllFields(): void
    {
        $contenu = 'Commentaire complet avec tous les champs';
        $this->commentaire->setContenu($contenu);
        $this->commentaire->setAuteur($this->user);
        $this->commentaire->setOeuvre($this->oeuvre);

        $this->assertEquals($contenu, $this->commentaire->getContenu());
        $this->assertEquals($this->user, $this->commentaire->getAuteur());
        $this->assertEquals($this->oeuvre, $this->commentaire->getOeuvre());
        $this->assertNull($this->commentaire->getParent());
        $this->assertCount(0, $this->commentaire->getReponses());
        $this->assertFalse($this->commentaire->isReponse());
    }

    public function testCommentaireAsReply(): void
    {
        $parentCommentaire = new Commentaire();
        $parentCommentaire->setContenu('Commentaire parent');
        $parentCommentaire->setAuteur($this->user);
        $parentCommentaire->setOeuvre($this->oeuvre);

        $this->commentaire->setContenu('Réponse au commentaire parent');
        $this->commentaire->setAuteur($this->user);
        $this->commentaire->setOeuvre($this->oeuvre);
        $this->commentaire->setParent($parentCommentaire);

        $this->assertTrue($this->commentaire->isReponse());
        $this->assertEquals($parentCommentaire, $this->commentaire->getParent());
        $this->assertEquals($this->oeuvre, $this->commentaire->getOeuvre());
    }

    public function testCommentaireHierarchy(): void
    {
        // Créer une hiérarchie de commentaires
        $parentCommentaire = new Commentaire();
        $parentCommentaire->setContenu('Commentaire parent');
        $parentCommentaire->setAuteur($this->user);
        $parentCommentaire->setOeuvre($this->oeuvre);

        $reponse1 = new Commentaire();
        $reponse1->setContenu('Première réponse');
        $reponse1->setAuteur($this->user);
        $reponse1->setOeuvre($this->oeuvre);
        $reponse1->setParent($parentCommentaire);

        $reponse2 = new Commentaire();
        $reponse2->setContenu('Deuxième réponse');
        $reponse2->setAuteur($this->user);
        $reponse2->setOeuvre($this->oeuvre);
        $reponse2->setParent($parentCommentaire);

        $parentCommentaire->addReponse($reponse1);
        $parentCommentaire->addReponse($reponse2);

        $this->assertCount(2, $parentCommentaire->getReponses());
        $this->assertTrue($reponse1->isReponse());
        $this->assertTrue($reponse2->isReponse());
        $this->assertFalse($parentCommentaire->isReponse());
        $this->assertEquals($parentCommentaire, $reponse1->getParent());
        $this->assertEquals($parentCommentaire, $reponse2->getParent());
    }

    public function testCommentaireTimestamps(): void
    {
        $createdAt = $this->commentaire->getCreatedAt();
        $updatedAt = $this->commentaire->getUpdatedAt();

        // Les timestamps initiaux doivent être proches
        $this->assertLessThan(2, abs($createdAt->getTimestamp() - $updatedAt->getTimestamp()));

        // Attendre et mettre à jour
        sleep(1);
        $this->commentaire->updateTimestamp();

        $newUpdatedAt = $this->commentaire->getUpdatedAt();
        $this->assertGreaterThan($updatedAt, $newUpdatedAt);
        $this->assertEquals($createdAt, $this->commentaire->getCreatedAt()); // CreatedAt ne change pas
    }

    public function testCommentaireValidation(): void
    {
        // Test avec contenu vide
        $this->commentaire->setContenu('');
        $this->assertEquals('', $this->commentaire->getContenu());

        // Test avec contenu très long
        $longContent = str_repeat('a', 1000);
        $this->commentaire->setContenu($longContent);
        $this->assertEquals($longContent, $this->commentaire->getContenu());

        // Test avec contenu normal
        $normalContent = 'Contenu normal avec des caractères spéciaux : éàçù€$£¥';
        $this->commentaire->setContenu($normalContent);
        $this->assertEquals($normalContent, $this->commentaire->getContenu());
    }
} 