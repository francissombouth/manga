<?php

namespace App\Command;

use App\Entity\Oeuvre;
use App\Service\MangaDxImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-genres',
    description: 'Vérifie et corrige les genres des œuvres',
)]
class CheckGenresCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MangaDxImportService $importService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Corriger automatiquement les œuvres sans genres')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limiter le nombre d\'œuvres à traiter', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fix = $input->getOption('fix');
        $limit = (int) $input->getOption('limit');

        $io->title('🔍 Vérification des Genres des Œuvres');

        try {
            $oeuvres = $this->entityManager->getRepository(Oeuvre::class)
                ->createQueryBuilder('o')
                ->orderBy('o.createdAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            if (empty($oeuvres)) {
                $io->warning('Aucune œuvre trouvée');
                return Command::SUCCESS;
            }

            $tableData = [];
            $oeuvresWithoutGenres = [];
            
            foreach ($oeuvres as $oeuvre) {
                $genres = [];
                foreach ($oeuvre->getTags() as $tag) {
                    $genres[] = $tag->getNom();
                }
                
                $tableData[] = [
                    $oeuvre->getTitre(),
                    count($genres),
                    implode(', ', array_slice($genres, 0, 3)) . (count($genres) > 3 ? '...' : ''),
                    $oeuvre->getMangadxId() ? '✅' : '❌'
                ];
                
                if (count($genres) === 0 && $oeuvre->getMangadxId()) {
                    $oeuvresWithoutGenres[] = $oeuvre;
                }
            }

            $io->table(
                ['Œuvre', 'Nb Genres', 'Genres (3 premiers)', 'MangaDx ID'],
                $tableData
            );

            // Statistiques
            $totalOeuvres = count($oeuvres);
            $oeuvresAvecGenres = 0;
            $oeuvresAvecMangadxId = 0;
            $totalGenres = 0;

            foreach ($oeuvres as $oeuvre) {
                $nbGenres = count($oeuvre->getTags());
                if ($nbGenres > 0) {
                    $oeuvresAvecGenres++;
                }
                if ($oeuvre->getMangadxId()) {
                    $oeuvresAvecMangadxId++;
                }
                $totalGenres += $nbGenres;
            }

            $io->section('📊 Statistiques');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Œuvres avec genres', $oeuvresAvecGenres . '/' . $totalOeuvres],
                    ['Œuvres avec ID MangaDx', $oeuvresAvecMangadxId . '/' . $totalOeuvres],
                    ['Œuvres sans genres mais avec ID MangaDx', count($oeuvresWithoutGenres)],
                    ['Moyenne genres/œuvre', $totalOeuvres > 0 ? round($totalGenres / $totalOeuvres, 1) : 0],
                    ['Total associations', $totalGenres]
                ]
            );

            // Correction automatique si demandée
            if ($fix && !empty($oeuvresWithoutGenres)) {
                $io->section('🔧 Correction automatique des genres');
                
                $io->confirm('Voulez-vous corriger automatiquement les œuvres sans genres ?', false);
                
                $progressBar = $io->createProgressBar(count($oeuvresWithoutGenres));
                $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | 📚 %message%');
                
                $fixed = 0;
                $errors = 0;
                
                foreach ($oeuvresWithoutGenres as $oeuvre) {
                    $progressBar->setMessage($oeuvre->getTitre());
                    
                    try {
                        // Mettre à jour l'œuvre pour récupérer les genres
                        $updatedOeuvre = $this->importService->updateOeuvre($oeuvre->getMangadxId());
                        
                        if ($updatedOeuvre && count($updatedOeuvre->getTags()) > 0) {
                            $fixed++;
                            $io->newLine();
                            $io->text("✅ {$oeuvre->getTitre()} - " . count($updatedOeuvre->getTags()) . " genres ajoutés");
                        }
                    } catch (\Exception $e) {
                        $errors++;
                        $io->newLine();
                        $io->text("❌ {$oeuvre->getTitre()} - Erreur: " . $e->getMessage());
                    }
                    
                    $progressBar->advance();
                }
                
                $progressBar->finish();
                $io->newLine(2);
                
                $io->success([
                    "Correction terminée !",
                    "Œuvres corrigées: {$fixed}",
                    "Erreurs: {$errors}"
                ]);
            } elseif ($fix && empty($oeuvresWithoutGenres)) {
                $io->info('Aucune œuvre à corriger - toutes les œuvres ont déjà des genres ou n\'ont pas d\'ID MangaDx');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la vérification: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 