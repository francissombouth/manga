<?php

namespace App\Command;

use App\Entity\Oeuvre;
use App\Entity\Auteur;
use App\Entity\Tag;
use App\Entity\Chapitre;
use App\Repository\OeuvreRepository;
use App\Repository\AuteurRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-sample-data',
    description: 'Charge des données d\'exemple simulant des imports depuis l\'API MangaDx',
)]
class LoadSampleDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OeuvreRepository $oeuvreRepository,
        private AuteurRepository $auteurRepository,
        private TagRepository $tagRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forcer le rechargement même si des données existent')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        $io->title('🚀 Chargement des données d\'exemple depuis l\'API simulée');

        // Vérifier s'il y a déjà des œuvres
        $existingOeuvres = $this->oeuvreRepository->findAll();
        if (!empty($existingOeuvres) && !$force) {
            $io->warning(sprintf(
                'Il y a déjà %d œuvre(s) en base. Utilisez --force pour forcer le rechargement.',
                count($existingOeuvres)
            ));
            return Command::SUCCESS;
        }

        // Données simulées d'œuvres populaires (comme si elles venaient de MangaDx API)
        $oeuvresData = [
            [
                'mangadxId' => 'a96676e5-8ae2-425e-b549-7f15dd34a6d8',
                'titre' => 'Solo Leveling',
                'auteur' => 'Chugong',
                'type' => 'Manga',
                'resume' => 'Dans un monde où des portails apparaissent et connectent notre monde à d\'autres dimensions pleines de monstres, Sung Jinwoo, le chasseur le plus faible, découvre qu\'il peut devenir plus fort.',
                'datePublication' => '2018-03-04',
                'couverture' => '/covers/solo-leveling.jpg',
                'tags' => ['Action', 'Aventure', 'Fantastique', 'Surnaturel'],
                'chapitres' => [
                    ['titre' => 'Le chasseur le plus faible', 'ordre' => 1],
                    ['titre' => 'Le système', 'ordre' => 2],
                    ['titre' => 'Éveil', 'ordre' => 3],
                    ['titre' => 'Premier raid solo', 'ordre' => 4],
                    ['titre' => 'La quête secrète', 'ordre' => 5]
                ]
            ],
            [
                'mangadxId' => 'b183c6f8-1d80-4119-a52e-8d08b9b13ba5',
                'titre' => 'Tower of God',
                'auteur' => 'SIU',
                'type' => 'Webtoon',
                'resume' => 'Vingt-cinquième Bam a passé sa vie entière sous une mystérieuse Tour, avec seulement sa compagne Rachel pour lui tenir compagnie. Quand elle part pour gravir la Tour, il suit ses traces.',
                'datePublication' => '2010-06-30',
                'couverture' => '/covers/tower-of-god.jpg',
                'tags' => ['Action', 'Aventure', 'Drame', 'Fantastique', 'Mystère'],
                'chapitres' => [
                    ['titre' => 'Ball', 'ordre' => 1],
                    ['titre' => '2F - Evankhell\'s Hell', 'ordre' => 2],
                    ['titre' => '2F - Test', 'ordre' => 3]
                ]
            ],
            [
                'mangadxId' => 'c52b2ce3-7f95-469c-96b0-479524fb7a1a',
                'titre' => 'One Piece',
                'auteur' => 'Eiichiro Oda',
                'type' => 'Manga',
                'resume' => 'Monkey D. Luffy rêve de devenir le roi des pirates. Il gagne des pouvoirs élastiques en mangeant un fruit du démon, mais perd sa capacité à nager.',
                'datePublication' => '1997-07-22',
                'couverture' => '/covers/one-piece.jpg',
                'tags' => ['Action', 'Aventure', 'Comédie', 'Drame', 'Shonen'],
                'chapitres' => [
                    ['titre' => 'Romance Dawn', 'ordre' => 1],
                    ['titre' => 'Ils l\'appellent "Luffy le Chapeau de Paille"', 'ordre' => 2],
                    ['titre' => 'Morgan contre Luffy', 'ordre' => 3],
                    ['titre' => 'L\'épée du capitaine Morgan', 'ordre' => 4],
                    ['titre' => 'Le roi des pirates et le grand épéiste', 'ordre' => 5]
                ]
            ],
            [
                'mangadxId' => 'd1a9fdeb-f713-407f-960c-8326b586e6fd',
                'titre' => 'Demon Slayer',
                'auteur' => 'Koyoharu Gotouge',
                'type' => 'Manga',
                'resume' => 'Depuis les temps anciens, des rumeurs parlent de démons dévoreurs d\'hommes qui se cachent dans les bois. Pour cette raison, les habitants de la ville n\'y mettent jamais les pieds la nuit.',
                'datePublication' => '2016-02-15',
                'couverture' => '/covers/demon-slayer.jpg',
                'tags' => ['Action', 'Drame', 'Historique', 'Shonen', 'Surnaturel'],
                'chapitres' => [
                    ['titre' => 'Cruauté', 'ordre' => 1],
                    ['titre' => 'L\'étranger', 'ordre' => 2],
                    ['titre' => 'Retour chez soi', 'ordre' => 3]
                ]
            ],
            [
                'mangadxId' => 'e78a489b-6632-4d61-b00b-5206f5b8b22b',
                'titre' => 'The Beginning After The End',
                'auteur' => 'TurtleMe',
                'type' => 'Webtoon',
                'resume' => 'Le roi Grey possède une force, une richesse et un prestige inégalés dans un monde gouverné par la capacité martiale. Cependant, la solitude accompagne le pouvoir.',
                'datePublication' => '2018-12-11',
                'couverture' => '/covers/the-beginning-after-the-end.jpg',
                'tags' => ['Action', 'Aventure', 'Drame', 'Fantastique', 'Magie'],
                'chapitres' => [
                    ['titre' => 'Nouvelle Vie', 'ordre' => 1],
                    ['titre' => 'Seconde Chance', 'ordre' => 2],
                    ['titre' => 'Famille', 'ordre' => 3]
                ]
            ]
        ];

        $io->text('📥 Simulation de récupération depuis MangaDx API...');
        $io->newLine();

        $progressBar = $io->createProgressBar(count($oeuvresData));
        $progressBar->start();

        $created = 0;
        $errors = 0;

        foreach ($oeuvresData as $oeuvreData) {
            $progressBar->setMessage($oeuvreData['titre']);
            
            try {
                // Vérifier si l'œuvre existe déjà
                $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $oeuvreData['mangadxId']]);
                if ($existingOeuvre) {
                    $progressBar->advance();
                    continue;
                }

                $oeuvre = new Oeuvre();
                $oeuvre->setMangadxId($oeuvreData['mangadxId']);
                $oeuvre->setTitre($oeuvreData['titre']);
                $oeuvre->setType($oeuvreData['type']);
                $oeuvre->setResume($oeuvreData['resume']);
                $oeuvre->setCouverture($oeuvreData['couverture']);
                $oeuvre->setDatePublication(new \DateTime($oeuvreData['datePublication']));

                // Chercher ou créer l'auteur
                $auteur = $this->auteurRepository->findOneBy(['nom' => $oeuvreData['auteur']]);
                if (!$auteur) {
                    $auteur = new Auteur();
                    $auteur->setNom($oeuvreData['auteur']);
                    $this->entityManager->persist($auteur);
                }
                $oeuvre->setAuteur($auteur);

                // Ajouter les tags
                foreach ($oeuvreData['tags'] as $tagName) {
                    $tag = $this->tagRepository->findOneBy(['nom' => $tagName]);
                    if (!$tag) {
                        $tag = new Tag();
                        $tag->setNom($tagName);
                        $this->entityManager->persist($tag);
                    }
                    $oeuvre->addTag($tag);
                }

                $this->entityManager->persist($oeuvre);

                // Créer les chapitres
                foreach ($oeuvreData['chapitres'] as $chapitreData) {
                    $chapitre = new Chapitre();
                    $chapitre->setTitre($chapitreData['titre']);
                    $chapitre->setOrdre($chapitreData['ordre']);
                    $chapitre->setOeuvre($oeuvre);
                    $chapitre->setPages([]); // Pages vides pour l'instant

                    $this->entityManager->persist($chapitre);
                }

                $this->entityManager->flush();
                $created++;

            } catch (\Exception $e) {
                $errors++;
                $io->error(sprintf('Erreur pour "%s": %s', $oeuvreData['titre'], $e->getMessage()));
            }

            $progressBar->advance();
            
            // Délai simulé pour l'API
            usleep(100000); // 0.1 seconde
        }

        $progressBar->finish();
        $io->newLine(2);

        // Résumé
        $io->section('📊 Résumé de l\'import');
        $io->table(
            ['Statut', 'Nombre'],
            [
                ['✅ Œuvres créées', $created],
                ['❌ Erreurs', $errors],
                ['📚 Total traité', count($oeuvresData)]
            ]
        );

        if ($created > 0) {
            $io->success(sprintf(
                '🎉 %d œuvre(s) ont été sauvegardées depuis l\'API simulée !', 
                $created
            ));
            
            $io->text([
                '💡 Ces données simulent ce que vous récupéreriez depuis MangaDx API.',
                '🔄 Vous pouvez maintenant utiliser les commandes de synchronisation :',
                '   • php bin/console app:sync-mangadx --update-all',
                '   • php bin/console app:sync-mangadx <mangadx-id>',
            ]);
        }

        return $errors === 0 ? Command::SUCCESS : Command::FAILURE;
    }
} 