<?php

namespace App\Command;

use App\Service\MangaDxImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-import-with-pages',
    description: 'Test import d\'une œuvre avec pages depuis MangaDx'
)]
class TestImportWithPagesCommand extends Command
{
    public function __construct(
        private MangaDxImportService $importService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('📥 Test Import avec Pages');

        // ID de Solo Leveling
        $mangadxId = '32d76d19-8a05-4db0-9fc2-e0b0648fe9d0';
        
        $io->writeln("🔍 Import de l'œuvre avec ID: $mangadxId");

        try {
            // Supprimer l'œuvre si elle existe déjà pour tester l'import complet
            $existingOeuvre = $this->importService->importOrUpdateOeuvre($mangadxId);
            
            if ($existingOeuvre) {
                $io->success("✅ Œuvre importée avec succès !");
                $io->writeln("📚 Titre: " . $existingOeuvre->getTitre());
                $io->writeln("👤 Auteur: " . ($existingOeuvre->getAuteur() ? $existingOeuvre->getAuteur()->getNom() : 'Aucun'));
                $io->writeln("📖 Chapitres: " . count($existingOeuvre->getChapitres()));
                
                // Vérifier les pages du premier chapitre
                $chapitres = $existingOeuvre->getChapitres();
                if (!empty($chapitres)) {
                    $premierChapitre = $chapitres[0];
                    $pages = $premierChapitre->getPages();
                    $io->writeln("🖼️ Pages du premier chapitre: " . count($pages));
                    
                    if (!empty($pages)) {
                        $io->writeln("✅ Pages récupérées avec succès !");
                        $io->writeln("   Première page: " . substr($pages[0], 0, 80) . "...");
                    } else {
                        $io->warning("⚠️ Aucune page récupérée pour le premier chapitre");
                    }
                }
            } else {
                $io->error("❌ Échec de l'import");
            }

        } catch (\Exception $e) {
            $io->error("❌ Erreur: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
} 