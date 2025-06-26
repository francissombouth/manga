<?php

namespace App\Command;

use App\Service\MangaDxService;
use App\Service\AdminPagesService;
use App\Repository\OeuvreRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-chapter-retrieval',
    description: 'Test modéré de la récupération des chapitres'
)]
class TestChapterRetrievalCommand extends Command
{
    public function __construct(
        private MangaDxService $mangaDxService,
        private AdminPagesService $adminPagesService,
        private OeuvreRepository $oeuvreRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test Modéré de Récupération des Chapitres');

        // Récupérer une œuvre avec MangaDx ID
        $oeuvresAvecMangadxId = $this->oeuvreRepository->createQueryBuilder('o')
            ->where('o.mangadxId IS NOT NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($oeuvresAvecMangadxId)) {
            $io->error('Aucune œuvre avec MangaDx ID trouvée');
            return Command::FAILURE;
        }

        $oeuvre = $oeuvresAvecMangadxId[0];
        $io->section("Test avec : {$oeuvre->getTitre()} (MangaDx ID: {$oeuvre->getMangadxId()})");

        // 1. Test récupération des chapitres depuis l'API
        $io->text('1. Récupération des chapitres depuis l\'API...');
        $chaptersData = $this->mangaDxService->getAllMangaChapters($oeuvre->getMangadxId());
        $io->success("✅ {count($chaptersData)} chapitres trouvés dans l'API");

        // 2. Test des chapitres en base
        $chapitres = $oeuvre->getChapitres();
        $io->text("2. Chapitres en base de données : " . count($chapitres));

        // 3. Test de récupération des pages pour quelques chapitres seulement
        $io->section('3. Test de récupération des pages (3 premiers chapitres)');
        
        $testedChapters = 0;
        $successfulChapters = 0;
        $totalPages = 0;

        foreach ($chapitres as $chapitre) {
            if ($testedChapters >= 3) {
                break;
            }

            $testedChapters++;
            $io->text("Test chapitre {$chapitre->getOrdre()}: {$chapitre->getTitre()}");
            
            if ($chapitre->getMangadxChapterId()) {
                $io->text("  - MangaDx Chapter ID: {$chapitre->getMangadxChapterId()}");
                
                $pages = $this->adminPagesService->getChapitrePages($chapitre);
                $pagesCount = count($pages);
                $totalPages += $pagesCount;
                
                if ($pagesCount > 0) {
                    $successfulChapters++;
                    $io->text("  - ✅ Pages récupérées: {$pagesCount}");
                    $io->text("  - Première page: " . substr($pages[0], 0, 60) . "...");
                } else {
                    $io->text("  - ❌ Aucune page récupérée");
                }
            } else {
                $io->text("  - ❌ Pas de MangaDx Chapter ID");
            }
            
            // Pause entre les chapitres pour éviter le rate limiting
            $io->text("  - Pause de 2 secondes...");
            sleep(2);
            $io->newLine();
        }

        // 4. Résumé
        $io->section('📊 Résumé du test');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Chapitres testés', $testedChapters],
                ['Chapitres avec pages', $successfulChapters],
                ['Total pages récupérées', $totalPages],
                ['Taux de succès', $testedChapters > 0 ? round(($successfulChapters / $testedChapters) * 100, 1) . '%' : '0%']
            ]
        );

        if ($successfulChapters > 0) {
            $io->success('🎉 La récupération des chapitres fonctionne !');
            $io->info([
                'Conseils pour l\'utilisation :',
                '• Évitez de tester trop de chapitres d\'affilée',
                '• L\'API MangaDx a des limites de rate (429 Too Many Requests)',
                '• Certains chapitres peuvent être temporairement indisponibles (404)',
                '• Le système gère automatiquement les retry avec backoff'
            ]);
        } else {
            $io->warning('Aucun chapitre n\'a pu être récupéré');
        }

        return Command::SUCCESS;
    }
} 