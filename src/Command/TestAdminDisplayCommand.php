<?php

namespace App\Command;

use App\Repository\OeuvreRepository;
use App\Service\AdminPagesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-admin-display',
    description: 'Teste l\'affichage des pages dans l\'administration',
)]
class TestAdminDisplayCommand extends Command
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository,
        private AdminPagesService $adminPagesService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre maximum d\'œuvres à tester', 10)
            ->addOption('with-pages', 'p', InputOption::VALUE_NONE, 'Tester seulement les œuvres qui ont des chapitres avec mangadxChapterId')
            ->setHelp('Cette commande teste l\'affichage des pages dans l\'administration avec les œuvres importées.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = (int) $input->getOption('limit');
        $withPages = $input->getOption('with-pages');

        $io->title("Test de l'affichage dans l'administration");
        $io->text("Limite: {$limit} œuvres");
        $io->text("Avec pages uniquement: " . ($withPages ? 'Oui' : 'Non'));

        // Récupérer les œuvres
        $oeuvres = $this->oeuvreRepository->findAll();
        
        if ($withPages) {
            $oeuvresWithPages = [];
            foreach ($oeuvres as $oeuvre) {
                $hasChaptersWithId = false;
                foreach ($oeuvre->getChapitres() as $chapitre) {
                    if ($chapitre->getMangadxChapterId()) {
                        $hasChaptersWithId = true;
                        break;
                    }
                }
                if ($hasChaptersWithId) {
                    $oeuvresWithPages[] = $oeuvre;
                }
            }
            $oeuvres = $oeuvresWithPages;
        }

        $oeuvres = array_slice($oeuvres, 0, $limit);

        if (empty($oeuvres)) {
            $io->error('Aucune œuvre trouvée');
            return Command::FAILURE;
        }

        $io->text("✅ " . count($oeuvres) . " œuvres à tester");

        $totalChapters = 0;
        $totalChaptersWithId = 0;
        $totalPages = 0;
        $chaptersWithPages = 0;

        foreach ($oeuvres as $index => $oeuvre) {
            $io->section("(" . ($index + 1) . "/" . count($oeuvres) . ") Test de : {$oeuvre->getTitre()}");

            $chapitres = $oeuvre->getChapitres();
            $chaptersCount = count($chapitres);
            $totalChapters += $chaptersCount;
            
            $io->text("Chapitres: {$chaptersCount}");
            
            $oeuvreChaptersWithId = 0;
            $oeuvrePages = 0;
            $oeuvreChaptersWithPages = 0;
            
            // Tester les 3 premiers chapitres pour éviter le rate limiting
            $testChapters = array_slice($chapitres->toArray(), 0, 3);
            
            foreach ($testChapters as $chapitre) {
                if ($chapitre->getMangadxChapterId()) {
                    $oeuvreChaptersWithId++;
                    $totalChaptersWithId++;
                    
                    $io->text("  • Chapitre {$chapitre->getOrdre()}: {$chapitre->getTitre()}");
                    $io->text("    - MangaDx Chapter ID: {$chapitre->getMangadxChapterId()}");
                    
                    // Tester la récupération des pages
                    $pages = $this->adminPagesService->getChapitrePages($chapitre);
                    $pagesCount = count($pages);
                    $oeuvrePages += $pagesCount;
                    $totalPages += $pagesCount;
                    
                    $io->text("    - Pages récupérées: {$pagesCount}");
                    
                    if ($pagesCount > 0) {
                        $oeuvreChaptersWithPages++;
                        $chaptersWithPages++;
                        $io->text("    - Première page: " . substr($pages[0], 0, 60) . "...");
                    }
                    
                    // Pause pour éviter le rate limiting
                    sleep(1);
                }
            }
            
            $io->text("  Total pages pour cette œuvre: {$oeuvrePages}");
            $io->text("  Chapitres avec mangadxChapterId: {$oeuvreChaptersWithId}/{$chaptersCount}");
            $io->text("  Chapitres avec pages: {$oeuvreChaptersWithPages}");
        }

        $io->section("Résumé");
        $io->success([
            "Test terminé !",
            "Œuvres testées: " . count($oeuvres),
            "Total chapitres: {$totalChapters}",
            "Chapitres avec mangadxChapterId: {$totalChaptersWithId}",
            "Chapitres avec pages: {$chaptersWithPages}",
            "Total pages récupérées: {$totalPages}",
            "Taux de succès: " . ($totalChaptersWithId > 0 ? round(($chaptersWithPages / $totalChaptersWithId) * 100, 1) : 0) . "%"
        ]);

        if ($chaptersWithPages > 0) {
            $io->text("🎉 L'administration peut maintenant afficher les pages comme le catalogue !");
            $io->text("💡 Les pages sont récupérées dynamiquement depuis l'API MangaDex.");
        }

        return Command::SUCCESS;
    }
} 