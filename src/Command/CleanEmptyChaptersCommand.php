<?php

namespace App\Command;

use App\Entity\Chapitre;
use App\Repository\ChapitreRepository;
use App\Service\AdminPagesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'app:clean-empty-chapters',
    description: 'Supprime les chapitres qui n\'ont pas de pages disponibles'
)]
class CleanEmptyChaptersCommand extends Command
{
    public function __construct(
        private ChapitreRepository $chapitreRepository,
        private AdminPagesService $adminPagesService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les chapitres qui seraient supprimés sans les supprimer')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Supprime sans demander confirmation')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limite le nombre de chapitres à tester', 50)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $isForced = $input->getOption('force');
        $limit = (int) $input->getOption('limit');

        $io->title('🧹 Nettoyage des chapitres sans pages');

        if ($isDryRun) {
            $io->info('Mode simulation activé - aucune suppression ne sera effectuée');
        }

        // Récupérer tous les chapitres avec un MangaDx Chapter ID
        $chapitres = $this->chapitreRepository->createQueryBuilder('c')
            ->where('c.mangadxChapterId IS NOT NULL')
            ->orderBy('c.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if (empty($chapitres)) {
            $io->warning('Aucun chapitre avec MangaDx Chapter ID trouvé');
            return Command::SUCCESS;
        }

        $io->text("📊 Analyse de " . count($chapitres) . " chapitres...");
        $io->newLine();

        $emptyChapters = [];
        $processedCount = 0;
        $progressBar = $io->createProgressBar(count($chapitres));
        $progressBar->start();

        foreach ($chapitres as $chapitre) {
            $processedCount++;
            
            // Tester la récupération des pages
            $pages = $this->adminPagesService->getChapitrePages($chapitre);
            $pagesCount = count($pages);

            if ($pagesCount === 0) {
                $emptyChapters[] = [
                    'chapitre' => $chapitre,
                    'oeuvre' => $chapitre->getOeuvre()->getTitre(),
                    'ordre' => $chapitre->getOrdre(),
                    'titre' => $chapitre->getTitre(),
                    'mangadxId' => $chapitre->getMangadxChapterId()
                ];
            }

            $progressBar->advance();
            
            // Pause pour éviter le rate limiting
            if ($processedCount % 5 === 0) {
                usleep(1000000); // 1 seconde de pause tous les 5 chapitres
            }
        }

        $progressBar->finish();
        $io->newLine(2);

        if (empty($emptyChapters)) {
            $io->success('🎉 Tous les chapitres testés ont des pages disponibles !');
            return Command::SUCCESS;
        }

        // Afficher les chapitres sans pages
        $io->section("📋 Chapitres sans pages trouvés (" . count($emptyChapters) . ")");
        
        $tableData = [];
        foreach ($emptyChapters as $data) {
            $tableData[] = [
                $data['oeuvre'],
                "Ch. " . $data['ordre'],
                $data['titre'],
                substr($data['mangadxId'], 0, 8) . '...'
            ];
        }

        $io->table(
            ['Œuvre', 'Chapitre', 'Titre', 'MangaDx ID'],
            $tableData
        );

        if ($isDryRun) {
            $io->info('Mode simulation - aucune suppression effectuée');
            $io->text("Ces " . count($emptyChapters) . " chapitres seraient supprimés avec l'option --force");
            return Command::SUCCESS;
        }

        // Demander confirmation
        if (!$isForced) {
            $question = new ConfirmationQuestion(
                "Voulez-vous vraiment supprimer ces " . count($emptyChapters) . " chapitres sans pages ? (y/N) ",
                false
            );

            if (!$io->askQuestion($question)) {
                $io->text('Suppression annulée');
                return Command::SUCCESS;
            }
        }

        // Supprimer les chapitres
        $io->section('🗑️ Suppression en cours...');
        $deletedCount = 0;

        foreach ($emptyChapters as $data) {
            $chapitre = $data['chapitre'];
            
            try {
                $this->entityManager->remove($chapitre);
                $deletedCount++;
                
                $io->text("✅ Supprimé: {$data['oeuvre']} - Ch. {$data['ordre']} - {$data['titre']}");
                
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors de la suppression du chapitre {$data['ordre']}: " . $e->getMessage());
            }
        }

        // Sauvegarder les changements
        try {
            $this->entityManager->flush();
            $io->success("🎉 Suppression terminée ! {$deletedCount} chapitres supprimés.");
        } catch (\Exception $e) {
            $io->error("❌ Erreur lors de la sauvegarde: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Statistiques finales
        $io->section('📊 Statistiques');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Chapitres analysés', count($chapitres)],
                ['Chapitres sans pages', count($emptyChapters)],
                ['Chapitres supprimés', $deletedCount],
                ['Pourcentage nettoyé', count($chapitres) > 0 ? round(($deletedCount / count($chapitres)) * 100, 1) . '%' : '0%']
            ]
        );

        $io->info([
            'Conseils post-nettoyage:',
            '• Vous pouvez relancer cette commande périodiquement',
            '• Utilisez --dry-run pour tester avant suppression',
            '• Ajustez --limit pour traiter plus ou moins de chapitres',
            '• Certains chapitres peuvent redevenir disponibles plus tard'
        ]);

        return Command::SUCCESS;
    }
} 