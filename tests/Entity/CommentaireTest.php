<?php

namespace App\Tests\Entity;

use App\Entity\Commentaire;
use App\Entity\User;
use App\Entity\Oeuvre;
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
        $commentaire->setContenu('Test commentaire');
        
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $commentaire->setUser($user);
        
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Oeuvre');
        $commentaire->setOeuvre($oeuvre);

        $errors = $this->validator->validate($commentaire);
        
        $this->assertCount(0, $errors, 'Le commentaire devrait être valide');
        $this->assertEquals('Test commentaire', $commentaire->getContenu());
        $this->assertSame($user, $commentaire->getUser());
        $this->assertSame($oeuvre, $commentaire->getOeuvre());
    }

    public function testRelations(): void
    {
        $commentaire = new Commentaire();
        $commentaire->setContenu('Test commentaire');
        
        $user = new User();
        $user->setEmail('test@example.com');
        $commentaire->setUser($user);
        
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Oeuvre');
        $commentaire->setOeuvre($oeuvre);
        
        $this->assertSame($user, $commentaire->getUser());
        $this->assertSame($oeuvre, $commentaire->getOeuvre());
        $this->assertEquals('Test commentaire', $commentaire->getContenu());
    }
} 