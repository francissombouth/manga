<?php

namespace App\Tests\Entity;

use App\Entity\Commentaire;
use App\Entity\Oeuvre;
use App\Entity\User;
use App\Tests\TestKernel;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommentaireTest extends TestKernel
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidation(): void
    {
        $commentaire = new Commentaire();
        $commentaire->setContenu('Contenu de test');
        
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Test User');
        $commentaire->setAuteur($user);
        
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Oeuvre');
        $oeuvre->setType('Manga');
        $commentaire->setOeuvre($oeuvre);

        $errors = $this->validator->validate($commentaire);
        
        $this->assertCount(0, $errors, 'Le commentaire devrait être valide');
        $this->assertEquals('Contenu de test', $commentaire->getContenu());
        $this->assertEquals('Test User', $commentaire->getAuteur()->getNom());
    }

    public function testRelations(): void
    {
        $commentaire = new Commentaire();
        $commentaire->setContenu('Contenu de test');
        
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Test User');
        $commentaire->setAuteur($user);
        
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Oeuvre');
        $oeuvre->setType('Manga');
        $commentaire->setOeuvre($oeuvre);
        
        $this->assertSame($user, $commentaire->getAuteur());
        $this->assertSame($oeuvre, $commentaire->getOeuvre());
        $this->assertEquals(0, $commentaire->getLikesCount());
        $this->assertEquals(0, $commentaire->getReponsesCount());
    }
} 