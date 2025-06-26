<?php

namespace App\Command;

use App\Entity\Oeuvre;
use App\Entity\Auteur;
use App\Entity\Tag;
use App\Repository\OeuvreRepository;
use App\Repository\AuteurRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-oeuvre-sans-mangadex',
    description: 'Teste l\'ajout d\'une œuvre sans MangaDex'
)]
class TestOeuvreSansMangaDexCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OeuvreRepository $oeuvreRepository,
        private AuteurRepository $auteurRepository,
        private TagRepository $tagRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test d\'ajout d\'œuvre sans MangaDex');

        try {
            // Créer ou récupérer un auteur de test
            $auteur = $this->auteurRepository->findOneBy(['nom' => 'Auteur Test']);
            if (!$auteur) {
                $auteur = new Auteur();
                $auteur->setNom('Auteur Test');
                $auteur->setPrenom('Test');
                $auteur->setBiographie('Auteur de test pour les œuvres sans MangaDex');
                $this->entityManager->persist($auteur);
            }

            // Créer ou récupérer des tags de test
            $tags = [];
            $tagNames = ['Action', 'Aventure', 'Test'];
            foreach ($tagNames as $tagName) {
                $tag = $this->tagRepository->findOneBy(['nom' => $tagName]);
                if (!$tag) {
                    $tag = new Tag();
                    $tag->setNom($tagName);
                    $this->entityManager->persist($tag);
                }
                $tags[] = $tag;
            }

            // Créer une œuvre sans MangaDex
            $oeuvre = new Oeuvre();
            $oeuvre->setTitre('Mon Œuvre Test Sans MangaDex');
            $oeuvre->setAuteur($auteur);
            $oeuvre->setType('Manga');
            $oeuvre->setResume('Ceci est une œuvre de test créée sans lien MangaDex. Elle permet de tester le système d\'ajout d\'œuvres manuelles.');
            $oeuvre->setCouverture('https://via.placeholder.com/300x400/6366f1/ffffff?text=Test');
            $oeuvre->setDatePublication(new \DateTime('2024-01-01'));
            $oeuvre->setIsbn('978-1234567890');
            $oeuvre->setMangadxId(null); // Pas d'ID MangaDex

            // Ajouter les tags
            foreach ($tags as $tag) {
                $oeuvre->addTag($tag);
            }

            $this->entityManager->persist($oeuvre);
            $this->entityManager->flush();

            $io->success([
                "✅ Œuvre créée avec succès sans MangaDex !",
                "📖 Titre: {$oeuvre->getTitre()}",
                "👤 Auteur: " . ($oeuvre->getAuteur() ? $oeuvre->getAuteur()->getNom() : 'Aucun'),
                "📚 Type: {$oeuvre->getType()}",
                "🔗 MangaDx ID: " . ($oeuvre->getMangadxId() ?: 'Aucun'),
                "🏷️ Tags: " . implode(', ', array_map(fn($tag) => $tag->getNom(), $oeuvre->getTags()->toArray())),
                "📅 Date de publication: " . ($oeuvre->getDatePublication() ? $oeuvre->getDatePublication()->format('d/m/Y') : 'Non définie'),
                "📄 ISBN: " . ($oeuvre->getIsbn() ?: 'Non défini')
            ]);

            $io->text("🎯 Cette œuvre peut maintenant être gérée dans l'administration");
            $io->text("📝 Vous pouvez ajouter des chapitres manuellement avec leurs pages");
            $io->text("🔗 Elle n'aura pas de lien avec MangaDex mais fonctionnera parfaitement");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("❌ Erreur lors de la création: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 