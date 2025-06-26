<?php

namespace App\Command;

use App\Repository\OeuvreRepository;
use App\Repository\ChapitreRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-admin-final',
    description: 'Test final de l\'administration avec toutes les œuvres'
)]
class TestAdminFinalCommand extends Command
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository,
        private ChapitreRepository $chapitreRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🎯 Test Final Administration');

        // Statistiques générales
        $totalOeuvres = $this->oeuvreRepository->count([]);
        $totalChapitres = $this->chapitreRepository->count([]);

        $io->writeln("📊 Statistiques générales :");
        $io->writeln("  - Total œuvres : $totalOeuvres");
        $io->writeln("  - Total chapitres : $totalChapitres");
        $io->writeln("  - Moyenne chapitres/œuvre : " . round($totalChapitres / $totalOeuvres, 1));

        // Test de la pagination de l'admin (page 1, limit 10)
        $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $oeuvres = $this->oeuvreRepository->findBy([], ['updatedAt' => 'DESC'], $limit, $offset);
        $totalPages = ceil($totalOeuvres / $limit);

        $io->writeln("\n📄 Test pagination administration :");
        $io->writeln("  - Page : $page");
        $io->writeln("  - Limit : $limit");
        $io->writeln("  - Total pages : $totalPages");
        $io->writeln("  - Œuvres page 1 : " . count($oeuvres));

        $io->writeln("\n📚 Œuvres page 1 (les 10 premières) :");
        foreach ($oeuvres as $i => $oeuvre) {
            $chapitresCount = count($oeuvre->getChapitres());
            $auteur = $oeuvre->getAuteur() ? $oeuvre->getAuteur()->getNom() : 'Aucun';
            $io->writeln("  " . ($i + 1) . ". {$oeuvre->getTitre()} (Auteur: $auteur, Chapitres: $chapitresCount)");
        }

        // Test avec recherche
        $io->writeln("\n🔍 Test recherche 'Mystery' :");
        $oeuvresMystery = $this->oeuvreRepository->findByTitre('Mystery');
        $io->writeln("  - Résultats trouvés : " . count($oeuvresMystery));
        foreach ($oeuvresMystery as $oeuvre) {
            $io->writeln("    - {$oeuvre->getTitre()}");
        }

        $io->success('✅ Administration prête ! Vous devriez maintenant voir ' . $totalOeuvres . ' œuvres dans l\'interface d\'administration.');

        return Command::SUCCESS;
    }
} 