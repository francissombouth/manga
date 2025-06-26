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
    name: 'app:import-top100-mangas',
    description: 'Nettoie le catalogue et importe les 100 mangas les plus populaires de MangaDx',
)]
class ImportTop100MangasCommand extends Command
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
            ->addOption('user-id', 'u', InputOption::VALUE_REQUIRED, 'ID de l\'utilisateur pour lequel ajouter aux favoris')
            ->addOption('user-email', 'm', InputOption::VALUE_REQUIRED, 'Email de l\'utilisateur pour lequel ajouter aux favoris')
            ->addOption('keep-favorites', 'k', InputOption::VALUE_NONE, 'Garder les favoris existants lors du nettoyage')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Mode simulation')
            ->setHelp('Cette commande nettoie le catalogue actuel et importe les 100 mangas les plus populaires de MangaDx.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getOption('user-id');
        $userEmail = $input->getOption('user-email');
        $keepFavorites = $input->getOption('keep-favorites');
        $dryRun = $input->getOption('dry-run');

        // Récupérer l'utilisateur
        $user = null;
        if ($userId) {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                $io->error("Utilisateur avec l'ID {$userId} non trouvé");
                return Command::FAILURE;
            }
        } elseif ($userEmail) {
            $user = $this->userRepository->findOneBy(['email' => $userEmail]);
            if (!$user) {
                $io->error("Utilisateur avec l'email {$userEmail} non trouvé");
                return Command::FAILURE;
            }
        }

        $io->title("Import des 100 mangas les plus populaires de MangaDx");
        
        if ($user) {
            $io->text("👤 Utilisateur: {$user->getNom()} ({$user->getEmail()})");
        }
        $io->text("🔥 Mode: Top 100 mangas les plus populaires");
        $io->text("💫 Conserver favoris: " . ($keepFavorites ? 'Oui' : 'Non'));
        $io->text("🧪 Mode simulation: " . ($dryRun ? 'Oui' : 'Non'));

        $io->newLine();

        // Étape 1 : Statistiques actuelles
        $io->section("1. État actuel du catalogue");
        
        $currentCount = $this->oeuvreRepository->count([]);
        $currentFavorites = $user ? $this->collectionRepository->count(['user' => $user]) : 0;
        
        $io->text("📚 Œuvres actuelles en base : {$currentCount}");
        if ($user) {
            $io->text("⭐ Favoris actuels : {$currentFavorites}");
        }

        // Étape 2 : Nettoyage du catalogue (seulement si pas vide)
        if (!$dryRun && $currentCount > 0) {
            $io->section("2. Nettoyage du catalogue actuel");
            
            if ($keepFavorites && $user && $currentFavorites > 0) {
                $io->warning("⚠️  Attention : Le nettoyage va supprimer toutes les œuvres SAUF vos favoris actuels.");
            } else {
                $io->warning("⚠️  Attention : Le nettoyage va supprimer TOUTES les œuvres du catalogue.");
            }
            
            if ($io->confirm("Confirmer le nettoyage du catalogue ?", false)) {
                $this->cleanCatalog($user, $keepFavorites, $io);
                $io->success("✅ Catalogue nettoyé");
            } else {
                $io->info("Nettoyage annulé - conservation du catalogue actuel");
            }
        } elseif ($dryRun) {
            $io->section("2. [Simulation] Nettoyage du catalogue");
            $io->note("Mode simulation - le catalogue ne sera pas modifié");
        } elseif ($currentCount === 0) {
            $io->section("2. Catalogue vide");
            $io->text("📭 Le catalogue est déjà vide, pas de nettoyage nécessaire");
        }

        // Étape 3 : Import du Top 100
        $io->section("3. Import des 100 mangas les plus populaires");

        try {
            $stats = $this->importTop100Mangas($user, $dryRun, $io);
        } catch (\Exception $e) {
            $io->error("Erreur lors de l'import : " . $e->getMessage());
            return Command::FAILURE;
        }

        // Étape 4 : Résultats finaux
        $io->section("4. Résultats finaux");

        if ($dryRun) {
            $io->note("Mode simulation - aucune modification effectuée");
        }

        $io->createTable()
            ->setHeaders(['Statistique', 'Nombre'])
            ->setRows([
                ['🔥 Mangas populaires traités', '100'],
                ['✅ Nouveaux imports', $stats['imported']],
                ['ℹ️  Déjà en base', $stats['existed']],
                ['⭐ Ajoutés aux favoris', $stats['favorites_added']],
                ['⏭️  Déjà en favoris', $stats['favorites_existed']],
                ['❌ Erreurs', $stats['errors']]
            ])
            ->render();

        $finalCount = $this->oeuvreRepository->count([]);
        $finalFavorites = $user ? $this->collectionRepository->count(['user' => $user]) : 0;

        $io->info("📚 Total final en base : {$finalCount}");
        if ($user) {
            $io->info("⭐ Total final favoris : {$finalFavorites}");
        }

        if ($stats['imported'] > 0) {
            $io->success("🎉 Import du Top 100 terminé avec succès !");
        }

        return Command::SUCCESS;
    }

    private function cleanCatalog($user, bool $keepFavorites, SymfonyStyle $io): void
    {
        if ($keepFavorites && $user) {
            // Supprimer toutes les œuvres SAUF celles en favoris
            $favoriteOeuvres = $this->collectionRepository->createQueryBuilder('c')
                ->select('IDENTITY(c.oeuvre)')
                ->where('c.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getSingleColumnResult();

            if (!empty($favoriteOeuvres)) {
                // Supprimer d'abord les chapitres des œuvres non favorites
                $io->text("🗑️  Suppression des chapitres...");
                $this->entityManager->createQueryBuilder()
                    ->delete('App\Entity\Chapitre', 'ch')
                    ->where('ch.oeuvre NOT IN (:favoriteIds)')
                    ->setParameter('favoriteIds', $favoriteOeuvres)
                    ->getQuery()
                    ->execute();

                // Puis supprimer les œuvres non favorites
                $io->text("🗑️  Suppression des œuvres non favorites...");
                $this->entityManager->createQueryBuilder()
                    ->delete('App\Entity\Oeuvre', 'o')
                    ->where('o.id NOT IN (:favoriteIds)')
                    ->setParameter('favoriteIds', $favoriteOeuvres)
                    ->getQuery()
                    ->execute();
            } else {
                // Aucun favori, supprimer tout
                $this->cleanAllData($io);
            }
        } else {
            // Supprimer toutes les données
            $this->cleanAllData($io);
        }

        $this->entityManager->flush();
        $io->text("🧹 Catalogue nettoyé");
    }

    private function cleanAllData(SymfonyStyle $io): void
    {
        // Supprimer dans l'ordre correct pour respecter les contraintes de clé étrangère
        
        // 1. Supprimer les relations de collection
        $io->text("🗑️  Suppression des favoris...");
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\CollectionUser', 'c')
            ->getQuery()
            ->execute();

        // 2. Supprimer tous les chapitres
        $io->text("🗑️  Suppression des chapitres...");
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Chapitre', 'ch')
            ->getQuery()
            ->execute();

        // 3. Supprimer toutes les œuvres
        $io->text("🗑️  Suppression des œuvres...");
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Oeuvre', 'o')
            ->getQuery()
            ->execute();
    }

    private function importTop100Mangas($user, bool $dryRun, SymfonyStyle $io): array
    {
        $stats = [
            'imported' => 0,
            'existed' => 0,
            'favorites_added' => 0,
            'favorites_existed' => 0,
            'errors' => 0
        ];

        $io->text("🔍 Récupération du Top 100 depuis MangaDx...");

        // Récupérer les 100 mangas les plus populaires en utilisant le service qui fonctionne
        $mangas = $this->mangaDxService->getPopularManga(100, 0);

        $io->text("📦 " . count($mangas) . " mangas populaires récupérés");
        $io->newLine();

        $progressBar = $io->createProgressBar(count($mangas));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Démarrage...');
        $progressBar->start();

        foreach ($mangas as $index => $mangaData) {
            $mangaId = $mangaData['id'];
            $title = isset($mangaData['attributes']['title']['en']) ? $mangaData['attributes']['title']['en'] : 'Titre inconnu';
            
            $progressBar->setMessage("Import: {$title}");

            try {
                if ($dryRun) {
                    $progressBar->setMessage("[Simulation] {$title}");
                    $progressBar->advance();
                    continue;
                }

                // Vérifier si le manga existe déjà
                $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangaId]);
                
                if (!$existingOeuvre) {
                    // Importer le nouveau manga
                    $oeuvre = $this->importService->importOrUpdateOeuvre($mangaId);
                    if ($oeuvre) {
                        $stats['imported']++;
                        $progressBar->setMessage("✅ Importé: {$title}");
                    } else {
                        $stats['errors']++;
                        $progressBar->setMessage("❌ Erreur: {$title}");
                        $progressBar->advance();
                        continue;
                    }
                } else {
                    $oeuvre = $existingOeuvre;
                    $stats['existed']++;
                    $progressBar->setMessage("ℹ️  Existant: {$title}");
                }

                // Ajouter automatiquement aux favoris si on a un utilisateur
                if ($user && $oeuvre) {
                    $existingFavorite = $this->collectionRepository->findOneBy([
                        'user' => $user,
                        'oeuvre' => $oeuvre
                    ]);

                    if (!$existingFavorite) {
                        $favorite = new CollectionUser();
                        $favorite->setUser($user);
                        $favorite->setOeuvre($oeuvre);
                        $this->entityManager->persist($favorite);
                        $stats['favorites_added']++;
                    } else {
                        $stats['favorites_existed']++;
                    }
                }

                // Flush périodique
                if (($index + 1) % 10 === 0) {
                    $this->entityManager->flush();
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $progressBar->setMessage("❌ Erreur: {$title}");
            }

            $progressBar->advance();
        }

        $progressBar->setMessage('Import terminé !');
        $progressBar->finish();
        $io->newLine(2);

        // Flush final
        if (!$dryRun) {
            $this->entityManager->flush();
        }

        return $stats;
    }
} 
