<?php

namespace App\Command;

use App\Entity\CollectionUser;
use App\Entity\User;
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
    name: 'app:import-catalog-to-collection',
    description: 'Importe toutes les œuvres du catalogue MangaDx dans la base de données et les ajoute à vos favoris',
)]
class ImportCatalogToCollectionCommand extends Command
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
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre maximum d\'œuvres à importer', 100)
            ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'Offset pour commencer à partir d\'un certain rang', 0)
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Mode simulation - affiche ce qui serait fait sans rien exécuter')
            ->setHelp('Cette commande importe les œuvres populaires du catalogue MangaDx dans votre base de données et les ajoute automatiquement à vos favoris.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getOption('user-id');
        $userEmail = $input->getOption('user-email');
        $limit = (int) $input->getOption('limit');
        $offset = (int) $input->getOption('offset');
        $dryRun = $input->getOption('dry-run');

        // Validation des paramètres
        if (!$userId && !$userEmail) {
            $io->error('Vous devez spécifier soit --user-id soit --user-email');
            return Command::FAILURE;
        }

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

        $io->title("Import du catalogue MangaDx vers vos favoris");
        $io->text("👤 Utilisateur: {$user->getNom()} ({$user->getEmail()})");
        $io->text("📊 Limite: {$limit} œuvres");
        $io->text("⏯️  Offset: {$offset}");
        $io->text("🧪 Mode simulation: " . ($dryRun ? 'Oui' : 'Non'));
        $io->newLine();

        // Récupérer les mangas populaires du catalogue
        $io->section("1. Récupération du catalogue MangaDx");
        try {
            $popularMangas = $this->mangaDxService->getPopularManga($limit, $offset);
            
            if (empty($popularMangas)) {
                $io->error('Aucun manga trouvé dans le catalogue');
                return Command::FAILURE;
            }

            $io->success("✅ {$limit} mangas récupérés depuis le catalogue");
        } catch (\Exception $e) {
            $io->error("Erreur lors de la récupération du catalogue: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Statistiques
        $importedCount = 0;
        $addedToCollectionCount = 0;
        $alreadyInCollectionCount = 0;
        $errorCount = 0;

        $io->section("2. Import et ajout aux favoris");

        foreach ($popularMangas as $index => $mangaData) {
            $mangaId = $mangaData['id'];
            $title = $mangaData['attributes']['title']['en'] ?? $mangaData['attributes']['title']['ja'] ?? 'Titre inconnu';
            
            $io->text("📖 (" . ($index + 1) . "/{$limit}) {$title}");

            if ($dryRun) {
                $io->text("   🧪 [Simulation] Œuvre serait importée et ajoutée aux favoris");
                continue;
            }

            try {
                // 1. Importer l'œuvre en base de données
                $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangaId]);
                
                if (!$existingOeuvre) {
                    $oeuvre = $this->importService->importOrUpdateOeuvre($mangaId);
                    if ($oeuvre) {
                        $importedCount++;
                        $io->text("   ✅ Œuvre importée en base de données");
                    } else {
                        $io->text("   ❌ Échec de l'import");
                        $errorCount++;
                        continue;
                    }
                } else {
                    $oeuvre = $existingOeuvre;
                    $io->text("   ℹ️  Œuvre déjà présente en base");
                }

                // 2. Vérifier si l'œuvre est déjà dans les favoris
                $existingCollection = $this->collectionRepository->findOneBy([
                    'user' => $user,
                    'oeuvre' => $oeuvre
                ]);

                if ($existingCollection) {
                    $alreadyInCollectionCount++;
                    $io->text("   ⏭️  Déjà dans vos favoris");
                } else {
                    // 3. Ajouter aux favoris
                    $collection = new CollectionUser();
                    $collection->setUser($user);
                    $collection->setOeuvre($oeuvre);
                    
                    $this->entityManager->persist($collection);
                    $addedToCollectionCount++;
                    $io->text("   ⭐ Ajoutée à vos favoris");
                }

                // Flush périodique pour éviter les problèmes de mémoire
                if (($importedCount + $addedToCollectionCount) % 10 === 0) {
                    $this->entityManager->flush();
                    $io->text("   💾 Sauvegarde intermédiaire...");
                }

            } catch (\Exception $e) {
                $io->text("   ❌ Erreur: " . $e->getMessage());
                $errorCount++;
            }
        }

        // Sauvegarde finale
        if (!$dryRun) {
            $this->entityManager->flush();
            $io->text("💾 Sauvegarde finale effectuée");
        }

        // Statistiques finales
        $io->section("3. Résultats");
        
        if ($dryRun) {
            $io->note("Mode simulation activé - aucune modification n'a été effectuée");
            $io->text("🧪 {$limit} œuvres auraient été traitées");
        } else {
            $io->createTable()
                ->setHeaders(['Statut', 'Nombre'])
                ->setRows([
                    ['✅ Nouvelles œuvres importées', $importedCount],
                    ['⭐ Ajoutées à vos favoris', $addedToCollectionCount],
                    ['⏭️  Déjà dans vos favoris', $alreadyInCollectionCount],
                    ['❌ Erreurs', $errorCount],
                    ['📊 Total traité', $index + 1]
                ])
                ->render();

            if ($addedToCollectionCount > 0) {
                $io->success("🎉 {$addedToCollectionCount} nouvelles œuvres ajoutées à vos favoris !");
            }

            // Afficher le total actuel des favoris
            $totalCollection = $this->collectionRepository->count(['user' => $user]);
            $io->info("⭐ Vous avez maintenant {$totalCollection} œuvres en favoris au total");
        }

        return Command::SUCCESS;
    }
} 