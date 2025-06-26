<?php

namespace App\Command;

use App\Service\MangaDxService;
use App\Service\MangaDxImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-import',
    description: 'Teste l\'import d\'œuvres avec le nouveau système mangadxChapterId',
)]
class TestImportCommand extends Command
{
    public function __construct(
        private MangaDxService $mangaDxService,
        private MangaDxImportService $importService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test d\'import d\'œuvres avec mangadxChapterId');

        // Récupérer quelques mangas populaires
        $popularMangas = $this->mangaDxService->getPopularManga(5, 0);
        
        if (empty($popularMangas)) {
            $io->error('Aucun manga populaire trouvé');
            return Command::FAILURE;
        }

        $io->info('Mangas populaires trouvés : ' . count($popularMangas));

        $importedCount = 0;
        $totalChapters = 0;
        $totalPages = 0;

        foreach ($popularMangas as $mangaData) {
            $mangaId = $mangaData['id'];
            $title = $mangaData['attributes']['title']['en'] ?? $mangaData['attributes']['title']['ja'] ?? 'Titre inconnu';
            
            $io->section("Import de : {$title} (ID: {$mangaId})");

            try {
                // Importer l'œuvre
                $oeuvre = $this->importService->importOrUpdateOeuvre($mangaId);
                
                if ($oeuvre) {
                    $importedCount++;
                    $chapters = $oeuvre->getChapitres();
                    $totalChapters += count($chapters);
                    
                    $io->text("✅ Œuvre importée avec succès");
                    $io->text("   - Titre: {$oeuvre->getTitre()}");
                    $io->text("   - Chapitres: " . count($chapters));
                    $io->text("   - MangaDx ID: {$oeuvre->getMangadxId()}");
                    
                    // Vérifier les mangadxChapterId
                    $chaptersWithId = 0;
                    $chaptersWithPages = 0;
                    
                    foreach ($chapters as $chapitre) {
                        if ($chapitre->getMangadxChapterId()) {
                            $chaptersWithId++;
                        }
                        if (!empty($chapitre->getPages())) {
                            $chaptersWithPages += count($chapitre->getPages());
                        }
                    }
                    
                    $io->text("   - Chapitres avec mangadxChapterId: {$chaptersWithId}/" . count($chapters));
                    $io->text("   - Pages totales: {$chaptersWithPages}");
                    
                    $totalPages += $chaptersWithPages;
                    
                    // Afficher les détails des 3 premiers chapitres
                    $firstChapters = array_slice($chapters->toArray(), 0, 3);
                    foreach ($firstChapters as $chapitre) {
                        $io->text("     • Chapitre {$chapitre->getOrdre()}: {$chapitre->getTitre()}");
                        $io->text("       - MangaDx Chapter ID: {$chapitre->getMangadxChapterId()}");
                        $io->text("       - Pages: " . count($chapitre->getPages()));
                    }
                    
                } else {
                    $io->text("❌ Échec de l'import");
                }
                
            } catch (\Exception $e) {
                $io->text("❌ Erreur lors de l'import: " . $e->getMessage());
            }
            
            $io->newLine();
        }

        $io->success([
            "Import terminé !",
            "Œuvres importées: {$importedCount}",
            "Total chapitres: {$totalChapters}",
            "Total pages: {$totalPages}"
        ]);

        return Command::SUCCESS;
    }
} 