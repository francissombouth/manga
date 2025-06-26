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
    name: 'app:test-admin-pages',
    description: 'Teste la récupération des pages pour l\'administration',
)]
class TestAdminPagesCommand extends Command
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

        $io->title('Test de récupération des pages pour l\'administration');

        // Récupérer toutes les œuvres avec un mangadxId non vide
        $oeuvres = $this->oeuvreRepository->findAll();
        $oeuvreAvecMangadxId = null;
        
        foreach ($oeuvres as $oeuvre) {
            if ($oeuvre->getMangadxId() && !empty(trim($oeuvre->getMangadxId()))) {
                $oeuvreAvecMangadxId = $oeuvre;
                break;
            }
        }
        
        if (!$oeuvreAvecMangadxId) {
            $io->error('Aucune œuvre avec un mangadxId valide trouvée');
            $io->text('Œuvres disponibles:');
            foreach ($oeuvres as $oeuvre) {
                $io->text("  - {$oeuvre->getTitre()} (ID: {$oeuvre->getId()}, MangaDx ID: '{$oeuvre->getMangadxId()}')");
            }
            return Command::FAILURE;
        }

        $oeuvre = $oeuvreAvecMangadxId;
        $io->info("Test avec l'œuvre: {$oeuvre->getTitre()} (ID: {$oeuvre->getId()}, MangaDx ID: '{$oeuvre->getMangadxId()}')");

        $chapitres = $oeuvre->getChapitres();
        if (empty($chapitres)) {
            $io->error('Cette œuvre n\'a pas de chapitres');
            return Command::FAILURE;
        }

        $io->info("Nombre de chapitres: " . count($chapitres));

        $totalPages = 0;
        $chapitresAvecPages = 0;

        foreach ($chapitres as $chapitre) {
            $io->section("Chapitre {$chapitre->getOrdre()}: {$chapitre->getTitre()}");
            
            $pages = $this->adminPagesService->getChapitrePages($chapitre);
            $pagesCount = count($pages);
            
            $io->text("Pages récupérées: {$pagesCount}");
            
            if ($pagesCount > 0) {
                $chapitresAvecPages++;
                $totalPages += $pagesCount;
                
                // Afficher les 3 premières pages
                $io->text("Premières pages:");
                for ($i = 0; $i < min(3, $pagesCount); $i++) {
                    $io->text("  - {$pages[$i]}");
                }
                
                if ($pagesCount > 3) {
                    $io->text("  ... et " . ($pagesCount - 3) . " autres");
                }
            } else {
                $io->text("❌ Aucune page récupérée");
            }
            
            $io->newLine();
        }

        $io->success([
            "Récupération terminée !",
            "Chapitres avec pages: {$chapitresAvecPages}/" . count($chapitres),
            "Total des pages: {$totalPages}"
        ]);

        return Command::SUCCESS;
    }
} 