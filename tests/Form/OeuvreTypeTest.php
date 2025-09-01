<?php

namespace App\Tests\Form;

use App\Entity\Oeuvre;
use App\Form\OeuvreType;
use App\Tests\TestKernel;

class OeuvreTypeTest extends TestKernel
{
    public function testFormCreation(): void
    {
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);

        $this->assertNotNull($form);
        $this->assertTrue($form->has('titre'));
        $this->assertTrue($form->has('resume'));
        $this->assertTrue($form->has('type'));
    }

    public function testFormDefaultData(): void
    {
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Test Manga');
        $oeuvre->setResume('Résumé de test');
        $oeuvre->setType('Manga');

        $form = $this->createForm(OeuvreType::class, $oeuvre);

        $this->assertEquals('Test Manga', $form->get('titre')->getData());
        $this->assertEquals('Résumé de test', $form->get('resume')->getData());
        $this->assertEquals('Manga', $form->get('type')->getData());
    }

    public function testTypeChoices(): void
    {
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);

        $typeChoices = $form->get('type')->getConfig()->getOption('choices');
        $this->assertContains('Manga', $typeChoices);
        $this->assertContains('Manhwa', $typeChoices);
        $this->assertContains('Manhua', $typeChoices);
    }

    public function testFormAttributes(): void
    {
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);

        $this->assertEquals('oeuvre', $form->getName());
    }

    private function createForm($type, $data = null)
    {
        $container = static::getContainer();
        return $container->get('form.factory')->create($type, $data);
    }
} 