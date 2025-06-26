<?php

namespace App\Command;

use App\Entity\CollectionUser;
use App\Repository\CollectionUserRepository;
use App\Repository\OeuvreRepository;
use App\Repository\UserRepository;
use App\Service\MangaDxImportService;
use App\Service\MangaDxService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-simple-100',
    description: 'Vide la base et importe exactement 100 mangas populaires',
)]
class ImportSimple100Command extends Command
{
    public function __construct(
        private MangaDxService $mangaDxService,
        private MangaDxImportService $importService,
        private OeuvreRepository $oeuvreRepository,
        private UserRepository $userRepository,
        private CollectionUserRepository $collectionRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user-id', 'u', InputOption::VALUE_REQUIRED, 'ID de l\'utilisateur', 36)
            ->setHelp('Cette commande vide la base et importe exactement 100 mangas populaires de MangaDx.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = (int) $input->getOption('user-id');

        // Récupérer l'utilisateur
        $user = $this->userRepository->find($userId);
        if (!$user) {
            $io->error("Utilisateur avec l'ID {$userId} non trouvé");
            return Command::FAILURE;
        }

        $io->title("🔥 Import Simple : 100 Mangas Populaires");
        $io->text("👤 Utilisateur: {$user->getNom()} ({$user->getEmail()})");
        $io->newLine();

        // Étape 1: Nettoyage complet
        $io->section("1. Nettoyage de la base de données");
        
        $currentCount = $this->oeuvreRepository->count([]);
        $io->text("📚 Œuvres actuelles : {$currentCount}");
        
        if ($currentCount > 0) {
            if ($io->confirm("Supprimer toutes les œuvres actuelles pour recommencer ?", true)) {
                $this->cleanDatabase($io);
                $io->success("✅ Base de données nettoyée");
            } else {
                $io->info("Nettoyage annulé");
                return Command::SUCCESS;
            }
        }

        // Étape 2: Récupération du top 100
        $io->section("2. Récupération des 100 mangas les plus populaires");
        
        try {
            $io->text("🔍 Interrogation de l'API MangaDx...");
            $mangas = $this->mangaDxService->getPopularManga(100, 0);
            
            if (empty($mangas)) {
                $io->error("❌ Aucun manga récupéré depuis l'API");
                return Command::FAILURE;
            }
            
            $io->success("✅ " . count($mangas) . " mangas récupérés avec succès");
            
        } catch (\Exception $e) {
            $io->error("Erreur API : " . $e->getMessage());
            return Command::FAILURE;
        }

        // Étape 3: Import en base
        $io->section("3. Import des mangas en base de données");
        
        $stats = [
            'imported' => 0,
            'favorites' => 0,
            'errors' => 0
        ];

        $progressBar = $io->createProgressBar(count($mangas));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->start();

        foreach ($mangas as $index => $mangaData) {
            $mangaId = $mangaData['id'];
            $title = $this->extractTitle($mangaData);
            
            $progressBar->setMessage("Import: {$title}");

            try {
                // Importer le manga
                $oeuvre = $this->importService->importOrUpdateOeuvre($mangaId);
                
                if ($oeuvre) {
                    $stats['imported']++;
                    
                    // Ajouter automatiquement aux favoris
                    $favorite = new CollectionUser();
                    $favorite->setUser($user);
                    $favorite->setOeuvre($oeuvre);
                    $this->entityManager->persist($favorite);
                    $stats['favorites']++;
                    
                    $progressBar->setMessage("✅ {$title}");
                } else {
                    $stats['errors']++;
                    $progressBar->setMessage("❌ {$title}");
                }

                // Flush périodique pour éviter les problèmes de mémoire
                if (($index + 1) % 10 === 0) {
                    $this->entityManager->flush();
                    $progressBar->setMessage("💾 Sauvegarde... ({$stats['imported']} importés)");
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $progressBar->setMessage("❌ Erreur: {$title}");
            }

            $progressBar->advance();
        }

        // Flush final
        $this->entityManager->flush();
        
        $progressBar->setMessage('Import terminé !');
        $progressBar->finish();
        $io->newLine(2);

        // Résultats finaux
        $io->section("4. Résultats");
        
        $finalCount = $this->oeuvreRepository->count([]);
        $finalFavorites = $this->collectionRepository->count(['user' => $user]);

        $io->createTable()
            ->setHeaders(['Statistique', 'Nombre'])
            ->setRows([
                ['📚 Mangas en base', $finalCount],
                ['⭐ Favoris utilisateur', $finalFavorites],
                ['✅ Nouveaux imports', $stats['imported']],
                ['❌ Erreurs', $stats['errors']]
            ])
            ->render();

        if ($stats['imported'] > 0) {
            $io->success("🎉 Import terminé ! Vous avez maintenant exactement {$finalCount} mangas dans votre catalogue.");
            $io->info("💡 Tous ces mangas sont automatiquement dans vos favoris pour un accès facile !");
        }

        return Command::SUCCESS;
    }

    private function cleanDatabase(SymfonyStyle $io): void
    {
        $io->text("🗑️  Suppression des favoris...");
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\CollectionUser', 'c')
            ->getQuery()
            ->execute();

        $io->text("🗑️  Suppression des chapitres...");
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Chapitre', 'ch')
            ->getQuery()
            ->execute();

        $io->text("🗑️  Suppression des œuvres...");
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Oeuvre', 'o')
            ->getQuery()
            ->execute();

        $this->entityManager->flush();
    }

    private function extractTitle(array $mangaData): string
    {
        $attributes = $mangaData['attributes'] ?? [];
        $titles = $attributes['title'] ?? [];
        
        return $titles['en'] ?? $titles['fr'] ?? $titles['ja-ro'] ?? 
               (is_array($titles) ? array_values($titles)[0] ?? 'Titre inconnu' : 'Titre inconnu');
    }
} 