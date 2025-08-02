<?php

namespace App\Tests\Entity;

use App\Entity\Oeuvre;
use App\Entity\Auteur;
use App\Entity\Tag;
use App\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OeuvreTest extends TestKernel
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidation(): void
    {
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setDescription('Description de test');
        
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $oeuvre->setAuteur($auteur);

        $errors = $this->validator->validate($oeuvre);
        
        $this->assertCount(0, $errors, 'L\'œuvre devrait être valide');
        $this->assertEquals('Test Manga', $oeuvre->getTitre());
        $this->assertEquals('Test Auteur', $oeuvre->getAuteur()->getNom());
    }

    public function testRelations(): void
    {
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $oeuvre->setAuteur($auteur);
        
        $tag = new Tag();
        $tag->setNom('Action');
        $oeuvre->addTag($tag);
        
        $this->assertSame($auteur, $oeuvre->getAuteur());
        $this->assertTrue($oeuvre->getTags()->contains($tag));
        $this->assertCount(1, $oeuvre->getTags());
        
        $oeuvre->removeTag($tag);
        $this->assertCount(0, $oeuvre->getTags());
    }
} 