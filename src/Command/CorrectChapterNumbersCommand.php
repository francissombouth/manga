<?php

namespace App\Command;

use App\Service\MangaDxImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:correct-chapter-numbers',
    description: 'Corrige les numéros de chapitres existants pour utiliser les vrais numéros de MangaDx',
)]
class CorrectChapterNumbersCommand extends Command
{
    public function __construct(
        private MangaDxImportService $mangaDxImportService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔧 Correction des numéros de chapitres');
        $io->text('Cette commande corrige les numéros de chapitres existants pour utiliser les vrais numéros de MangaDx.');

        if (!$io->confirm('Voulez-vous continuer ? Cette opération peut prendre du temps.', false)) {
            $io->warning('Opération annulée.');
            return Command::SUCCESS;
        }

        try {
            $io->section('Début de la correction...');
            
            $this->mangaDxImportService->correctExistingChapterNumbers();
            
            $io->success('✅ Correction des numéros de chapitres terminée avec succès !');
            
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de la correction : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
} 