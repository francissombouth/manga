<?php

namespace App\Command;

use App\Service\MangaDxService;
use App\Service\MangaDxImportService;
use App\Service\AdminPagesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-specific-oeuvre',
    description: 'Teste une œuvre spécifique avec l\'ID MangaDex fourni',
)]
class TestSpecificOeuvreCommand extends Command
{
    public function __construct(
        private MangaDxService $mangaDxService,
        private MangaDxImportService $importService,
        private AdminPagesService $adminPagesService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mangadxId = 'a77742b1-befd-49a4-bff5-1ad4e6b0ef7b';
        
        $io->title("Test de l'œuvre avec l'ID: {$mangadxId}");

        // 1. Récupérer les infos de l'œuvre depuis l'API
        $io->section("1. Récupération des infos de l'œuvre");
        $mangaData = $this->mangaDxService->getMangaById($mangadxId);
        
        if (!$mangaData) {
            $io->error("Impossible de récupérer les données de l'œuvre depuis l'API");
            return Command::FAILURE;
        }

        $title = $mangaData['attributes']['title']['en'] ?? $mangaData['attributes']['title']['ja'] ?? 'Titre inconnu';
        $io->text("✅ Œuvre trouvée: {$title}");

        // 2. Récupérer les chapitres depuis l'API
        $io->section("2. Récupération des chapitres depuis l'API");
        $chaptersData = $this->mangaDxService->getAllMangaChapters($mangadxId);
        
        if (empty($chaptersData)) {
            $io->error("Aucun chapitre trouvé dans l'API");
            return Command::FAILURE;
        }

        $io->text("✅ Chapitres trouvés: " . count($chaptersData));

        // 3. Tester la récupération des pages pour les premiers chapitres
        $io->section("3. Test de récupération des pages");
        $testedChapters = array_slice($chaptersData, 0, 3); // Tester les 3 premiers chapitres
        
        foreach ($testedChapters as $chapterData) {
            $chapterId = $chapterData['id'];
            $chapterTitle = $chapterData['attributes']['title'] ?? 'Chapitre ' . ($chapterData['attributes']['chapter'] ?? '?');
            $chapterNumber = $chapterData['attributes']['chapter'] ?? '?';
            
            $io->text("Test du chapitre {$chapterNumber}: {$chapterTitle} (ID: {$chapterId})");
            
            // Tester avec le service catalogue
            $pages = $this->mangaDxService->getChapterPages($chapterId);
            $pagesCount = count($pages);
            
            $io->text("  Pages récupérées via MangaDxService: {$pagesCount}");
            
            if ($pagesCount > 0) {
                $io->text("  Première page: " . substr($pages[0], 0, 80) . "...");
                $io->text("  Dernière page: " . substr($pages[count($pages)-1], 0, 80) . "...");
            }
            
            $io->newLine();
        }

        // 4. Importer l'œuvre dans notre base
        $io->section("4. Import de l'œuvre dans notre base");
        try {
            $oeuvre = $this->importService->importOrUpdateOeuvre($mangadxId);
            
            if ($oeuvre) {
                $io->text("✅ Œuvre importée avec succès");
                $io->text("   - Titre: {$oeuvre->getTitre()}");
                $io->text("   - ID interne: {$oeuvre->getId()}");
                $io->text("   - MangaDx ID: {$oeuvre->getMangadxId()}");
                $io->text("   - Chapitres: " . count($oeuvre->getChapitres()));
                
                // 5. Tester avec le service admin
                $io->section("5. Test avec le service AdminPagesService");
                $chapitres = $oeuvre->getChapitres();
                $firstChapters = array_slice($chapitres->toArray(), 0, 3);
                
                foreach ($firstChapters as $chapitre) {
                    $io->text("Test du chapitre {$chapitre->getOrdre()}: {$chapitre->getTitre()}");
                    $io->text("  - MangaDx Chapter ID: {$chapitre->getMangadxChapterId()}");
                    
                    $pages = $this->adminPagesService->getChapitrePages($chapitre);
                    $pagesCount = count($pages);
                    
                    $io->text("  - Pages récupérées via AdminPagesService: {$pagesCount}");
                    
                    if ($pagesCount > 0) {
                        $io->text("  - Première page: " . substr($pages[0], 0, 80) . "...");
                    }
                    
                    $io->newLine();
                }
                
            } else {
                $io->text("❌ Échec de l'import");
            }
            
        } catch (\Exception $e) {
            $io->text("❌ Erreur lors de l'import: " . $e->getMessage());
        }

        $io->success("Test terminé !");

        return Command::SUCCESS;
    }
} 