<?php

namespace App\Command;

use App\Service\MangaDxService;
use App\Service\MangaDxImportService;
use App\Repository\OeuvreRepository;
use App\Repository\ChapitreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:import-massive-data',
    description: 'Importe massivement des œuvres depuis MangaDx pour alimenter la base de données',
)]
class ImportMassiveDataCommand extends Command
{
    public function __construct(
        private MangaDxService $mangaDxService,
        private MangaDxImportService $importService,
        private OeuvreRepository $oeuvreRepository,
        private ChapitreRepository $chapitreRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre d\'œuvres à importer', 50)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Vider la base avant import (DESTRUCTEUR)')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Mode simulation (aucune sauvegarde)')
            ->addOption('start-offset', 's', InputOption::VALUE_OPTIONAL, 'Décalage de départ pour la pagination', 0)
            ->addOption('category', 'c', InputOption::VALUE_OPTIONAL, 'Catégorie à importer (popular, latest, random)', 'popular')
            ->setHelp('Cette commande importe massivement des œuvres depuis MangaDx pour créer une base de données complète.
            
Exemples :
- Importer 50 mangas populaires : php bin/console app:import-massive-data
- Importer 100 derniers mangas : php bin/console app:import-massive-data --limit=100 --category=latest
- Mode simulation : php bin/console app:import-massive-data --dry-run
- Vider et recréer : php bin/console app:import-massive-data --force --limit=200')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $limit = (int) $input->getOption('limit');
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');
        $startOffset = (int) $input->getOption('start-offset');
        $category = $input->getOption('category');

        $io->title('🏭 Import Massif depuis MangaDx');
        
        // Validation des paramètres
        if ($limit <= 0 || $limit > 500) {
            $io->error('La limite doit être entre 1 et 500');
            return Command::FAILURE;
        }

        if (!in_array($category, ['popular', 'latest', 'random'])) {
            $io->error('Catégorie invalide. Utilise: popular, latest, ou random');
            return Command::FAILURE;
        }

        // Affichage des paramètres
        $io->section('📋 Paramètres');
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['Limite', $limit],
                ['Catégorie', $category],
                ['Décalage', $startOffset],
                ['Mode simulation', $dryRun ? '✅ Oui' : '❌ Non'],
                ['Vider la base', $force ? '⚠️ Oui' : '❌ Non'],
            ]
        );

        if ($force && !$dryRun) {
            $io->warning('ATTENTION : L\'option --force va SUPPRIMER toutes les œuvres existantes !');
            if (!$io->confirm('Êtes-vous absolument sûr de vouloir continuer ?')) {
                $io->note('Opération annulée');
                return Command::SUCCESS;
            }
        }

        // Statistiques avant import
        $statsAvant = $this->getStats();
        $io->section('📊 Statistiques avant import');
        $io->table(
            ['Type', 'Nombre'],
            [
                ['Œuvres', $statsAvant['oeuvres']],
                ['Chapitres', $statsAvant['chapitres']],
            ]
        );

        // Vider la base si demandé
        if ($force && !$dryRun) {
            $io->section('🧹 Nettoyage de la base de données');
            $this->clearDatabase($io);
        }

        // Import des œuvres
        $io->section("📥 Import de {$limit} œuvres ({$category})");
        
        $progressBar = $io->createProgressBar($limit);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | ⏱️ %elapsed:6s% | 📚 %message%');
        $progressBar->setMessage('Initialisation...');

        $successes = 0;
        $errors = 0;
        $skipped = 0;
        $errorMessages = [];

        try {
            // Récupérer la liste des œuvres depuis MangaDx
            $oeuvresData = $this->getOeuvresFromMangaDx($category, $limit, $startOffset);
            
            if (empty($oeuvresData)) {
                $io->error('Aucune œuvre trouvée sur MangaDx');
                return Command::FAILURE;
            }

            $progressBar->start();

            foreach ($oeuvresData as $index => $oeuvreData) {
                $mangadxId = $oeuvreData['id'];
                $title = $oeuvreData['attributes']['title']['en'] ?? $oeuvreData['attributes']['title']['fr'] ?? array_values($oeuvreData['attributes']['title'])[0] ?? 'Titre inconnu';
                
                $progressBar->setMessage($title);
                
                try {
                    // Vérifier si l'œuvre existe déjà
                    $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangadxId]);
                    
                    if ($existingOeuvre) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    if (!$dryRun) {
                        // Importer l'œuvre complète
                        $oeuvre = $this->importService->importOrUpdateOeuvre($mangadxId);
                        
                        if ($oeuvre) {
                            $successes++;
                            $this->logger->info("Œuvre importée avec succès", [
                                'title' => $oeuvre->getTitre(),
                                'mangadx_id' => $mangadxId,
                                'chapters_count' => count($oeuvre->getChapitres())
                            ]);
                        } else {
                            $errors++;
                            $errorMessages[] = "Échec import: {$title}";
                        }
                    } else {
                        // Mode simulation
                        $successes++;
                        $this->logger->info("Simulation import", [
                            'title' => $title,
                            'mangadx_id' => $mangadxId
                        ]);
                    }

                } catch (\Exception $e) {
                    $errors++;
                    $errorMessages[] = "Erreur {$title}: " . $e->getMessage();
                    $this->logger->error("Erreur import œuvre", [
                        'title' => $title,
                        'mangadx_id' => $mangadxId,
                        'error' => $e->getMessage()
                    ]);
                }

                $progressBar->advance();

                // Petite pause pour éviter le rate limiting
                if ((int) $index + 1 % 5 === 0) {
                    usleep(500000); // 0.5 secondes
                }
            }

            $progressBar->finish();
            $io->newLine(2);

        } catch (\Exception $e) {
            $progressBar->finish();
            $io->newLine(2);
            $io->error('Erreur lors de la récupération des données MangaDx: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Statistiques finales
        $statsApres = $this->getStats();
        $io->section('📊 Résultats de l\'import');
        
        $io->table(
            ['Résultat', 'Nombre', 'Détail'],
            [
                ['✅ Succès', $successes, $dryRun ? 'Simulés' : 'Importés réellement'],
                ['⏭️ Ignorés', $skipped, 'Œuvres déjà existantes'],
                ['❌ Erreurs', $errors, 'Échecs d\'import'],
                ['📚 Total œuvres', $statsApres['oeuvres'], "Avant: {$statsAvant['oeuvres']}"],
                ['📖 Total chapitres', $statsApres['chapitres'], "Avant: {$statsAvant['chapitres']}"],
            ]
        );

        // Afficher les erreurs s'il y en a
        if (!empty($errorMessages)) {
            $io->section('❌ Erreurs rencontrées');
            foreach (array_slice($errorMessages, 0, 10) as $error) {
                $io->text("• {$error}");
            }
            if (count($errorMessages) > 10) {
                $io->text('... et ' . (count($errorMessages) - 10) . ' autres erreurs');
            }
        }

        // Message final
        if ($dryRun) {
            $io->success("Simulation terminée ! {$successes} œuvres auraient été importées.");
        } else {
            $io->success("{$successes} œuvres importées avec succès depuis MangaDx !");
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function getOeuvresFromMangaDx(string $category, int $limit, int $offset): array
    {
        try {
            return match($category) {
                'popular' => $this->mangaDxService->getPopularManga($limit, $offset),
                'latest' => $this->mangaDxService->getLatestManga($limit, $offset),
                'random' => $this->mangaDxService->getRandomManga($limit),
                default => []
            };
        } catch (\Exception $e) {
            $this->logger->error("Erreur récupération œuvres MangaDx", [
                'category' => $category,
                'limit' => $limit,
                'offset' => $offset,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * @return array<string, int>
     */
    private function getStats(): array
    {
        return [
            'oeuvres' => $this->oeuvreRepository->count([]),
            'chapitres' => $this->chapitreRepository->count([]),
        ];
    }

    private function clearDatabase(SymfonyStyle $io): void
    {
        // Supprimer dans l'ordre correct pour respecter les contraintes de clé étrangère
        
        $io->text('Suppression des notes et commentaires...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\OeuvreNote')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CommentaireLike')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Commentaire')->execute();
        
        $io->text('Suppression des collections utilisateurs...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\CollectionUser')->execute();
        
        $io->text('Suppression des statuts...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Statut')->execute();
        
        $io->text('Suppression des chapitres...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Chapitre')->execute();
        
        $io->text('Suppression des relations oeuvre-tag...');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM oeuvre_tag');
        
        $io->text('Suppression des œuvres...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Oeuvre')->execute();
        
        $io->text('Réinitialisation des séquences...');
        try {
            // PostgreSQL utilise des séquences, pas AUTO_INCREMENT
            $sequencesToReset = [
                'oeuvre_note_id_seq',
                'commentaire_like_id_seq', 
                'commentaire_id_seq',
                'collection_user_id_seq',
                'statut_id_seq',
                'chapitre_id_seq',
                'oeuvre_id_seq'
            ];
            
            foreach ($sequencesToReset as $sequence) {
                $this->entityManager->getConnection()->executeStatement("ALTER SEQUENCE {$sequence} RESTART WITH 1");
            }
        } catch (\Exception $e) {
            // Si les séquences n'existent pas ou si on est sur MySQL, ignorer l'erreur
            $io->note('Impossible de réinitialiser certaines séquences: ' . $e->getMessage());
        }
        
        $io->success('Base de données nettoyée !');
    }
}
