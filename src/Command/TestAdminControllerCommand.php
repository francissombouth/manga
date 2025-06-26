<?php

namespace App\Command;

use App\Controller\AdminController;
use App\Repository\OeuvreRepository;
use App\Repository\ChapitreRepository;
use App\Repository\AuteurRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;

#[AsCommand(
    name: 'app:test-admin-controller',
    description: 'Test admin controller directly'
)]
class TestAdminControllerCommand extends Command
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository,
        private ChapitreRepository $chapitreRepository,
        private AuteurRepository $auteurRepository,
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test AdminController');

        // Créer un contrôleur admin
        $adminController = new AdminController(
            $this->oeuvreRepository,
            $this->chapitreRepository,
            $this->auteurRepository,
            $this->tagRepository,
            $this->entityManager
        );

        // Simuler une requête
        $request = new Request();
        
        // Test de la méthode directement utilisée par l'admin
        $oeuvres = $this->oeuvreRepository->findAllWithAuteurAndChapitres();
        $io->writeln("📚 Œuvres récupérées par l'admin: " . count($oeuvres));
        
        foreach ($oeuvres as $oeuvre) {
            $auteur = $oeuvre->getAuteur() ? $oeuvre->getAuteur()->getNom() : 'Aucun auteur';
            $chapitres = count($oeuvre->getChapitres());
            $io->writeln("  - {$oeuvre->getTitre()} (Auteur: $auteur, Chapitres: $chapitres)");
        }

        // Test de pagination
        $page = 1;
        $limit = 10;
        $search = '';

        if ($search) {
            $oeuvres = $this->oeuvreRepository->findByTitre($search);
            $total = count($oeuvres);
            $oeuvres = array_slice($oeuvres, ($page - 1) * $limit, $limit);
        } else {
            $oeuvres = $this->oeuvreRepository->findBy([], ['updatedAt' => 'DESC'], $limit, ($page - 1) * $limit);
            $total = $this->oeuvreRepository->count([]);
        }

        $io->writeln("📊 Total avec pagination: $total");
        $io->writeln("📄 Œuvres page 1 (limit 10): " . count($oeuvres));

        $io->success('Test terminé !');

        return Command::SUCCESS;
    }
} 