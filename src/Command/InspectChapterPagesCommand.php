<?php

namespace App\Command;

use App\Repository\ChapitreRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:inspect-chapter-pages',
    description: 'Inspecte les URLs des pages de chapitres pour identifier le problème du proxy'
)]
class InspectChapterPagesCommand extends Command
{
    public function __construct(
        private ChapitreRepository $chapitreRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Inspection des URLs de pages de chapitres');

        // Récupérer quelques chapitres avec des pages
        $chapitres = $this->chapitreRepository->findAll();
        
        $found = false;
        foreach ($chapitres as $chapitre) {
            $pages = $chapitre->getPages();
            if (!empty($pages)) {
                $found = true;
                $io->section('Chapitre: ' . $chapitre->getTitre() . ' (ID: ' . $chapitre->getId() . ')');
                $io->text('Nombre de pages: ' . count($pages));
                
                // Afficher les 3 premières URLs
                for ($i = 0; $i < min(3, count($pages)); $i++) {
                    $url = $pages[$i];
                    $io->text("Page " . ($i + 1) . ": " . $url);
                    
                    // Vérifier si l'URL contient déjà le proxy
                    if (str_contains($url, '/proxy/image')) {
                        $io->error("⚠️  Cette URL contient déjà le proxy !");
                    } else {
                        $io->success("✅ URL directe (sans proxy)");
                    }
                }
                
                break; // On s'arrête au premier chapitre avec des pages
            }
        }
        
        if (!$found) {
            $io->warning('Aucun chapitre avec des pages trouvé dans la base de données');
        }

        return Command::SUCCESS;
    }
}
