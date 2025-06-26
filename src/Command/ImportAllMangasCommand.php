<?php

namespace App\Command;

use App\Entity\CollectionUser;
use App\Repository\CollectionUserRepository;
use App\Repository\OeuvreRepository;
use App\Repository\UserRepository;
use App\Service\MangaDxImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-all-mangas',
    description: 'Importe TOUS les mangas disponibles sur MangaDx dans la base de données',
)]
class ImportAllMangasCommand extends Command
{
    private const MANGADX_API_BASE = 'https://api.mangadx.org';
    private const BATCH_SIZE = 100; // Nombre de mangas par lot
    private const DELAY_BETWEEN_BATCHES = 2; // Délai en secondes entre les lots

    public function __construct(
        private HttpClientInterface $httpClient,
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
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Nombre de mangas par lot', self::BATCH_SIZE)
            ->addOption('max-batches', null, InputOption::VALUE_OPTIONAL, 'Nombre maximum de lots à traiter (pour tests)')
            ->addOption('start-offset', null, InputOption::VALUE_OPTIONAL, 'Offset de départ (pour reprendre)', 0)
            ->addOption('add-to-favorites', 'f', InputOption::VALUE_NONE, 'Ajouter automatiquement tous les mangas aux favoris')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Mode simulation')
            ->setHelp('Cette commande importe TOUS les mangas disponibles sur MangaDx dans votre base de données.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getOption('user-id');
        $userEmail = $input->getOption('user-email');
        $batchSize = (int) $input->getOption('batch-size');
        $maxBatches = $input->getOption('max-batches') ? (int) $input->getOption('max-batches') : null;
        $startOffset = (int) $input->getOption('start-offset');
        $addToFavorites = $input->getOption('add-to-favorites');
        $dryRun = $input->getOption('dry-run');

        // Récupérer l'utilisateur si on doit ajouter aux favoris
        $user = null;
        if ($addToFavorites) {
            if (!$userId && !$userEmail) {
                $io->error('Vous devez spécifier --user-id ou --user-email pour utiliser --add-to-favorites');
                return Command::FAILURE;
            }

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
        }

        $io->title("Import complet de TOUS les mangas MangaDx");
        
        if ($user) {
            $io->text("👤 Utilisateur: {$user->getNom()} ({$user->getEmail()})");
        }
        $io->text("📦 Taille des lots: {$batchSize} mangas");
        $io->text("🚀 Offset de départ: {$startOffset}");
        $io->text("⭐ Ajout aux favoris: " . ($addToFavorites ? 'Oui' : 'Non'));
        $io->text("🧪 Mode simulation: " . ($dryRun ? 'Oui' : 'Non'));
        
        if ($maxBatches) {
            $io->text("🔢 Limite de lots: {$maxBatches}");
        }

        $io->newLine();

        // Étape 1 : Obtenir le total de mangas disponibles
        $io->section("1. Récupération du nombre total de mangas");
        
        try {
            $totalMangas = $this->getTotalMangasCount();
            $io->success("✅ {$totalMangas} mangas disponibles sur MangaDx");
        } catch (\Exception $e) {
            $io->error("Erreur lors de la récupération du total : " . $e->getMessage());
            return Command::FAILURE;
        }

        // Calcul des lots
        $totalBatches = ceil(($totalMangas - $startOffset) / $batchSize);
        if ($maxBatches) {
            $totalBatches = min($totalBatches, $maxBatches);
        }

        $io->text("📊 Lots à traiter : {$totalBatches}");
        $estimatedTime = $totalBatches * (self::DELAY_BETWEEN_BATCHES + 30); // ~30 sec par lot
        $io->text("⏱️  Temps estimé : ~" . gmdate('H:i:s', $estimatedTime));

        if (!$dryRun && $totalBatches > 50) {
            $io->warning("⚠️  Import de grande envergure ! Vérifiez :");
            $io->listing([
                'Connexion internet stable',
                'Espace disque suffisant',
                'Temps disponible',
                'Surveillance du processus'
            ]);
            
            if (!$io->confirm("Continuer avec l'import complet ?", false)) {
                $io->info("Import annulé");
                return Command::SUCCESS;
            }
        }

        // Étape 2 : Import par lots
        $io->section("2. Import des mangas par lots");

        $stats = [
            'processed' => 0,
            'imported' => 0,
            'existed' => 0,
            'favorites_added' => 0,
            'favorites_existed' => 0,
            'errors' => 0
        ];

        $currentOffset = $startOffset;
        
        for ($batch = 1; $batch <= $totalBatches; $batch++) {
            $io->text("📦 Lot {$batch}/{$totalBatches} (offset: {$currentOffset})");
            
            try {
                $batchStats = $this->processBatch($currentOffset, $batchSize, $user, $addToFavorites, $dryRun, $io);
                
                foreach ($batchStats as $key => $value) {
                    $stats[$key] += $value;
                }

                $currentOffset += $batchSize;

                // Affichage du progrès
                $progress = round(($batch / $totalBatches) * 100, 1);
                $io->text("   ✅ Lot terminé | Progrès: {$progress}% | Importés: {$batchStats['imported']} | Erreurs: {$batchStats['errors']}");

                // Délai entre les lots pour éviter la surcharge de l'API
                if ($batch < $totalBatches && !$dryRun) {
                    $io->text("   ⏸️  Pause de " . self::DELAY_BETWEEN_BATCHES . " secondes...");
                    sleep(self::DELAY_BETWEEN_BATCHES);
                }

            } catch (\Exception $e) {
                $io->error("Erreur dans le lot {$batch} : " . $e->getMessage());
                $stats['errors'] += $batchSize; // Compter tout le lot comme erreur
                
                if (!$io->confirm("Continuer malgré l'erreur ?", true)) {
                    break;
                }
            }
        }

        // Étape 3 : Résultats finaux
        $io->section("3. Résultats finaux");

        if ($dryRun) {
            $io->note("Mode simulation - aucune modification effectuée");
        }

        $io->createTable()
            ->setHeaders(['Statistique', 'Nombre'])
            ->setRows([
                ['📊 Mangas traités', $stats['processed']],
                ['✅ Nouveaux imports', $stats['imported']],
                ['ℹ️  Déjà en base', $stats['existed']],
                $addToFavorites ? ['⭐ Ajoutés aux favoris', $stats['favorites_added']] : null,
                $addToFavorites ? ['⏭️  Déjà en favoris', $stats['favorites_existed']] : null,
                ['❌ Erreurs', $stats['errors']]
            ])
            ->render();

        if ($addToFavorites && $user && !$dryRun) {
            $totalFavorites = $this->collectionRepository->count(['user' => $user]);
            $io->info("⭐ Total de favoris : {$totalFavorites}");
        }

        $totalInDb = $this->oeuvreRepository->count([]);
        $io->info("📚 Total en base de données : {$totalInDb}");

        if ($stats['imported'] > 0) {
            $io->success("🎉 Import terminé avec succès !");
        }

        return Command::SUCCESS;
    }

    private function getTotalMangasCount(): int
    {
        $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga', [
            'query' => [
                'limit' => 1,
                'contentRating' => ['safe', 'suggestive', 'erotica'],
                'availableTranslatedLanguage' => ['en', 'fr'],
                'hasAvailableChapters' => 'true',
                'order' => ['followedCount' => 'desc']
            ],
            'headers' => [
                'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
            ]
        ]);

        $data = $response->toArray();
        return isset($data['total']) ? $data['total'] : 0;
    }

    private function processBatch(int $offset, int $limit, $user, bool $addToFavorites, bool $dryRun, SymfonyStyle $io): array
    {
        $stats = [
            'processed' => 0,
            'imported' => 0,
            'existed' => 0,
            'favorites_added' => 0,
            'favorites_existed' => 0,
            'errors' => 0
        ];

        // Récupérer le lot de mangas
        $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga', [
            'query' => [
                'limit' => $limit,
                'offset' => $offset,
                'contentRating' => ['safe', 'suggestive', 'erotica'],
                'availableTranslatedLanguage' => ['en', 'fr'],
                'hasAvailableChapters' => 'true',
                'order' => ['followedCount' => 'desc'],
                'includes' => ['author', 'artist', 'tag', 'cover_art']
            ],
            'headers' => [
                'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Erreur HTTP : " . $response->getStatusCode());
        }

        $data = $response->toArray();
        $mangas = isset($data['data']) ? $data['data'] : [];

        foreach ($mangas as $mangaData) {
            $stats['processed']++;
            $mangaId = $mangaData['id'];

            try {
                if ($dryRun) {
                    $title = isset($mangaData['attributes']['title']['en']) ? $mangaData['attributes']['title']['en'] : 'Titre inconnu';
                    $io->text("   🧪 [Simulation] {$title}");
                    continue;
                }

                // Vérifier si le manga existe déjà
                $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangaId]);
                
                if (!$existingOeuvre) {
                    // Importer le nouveau manga
                    $oeuvre = $this->importService->importOrUpdateOeuvre($mangaId);
                    if ($oeuvre) {
                        $stats['imported']++;
                    } else {
                        $stats['errors']++;
                        continue;
                    }
                } else {
                    $oeuvre = $existingOeuvre;
                    $stats['existed']++;
                }

                // Ajouter aux favoris si demandé
                if ($addToFavorites && $user && $oeuvre) {
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

            } catch (\Exception $e) {
                $stats['errors']++;
                // Continue avec le manga suivant
            }
        }

        // Flush périodique pour éviter les problèmes de mémoire
        if (!$dryRun) {
            $this->entityManager->flush();
            $this->entityManager->clear(); // Libérer la mémoire
        }

        return $stats;
    }
} 