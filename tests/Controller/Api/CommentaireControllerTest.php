<?php

namespace App\Tests\Controller\Api;

use App\Entity\Auteur;
use App\Entity\Commentaire;
use App\Entity\Oeuvre;
use App\Entity\User;
use App\Repository\CommentaireRepository;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CommentaireControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;
    private $oeuvre;
    private $auteur;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Nettoyer la base de données
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Chapitre')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\CollectionUser')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\CommentaireLike')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Commentaire')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\OeuvreNote')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Oeuvre')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Auteur')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
        $this->entityManager->flush();

        // Créer un utilisateur de test
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setNom('Test User');
        $this->user->setPassword('$2y$13$hK.dvjXKJhK.dvjXKJhK.dvjXKJhK.dvjXKJhK.dvjXKJhK.dvjXKJhK.dvjXKJ');
        $this->user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($this->user);

        // Créer un auteur
        $this->auteur = new Auteur();
        $this->auteur->setNom('Test Auteur');
        $this->auteur->setPrenom('Test');
        $this->entityManager->persist($this->auteur);

        // Créer une œuvre
        $this->oeuvre = new Oeuvre();
        $this->oeuvre->setTitre('Test Oeuvre');
        $this->oeuvre->setResume('Description de test');
        $this->oeuvre->setType('manga');
        $this->oeuvre->setAuteur($this->auteur);
        $this->oeuvre->setCouverture('test.jpg');
        $this->entityManager->persist($this->oeuvre);

        $this->entityManager->flush();
    }

    public function testGetCommentairesWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/commentaires/oeuvre/' . $this->oeuvre->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('commentaires', $responseData);
        $this->assertArrayHasKey('notes', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertEquals(0, $responseData['total']);
    }

    public function testGetCommentairesWithOeuvreNotFound(): void
    {
        $this->client->request('GET', '/api/commentaires/oeuvre/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Œuvre non trouvée', $responseData['message']);
    }

    public function testCreateCommentaireWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/commentaires/oeuvre/' . $this->oeuvre->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['contenu' => 'Test commentaire']));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Authentification requise', $responseData['message']);
    }

    public function testCreateCommentaireWithValidData(): void
    {
        // Simuler l'authentification
        $this->client->loginUser($this->user);

        $commentData = ['contenu' => 'Test commentaire'];
        
        $this->client->request('POST', '/api/commentaires/oeuvre/' . $this->oeuvre->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($commentData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('commentaire', $responseData);
        $this->assertEquals('Commentaire ajouté avec succès', $responseData['message']);
        $this->assertEquals('Test commentaire', $responseData['commentaire']['contenu']);
    }

    public function testCreateCommentaireWithEmptyContent(): void
    {
        $this->client->loginUser($this->user);

        $commentData = ['contenu' => ''];
        
        $this->client->request('POST', '/api/commentaires/oeuvre/' . $this->oeuvre->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($commentData));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Le contenu du commentaire ne peut pas être vide', $responseData['message']);
    }

    public function testCreateCommentaireWithInvalidData(): void
    {
        $this->client->loginUser($this->user);

        $this->client->request('POST', '/api/commentaires/oeuvre/' . $this->oeuvre->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Contenu du commentaire requis', $responseData['message']);
    }

    public function testCreateCommentaireWithOeuvreNotFound(): void
    {
        $this->client->loginUser($this->user);

        $commentData = ['contenu' => 'Test commentaire'];
        
        $this->client->request('POST', '/api/commentaires/oeuvre/99999', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($commentData));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Œuvre non trouvée', $responseData['message']);
    }

    public function testRepondreCommentaireWithoutAuthentication(): void
    {
        // Créer un commentaire parent
        $parentCommentaire = new Commentaire();
        $parentCommentaire->setContenu('Commentaire parent');
        $parentCommentaire->setAuteur($this->user);
        $parentCommentaire->setOeuvre($this->oeuvre);
        $this->entityManager->persist($parentCommentaire);
        $this->entityManager->flush();

        $this->client->request('POST', '/api/commentaires/' . $parentCommentaire->getId() . '/repondre', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['contenu' => 'Test réponse']));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Authentification requise', $responseData['message']);
    }

    public function testRepondreCommentaireWithValidData(): void
    {
        $this->client->loginUser($this->user);

        // Créer un commentaire parent
        $parentCommentaire = new Commentaire();
        $parentCommentaire->setContenu('Commentaire parent');
        $parentCommentaire->setAuteur($this->user);
        $parentCommentaire->setOeuvre($this->oeuvre);
        $this->entityManager->persist($parentCommentaire);
        $this->entityManager->flush();

        $reponseData = ['contenu' => 'Test réponse'];
        
        $this->client->request('POST', '/api/commentaires/' . $parentCommentaire->getId() . '/repondre', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($reponseData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('reponse', $responseData);
        $this->assertEquals('Réponse ajoutée avec succès', $responseData['message']);
        $this->assertEquals('Test réponse', $responseData['reponse']['contenu']);
        $this->assertTrue($responseData['reponse']['isReponse']);
        $this->assertEquals($parentCommentaire->getId(), $responseData['reponse']['parentId']);
    }

    public function testRepondreCommentaireWithParentNotFound(): void
    {
        $this->client->loginUser($this->user);

        $reponseData = ['contenu' => 'Test réponse'];
        
        $this->client->request('POST', '/api/commentaires/99999/repondre', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($reponseData));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Commentaire parent non trouvé', $responseData['message']);
    }

    public function testGetCommentairesWithExistingCommentaires(): void
    {
        // Créer quelques commentaires
        $commentaire1 = new Commentaire();
        $commentaire1->setContenu('Premier commentaire');
        $commentaire1->setAuteur($this->user);
        $commentaire1->setOeuvre($this->oeuvre);
        $this->entityManager->persist($commentaire1);

        $commentaire2 = new Commentaire();
        $commentaire2->setContenu('Deuxième commentaire');
        $commentaire2->setAuteur($this->user);
        $commentaire2->setOeuvre($this->oeuvre);
        $this->entityManager->persist($commentaire2);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request('GET', '/api/commentaires/oeuvre/' . $this->oeuvre->getId());

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertGreaterThanOrEqual(2, $responseData['total']);
        $this->assertGreaterThanOrEqual(2, count($responseData['commentaires']));
    }

    public function testCreateCommentaireAsReply(): void
    {
        $this->client->loginUser($this->user);

        // Créer un commentaire parent
        $parentCommentaire = new Commentaire();
        $parentCommentaire->setContenu('Commentaire parent');
        $parentCommentaire->setAuteur($this->user);
        $parentCommentaire->setOeuvre($this->oeuvre);
        $this->entityManager->persist($parentCommentaire);
        $this->entityManager->flush();

        $commentData = [
            'contenu' => 'Test réponse',
            'parentId' => $parentCommentaire->getId()
        ];
        
        $this->client->request('POST', '/api/commentaires/oeuvre/' . $this->oeuvre->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($commentData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('commentaire', $responseData);
        $this->assertTrue($responseData['commentaire']['isReponse']);
        $this->assertEquals($parentCommentaire->getId(), $responseData['commentaire']['parentId']);
    }
} 