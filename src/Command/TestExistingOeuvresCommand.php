<?php

namespace App\Command;

use App\Entity\Oeuvre;
use App\Repository\OeuvreRepository;
use App\Service\AdminPagesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-existing-oeuvres',
    description: 'Teste le nouveau système avec les œuvres existantes',
)]
class TestExistingOeuvresCommand extends Command
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository,
        private AdminPagesService $adminPagesService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du nouveau système avec les œuvres existantes');

        // Récupérer les œuvres avec un mangadxId
        $oeuvres = $this->oeuvreRepository->findAll();
        $oeuvresAvecMangadxId = [];
        
        foreach ($oeuvres as $oeuvre) {
            if ($oeuvre->getMangadxId() && !empty(trim($oeuvre->getMangadxId()))) {
                $oeuvresAvecMangadxId[] = $oeuvre;
            }
        }

        if (empty($oeuvresAvecMangadxId)) {
            $io->error('Aucune œuvre avec un mangadxId valide trouvée');
            return Command::FAILURE;
        }

        $io->info('Œuvres avec mangadxId trouvées : ' . count($oeuvresAvecMangadxId));

        $totalChapters = 0;
        $totalPages = 0;
        $chaptersWithId = 0;

        foreach ($oeuvresAvecMangadxId as $oeuvre) {
            $io->section("Test de : {$oeuvre->getTitre()} (MangaDx ID: {$oeuvre->getMangadxId()})");

            $chapitres = $oeuvre->getChapitres();
            $totalChapters += count($chapitres);
            
            $io->text("Chapitres: " . count($chapitres));
            
            $oeuvrePages = 0;
            $oeuvreChaptersWithId = 0;
            
            foreach ($chapitres as $chapitre) {
                if ($chapitre->getMangadxChapterId()) {
                    $oeuvreChaptersWithId++;
                    $chaptersWithId++;
                }
                
                // Tester la récupération des pages avec le nouveau système
                $pages = $this->adminPagesService->getChapitrePages($chapitre);
                $pagesCount = count($pages);
                $oeuvrePages += $pagesCount;
                
                $io->text("  • Chapitre {$chapitre->getOrdre()}: {$chapitre->getTitre()}");
                $io->text("    - MangaDx Chapter ID: {$chapitre->getMangadxChapterId()}");
                $io->text("    - Pages récupérées: {$pagesCount}");
                
                if ($pagesCount > 0) {
                    $io->text("    - Première page: " . substr($pages[0], 0, 80) . "...");
                }
            }
            
            $totalPages += $oeuvrePages;
            $io->text("  Total pages pour cette œuvre: {$oeuvrePages}");
            $io->text("  Chapitres avec mangadxChapterId: {$oeuvreChaptersWithId}/" . count($chapitres));
            
            $io->newLine();
        }

        $io->success([
            "Test terminé !",
            "Œuvres testées: " . count($oeuvresAvecMangadxId),
            "Total chapitres: {$totalChapters}",
            "Chapitres avec mangadxChapterId: {$chaptersWithId}",
            "Total pages récupérées: {$totalPages}"
        ]);

        return Command::SUCCESS;
    }
} 