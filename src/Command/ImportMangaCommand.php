<?php

namespace App\Command;

use App\Service\MangaDxImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-manga',
    description: 'Importe une œuvre depuis MangaDex avec ses genres',
)]
class ImportMangaCommand extends Command
{
    public function __construct(
        private MangaDxImportService $importService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('mangadx-id', InputArgument::REQUIRED, 'ID MangaDex de l\'œuvre à importer')
            ->setHelp('Cette commande importe une œuvre depuis MangaDex avec tous ses genres.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mangadxId = $input->getArgument('mangadx-id');

        $io->title('🎯 Import d\'une œuvre depuis MangaDex');
        $io->text("ID MangaDex: <info>{$mangadxId}</info>");

        try {
            $io->section('📥 Début de l\'importation...');
            
            $oeuvre = $this->importService->importOeuvre($mangadxId);
            
            if ($oeuvre) {
                $io->success([
                    '✅ Œuvre importée avec succès !',
                    "Titre: {$oeuvre->getTitre()}",
                    "Auteur: " . ($oeuvre->getAuteur() ? $oeuvre->getAuteur()->getNom() : 'Non défini'),
                    "Genres: " . count($oeuvre->getTags()) . " genre(s)",
                    "Chapitres: " . count($oeuvre->getChapitres()) . " chapitre(s)"
                ]);

                // Afficher les genres importés
                if ($oeuvre->getTags()->count() > 0) {
                    $io->section('🎭 Genres importés:');
                    $genres = [];
                    foreach ($oeuvre->getTags() as $tag) {
                        $genres[] = $tag->getNom();
                    }
                    $io->listing($genres);
                }

                return Command::SUCCESS;
            } else {
                $io->error('❌ Échec de l\'importation de l\'œuvre');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error([
                '❌ Erreur lors de l\'importation:',
                $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }
} 