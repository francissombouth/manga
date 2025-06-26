<?php

namespace App\Command;

use App\Service\MangaDxService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-mangadx-api',
    description: 'Test de l\'API MangaDx réelle'
)]
class TestMangaDxApiCommand extends Command
{
    public function __construct(
        private MangaDxService $mangaDxService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🌐 Test API MangaDx Réelle');

        try {
            $io->writeln("📡 Test de connexion à l'API MangaDx...");
            
            // Test 1: Récupérer des mangas populaires
            $io->writeln("🔍 Récupération de 5 mangas populaires...");
            $popularMangas = $this->mangaDxService->getPopularManga(5, 0);
            
            if (empty($popularMangas)) {
                $io->error("❌ L'API MangaDx ne retourne aucun résultat");
                return Command::FAILURE;
            }

            $io->writeln("✅ " . count($popularMangas) . " mangas récupérés avec succès !");
            
            // Afficher les détails du premier manga
            $firstManga = $popularMangas[0];
            $io->writeln("\n📚 Premier manga récupéré :");
            $io->writeln("  - ID: " . ($firstManga['id'] ?? 'N/A'));
            $io->writeln("  - Titre: " . ($firstManga['attributes']['title']['en'] ?? 'N/A'));
            $io->writeln("  - Type: " . ($firstManga['type'] ?? 'N/A'));

            // Test 2: Récupérer les chapitres d'un manga
            if (isset($firstManga['id'])) {
                $io->writeln("\n📖 Test récupération des chapitres pour l'ID " . $firstManga['id'] . "...");
                $chapters = $this->mangaDxService->getAllMangaChapters($firstManga['id']);
                
                if (empty($chapters)) {
                    $io->warning("⚠️ Aucun chapitre trouvé pour ce manga");
                } else {
                    $io->writeln("✅ " . count($chapters) . " chapitres récupérés !");
                    
                    // Afficher le premier chapitre
                    $firstChapter = $chapters[0];
                    $io->writeln("\n📄 Premier chapitre :");
                    $io->writeln("  - ID: " . ($firstChapter['id'] ?? 'N/A'));
                    $io->writeln("  - Titre: " . ($firstChapter['attributes']['title'] ?? 'N/A'));
                    $io->writeln("  - Chapitre: " . ($firstChapter['attributes']['chapter'] ?? 'N/A'));

                    // Test 3: Récupérer les pages d'un chapitre
                    if (isset($firstChapter['id'])) {
                        $io->writeln("\n🖼️ Test récupération des pages pour le chapitre " . $firstChapter['id'] . "...");
                        $pages = $this->mangaDxService->getChapterPages($firstChapter['id']);
                        
                        if (empty($pages)) {
                            $io->warning("⚠️ Aucune page trouvée pour ce chapitre");
                        } else {
                            $io->writeln("✅ " . count($pages) . " pages récupérées !");
                            $io->writeln("  - Première page: " . ($pages[0] ?? 'N/A'));
                        }
                    }
                }
            }

            $io->success("🎉 L'API MangaDx fonctionne correctement !");

        } catch (\Exception $e) {
            $io->error("❌ Erreur API MangaDx : " . $e->getMessage());
            $io->writeln("\n💡 Solutions possibles :");
            $io->writeln("  1. Vérifier la connexion internet");
            $io->writeln("  2. L'API MangaDx peut être temporairement indisponible");
            $io->writeln("  3. Utiliser des données simulées en attendant");
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
} 