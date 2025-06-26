<?php

namespace App\Command;

use App\Service\MangaDxService;
use App\Service\MangaDxImportService;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-all-catalog',
    description: 'Importe toutes les œuvres du catalogue MangaDex avec le nouveau système',
)]
class ImportAllCatalogCommand extends Command
{
    public function __construct(
        private MangaDxService $mangaDxService,
        private MangaDxImportService $importService,
        private OeuvreRepository $oeuvreRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre maximum d\'œuvres à importer', 50)
            ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'Offset pour commencer à partir d\'un certain rang', 0)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forcer la mise à jour des œuvres existantes')
            ->setHelp('Cette commande importe les œuvres populaires du catalogue MangaDex avec le nouveau système mangadxChapterId.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = (int) $input->getOption('limit');
        $offset = (int) $input->getOption('offset');
        $force = $input->getOption('force');

        $io->title("Import du catalogue MangaDex");
        $io->text("Limite: {$limit} œuvres");
        $io->text("Offset: {$offset}");
        $io->text("Force mise à jour: " . ($force ? 'Oui' : 'Non'));

        // Récupérer les mangas populaires
        $io->section("1. Récupération des mangas populaires");
        $popularMangas = $this->mangaDxService->getPopularManga($limit, $offset);
        
        if (empty($popularMangas)) {
            $io->error('Aucun manga populaire trouvé');
            return Command::FAILURE;
        }

        $io->text("✅ {$limit} mangas populaires récupérés");

        // Statistiques
        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $totalChapters = 0;
        $totalChaptersWithId = 0;

        $io->section("2. Import des œuvres");

        foreach ($popularMangas as $index => $mangaData) {
            $mangaId = $mangaData['id'];
            $title = $mangaData['attributes']['title']['en'] ?? $mangaData['attributes']['title']['ja'] ?? 'Titre inconnu';
            
            $io->text("(" . ($index + 1) . "/{$limit}) Import de : {$title}");

            try {
                // Vérifier si l'œuvre existe déjà
                $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangaId]);
                
                if ($existingOeuvre && !$force) {
                    $io->text("  ⏭️  Œuvre déjà présente, ignorée");
                    $skippedCount++;
                    continue;
                }

                // Importer l'œuvre
                $oeuvre = $this->importService->importOrUpdateOeuvre($mangaId);
                
                if ($oeuvre) {
                    $chapitres = $oeuvre->getChapitres();
                    $chaptersCount = count($chapitres);
                    $chaptersWithId = 0;
                    
                    foreach ($chapitres as $chapitre) {
                        if ($chapitre->getMangadxChapterId()) {
                            $chaptersWithId++;
                        }
                    }
                    
                    $totalChapters += $chaptersCount;
                    $totalChaptersWithId += $chaptersWithId;
                    
                    if ($existingOeuvre) {
                        $updatedCount++;
                        $io->text("  ✅ Œuvre mise à jour ({$chaptersCount} chapitres, {$chaptersWithId} avec mangadxChapterId)");
                    } else {
                        $importedCount++;
                        $io->text("  ✅ Œuvre importée ({$chaptersCount} chapitres, {$chaptersWithId} avec mangadxChapterId)");
                    }
                    
                    // Flush périodique pour éviter les problèmes de mémoire
                    if (($importedCount + $updatedCount) % 10 === 0) {
                        $this->entityManager->flush();
                        $io->text("  💾 Sauvegarde intermédiaire...");
                    }
                    
                } else {
                    $io->text("  ❌ Échec de l'import");
                    $errorCount++;
                }
                
            } catch (\Exception $e) {
                $io->text("  ❌ Erreur: " . $e->getMessage());
                $errorCount++;
            }
            
            // Pause entre les imports pour éviter le rate limiting
            if ($index < count($popularMangas) - 1) {
                sleep(1);
            }
        }

        // Flush final
        $this->entityManager->flush();

        $io->section("3. Résumé");
        $io->success([
            "Import terminé !",
            "Œuvres importées: {$importedCount}",
            "Œuvres mises à jour: {$updatedCount}",
            "Œuvres ignorées: {$skippedCount}",
            "Erreurs: {$errorCount}",
            "Total chapitres: {$totalChapters}",
            "Chapitres avec mangadxChapterId: {$totalChaptersWithId}",
            "Taux de succès: " . round((($importedCount + $updatedCount) / ($importedCount + $updatedCount + $errorCount)) * 100, 1) . "%"
        ]);

        if ($totalChaptersWithId > 0) {
            $io->text("🎉 Les chapitres avec mangadxChapterId peuvent maintenant afficher leurs pages dans l'administration !");
        }

        return Command::SUCCESS;
    }
} 