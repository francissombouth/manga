<?php

namespace App\Command;

use App\Repository\OeuvreRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-exact-admin',
    description: 'Test exact same code as AdminController'
)]
class TestExactAdminCommand extends Command
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🎯 Test Code Exact AdminController');

        // Code EXACT de l'AdminController
        $page = 1;
        $limit = 10;
        $search = '';

        if ($search) {
            $oeuvres = $this->oeuvreRepository->findByTitre($search);
            $total = count($oeuvres);
            $oeuvres = array_slice($oeuvres, ($page - 1) * $limit, $limit);
        } else {
            // CODE EXACT de l'AdminController :
            $oeuvres = $this->oeuvreRepository->findBy([], ['updatedAt' => 'DESC'], $limit, ($page - 1) * $limit);
            $total = $this->oeuvreRepository->count([]);
        }

        $totalPages = ceil($total / $limit);

        $io->writeln("🔍 Paramètres :");
        $io->writeln("  - Page: $page");
        $io->writeln("  - Limit: $limit");
        $io->writeln("  - Search: '$search'");
        $io->writeln("  - Offset: " . (($page - 1) * $limit));

        $io->writeln("📊 Résultats :");
        $io->writeln("  - Total en BDD: $total");
        $io->writeln("  - Œuvres récupérées: " . count($oeuvres));
        $io->writeln("  - Total pages: $totalPages");

        $io->writeln("📚 Œuvres trouvées :");
        foreach ($oeuvres as $i => $oeuvre) {
            $io->writeln("  " . ($i + 1) . ". {$oeuvre->getTitre()} (ID: {$oeuvre->getId()}, Updated: {$oeuvre->getUpdatedAt()->format('Y-m-d H:i:s')})");
        }

        $io->success('Test terminé !');

        return Command::SUCCESS;
    }
} 