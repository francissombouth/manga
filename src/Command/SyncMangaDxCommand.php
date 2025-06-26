<?php

namespace App\Command;

use App\Service\MangaDxImportService;
use App\Repository\OeuvreRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'app:sync-mangadx',
    description: 'Synchronise les œuvres depuis MangaDx API',
)]
class SyncMangaDxCommand extends Command
{
    public function __construct(
        private MangaDxImportService $importService,
        private OeuvreRepository $oeuvreRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('mangadx-id', InputArgument::OPTIONAL, 'ID MangaDx spécifique à importer/synchroniser')
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Mettre à jour toutes les œuvres existantes')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forcer la mise à jour même si récente')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mangadxId = $input->getArgument('mangadx-id');
        $updateAll = $input->getOption('update-all');
        $force = $input->getOption('force');

        if ($mangadxId) {
            // Importer/mettre à jour une œuvre spécifique
            return $this->syncSingleOeuvre($io, $mangadxId);
        }

        if ($updateAll) {
            // Mettre à jour toutes les œuvres existantes
            return $this->syncAllOeuvres($io, $force);
        }

        $io->error('Vous devez spécifier soit un ID MangaDx, soit utiliser --update-all');
        return Command::FAILURE;
    }

    private function syncSingleOeuvre(SymfonyStyle $io, string $mangadxId): int
    {
        $io->title('Synchronisation d\'une œuvre MangaDx');
        $io->text("ID MangaDx: $mangadxId");

        try {
            $oeuvre = $this->importService->importOrUpdateOeuvre($mangadxId);
            
            if ($oeuvre) {
                $io->success(sprintf(
                    '✅ Œuvre "%s" synchronisée avec succès (ID: %d)',
                    $oeuvre->getTitre(),
                    $oeuvre->getId()
                ));
                
                $io->table(
                    ['Propriété', 'Valeur'],
                    [
                        ['Titre', $oeuvre->getTitre()],
                        ['Auteur', $oeuvre->getAuteur() ? $oeuvre->getAuteur()->getNom() : 'Non défini'],
                        ['Type', $oeuvre->getType()],
                        ['Chapitres', count($oeuvre->getChapitres())],
                        ['Tags', count($oeuvre->getTags())],
                        ['Couverture', $oeuvre->getCouverture() ? '✅' : '❌'],
                    ]
                );
                
                return Command::SUCCESS;
            }
        } catch (\Exception $e) {
            $io->error('Erreur lors de la synchronisation: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::FAILURE;
    }

    private function syncAllOeuvres(SymfonyStyle $io, bool $force): int
    {
        $io->title('Synchronisation de toutes les œuvres MangaDx');

        // Récupérer toutes les œuvres avec un ID MangaDx
        $oeuvres = $this->oeuvreRepository->createQueryBuilder('o')
            ->where('o.mangadxId IS NOT NULL')
            ->getQuery()
            ->getResult();

        if (empty($oeuvres)) {
            $io->warning('Aucune œuvre avec un ID MangaDx trouvée');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Trouvé %d œuvre(s) à synchroniser', count($oeuvres)));

        $progressBar = $io->createProgressBar(count($oeuvres));
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($oeuvres as $oeuvre) {
            $progressBar->setMessage($oeuvre->getTitre());
            
            try {
                $this->importService->updateOeuvre($oeuvre->getMangadxId());
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = sprintf('"%s": %s', $oeuvre->getTitre(), $e->getMessage());
            }

            $progressBar->advance();
            
            // Pause pour éviter de surcharger l'API
            usleep(500000); // 0.5 seconde
        }

        $progressBar->finish();
        $io->newLine(2);

        // Résumé
        $io->section('Résumé de la synchronisation');
        $io->table(
            ['Statut', 'Nombre'],
            [
                ['✅ Succès', $successCount],
                ['❌ Erreurs', $errorCount],
                ['📊 Total', count($oeuvres)]
            ]
        );

        if (!empty($errors)) {
            $io->section('Erreurs détaillées');
            foreach ($errors as $error) {
                $io->text("❌ $error");
            }
        }

        return $errorCount === 0 ? Command::SUCCESS : Command::FAILURE;
    }
} 