<?php

namespace App\Tests\Form;

use App\Entity\Auteur;
use App\Entity\Oeuvre;
use App\Entity\Tag;
use App\Form\OeuvreType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OeuvreTypeTest extends WebTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        
        // Nettoyer la base de données de test
        $this->entityManager->createQuery('DELETE FROM App\Entity\Oeuvre')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Auteur')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Tag')->execute();
    }

    public function testSubmitValidData()
    {
        // Créer des données de test
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);
        $this->entityManager->flush();

        $tag = new Tag();
        $tag->setNom('Action');
        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $formData = [
            'titre' => 'Test Manga',
            'resume' => 'Résumé de test',
            'type' => 'Manga',
            'datePublication' => '2023-01-01',
            'couverture' => 'https://example.com/image.jpg',
            'auteur' => $auteur->getId(),
            'tags' => [$tag->getId()],
        ];

        $oeuvre = new Oeuvre();
        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class, $oeuvre);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        
        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->fail('Form is not valid. Errors: ' . implode(', ', $errors));
        }
        
        $this->assertTrue($form->isValid());
        $this->assertEquals('Test Manga', $oeuvre->getTitre());
        $this->assertEquals('Résumé de test', $oeuvre->getResume());
        $this->assertEquals('Manga', $oeuvre->getType());
    }

    public function testSubmitEmptyData()
    {
        $oeuvre = new Oeuvre();
        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class, $oeuvre);
        $form->submit([]);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('titre')->getErrors()->count() > 0);
        $this->assertTrue($form->get('type')->getErrors()->count() > 0);
    }

    public function testSubmitInvalidUrl()
    {
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);
        $this->entityManager->flush();

        $formData = [
            'titre' => 'Test Manga',
            'resume' => 'Résumé de test',
            'type' => 'Manga',
            'datePublication' => '2023-01-01',
            'couverture' => 'http://not a url',
            'auteur' => $auteur->getId(),
        ];

        $oeuvre = new Oeuvre();
        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class, $oeuvre);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('couverture')->getErrors()->count() > 0);
    }

    public function testSubmitInvalidDate()
    {
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);
        $this->entityManager->flush();

        $formData = [
            'titre' => 'Test Manga',
            'resume' => 'Résumé de test',
            'type' => 'Manga',
            'datePublication' => 'invalid-date',
            'auteur' => $auteur->getId(),
        ];

        $oeuvre = new Oeuvre();
        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class, $oeuvre);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('datePublication')->getErrors()->count() > 0);
    }

    public function testSubmitWithOptionalFieldsEmpty()
    {
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);
        $this->entityManager->flush();

        $formData = [
            'titre' => 'Test Manga',
            'type' => 'Manga',
            'auteur' => $auteur->getId(),
            // Champs optionnels vides
            'resume' => '',
            'datePublication' => '',
            'couverture' => '',
        ];

        $oeuvre = new Oeuvre();
        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class, $oeuvre);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        
        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->fail('Form is not valid. Errors: ' . implode(', ', $errors));
        }
        
        $this->assertTrue($form->isValid());
        $this->assertEquals('Test Manga', $oeuvre->getTitre());
        $this->assertEquals('', $oeuvre->getResume());
    }

    public function testFormDefaultData()
    {
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre('Titre par défaut');
        $oeuvre->setResume('Résumé par défaut');
        $oeuvre->setType('Manga');

        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class, $oeuvre);

        $this->assertEquals('Titre par défaut', $form->get('titre')->getData());
        $this->assertEquals('Résumé par défaut', $form->get('resume')->getData());
        $this->assertEquals('Manga', $form->get('type')->getData());
    }

    public function testTypeChoices()
    {
        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class);
        $typeField = $form->get('type');
        
        $choices = $typeField->getConfig()->getOption('choices');
        $this->assertArrayHasKey('Manga', $choices);
        $this->assertArrayHasKey('Manhwa', $choices);
        $this->assertArrayHasKey('Manhua', $choices);
    }

    public function testAuteurChoiceLabel()
    {
        $auteur = new Auteur();
        $auteur->setNom('Test Auteur');
        $this->entityManager->persist($auteur);
        $this->entityManager->flush();

        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class);
        $auteurField = $form->get('auteur');
        
        $choiceLabel = $auteurField->getConfig()->getOption('choice_label');
        $this->assertIsCallable($choiceLabel);
        
        // Test du callable
        $label = $choiceLabel($auteur);
        $this->assertEquals('Test Auteur', $label);
    }

    public function testTagsMultipleSelection()
    {
        $tag1 = new Tag();
        $tag1->setNom('Action');
        $this->entityManager->persist($tag1);

        $tag2 = new Tag();
        $tag2->setNom('Aventure');
        $this->entityManager->persist($tag2);
        $this->entityManager->flush();

        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class);
        $tagsField = $form->get('tags');
        
        $this->assertTrue($tagsField->getConfig()->getOption('multiple'));
        $this->assertFalse($tagsField->getConfig()->getOption('expanded'));
        
        $choiceLabel = $tagsField->getConfig()->getOption('choice_label');
        $this->assertEquals('nom', $choiceLabel);
    }

    public function testFormAttributes()
    {
        $form = $this->client->getContainer()->get('form.factory')->create(OeuvreType::class);
        
        $this->assertTrue($form->getConfig()->getOption('data_class') === Oeuvre::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
} 