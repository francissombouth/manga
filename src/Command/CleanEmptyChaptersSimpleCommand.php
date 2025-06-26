<?php

namespace App\Command;

use App\Repository\ChapitreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'app:clean-empty-chapters-simple',
    description: 'Supprime les chapitres qui ont un tableau de pages vide en base de données'
)]
class CleanEmptyChaptersSimpleCommand extends Command
{
    public function __construct(
        private ChapitreRepository $chapitreRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les chapitres qui seraient supprimés sans les supprimer')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Supprime sans demander confirmation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $isForced = $input->getOption('force');

        $io->title('🧹 Nettoyage rapide des chapitres sans pages (base de données)');

        if ($isDryRun) {
            $io->info('Mode simulation activé - aucune suppression ne sera effectuée');
        }

        // Récupérer les chapitres avec un tableau de pages vide
        $emptyChapters = $this->chapitreRepository->createQueryBuilder('c')
            ->where('JSON_LENGTH(c.pages) = 0 OR c.pages = :emptyArray')
            ->setParameter('emptyArray', '[]')
            ->getQuery()
            ->getResult();

        if (empty($emptyChapters)) {
            $io->success('🎉 Aucun chapitre avec pages vides trouvé en base de données !');
            return Command::SUCCESS;
        }

        $io->section("📋 Chapitres avec pages vides trouvés (" . count($emptyChapters) . ")");
        
        // Organiser par œuvre pour un meilleur affichage
        $chaptersGrouped = [];
        foreach ($emptyChapters as $chapitre) {
            $oeuvreTitle = $chapitre->getOeuvre()->getTitre();
            if (!isset($chaptersGrouped[$oeuvreTitle])) {
                $chaptersGrouped[$oeuvreTitle] = [];
            }
            $chaptersGrouped[$oeuvreTitle][] = $chapitre;
        }

        $tableData = [];
        foreach ($chaptersGrouped as $oeuvreTitle => $chapitres) {
            foreach ($chapitres as $chapitre) {
                $tableData[] = [
                    $oeuvreTitle,
                    "Ch. " . $chapitre->getOrdre(),
                    $chapitre->getTitre(),
                    $chapitre->getMangadxChapterId() ? 'Oui' : 'Non',
                    $chapitre->getCreatedAt()->format('d/m/Y')
                ];
            }
        }

        $io->table(
            ['Œuvre', 'Chapitre', 'Titre', 'MangaDx ID', 'Créé le'],
            $tableData
        );

        // Afficher statistiques par œuvre
        $io->section('📊 Répartition par œuvre');
        $statsData = [];
        foreach ($chaptersGrouped as $oeuvreTitle => $chapitres) {
            $statsData[] = [$oeuvreTitle, count($chapitres)];
        }
        $io->table(['Œuvre', 'Chapitres vides'], $statsData);

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
        $progressBar = $io->createProgressBar(count($emptyChapters));
        $progressBar->start();

        foreach ($emptyChapters as $chapitre) {
            try {
                $oeuvreTitle = $chapitre->getOeuvre()->getTitre();
                $this->entityManager->remove($chapitre);
                $deletedCount++;
                
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors de la suppression du chapitre {$chapitre->getOrdre()}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine();

        // Sauvegarder les changements
        try {
            $this->entityManager->flush();
            $io->success("🎉 Suppression terminée ! {$deletedCount} chapitres supprimés.");
        } catch (\Exception $e) {
            $io->error("❌ Erreur lors de la sauvegarde: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Statistiques finales
        $io->section('📊 Résumé');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Chapitres trouvés avec pages vides', count($emptyChapters)],
                ['Chapitres supprimés', $deletedCount],
                ['Œuvres affectées', count($chaptersGrouped)],
                ['Taux de succès', count($emptyChapters) > 0 ? round(($deletedCount / count($emptyChapters)) * 100, 1) . '%' : '0%']
            ]
        );

        $io->info([
            'Cette commande a supprimé les chapitres avec des pages vides en base.',
            'Pour une vérification plus approfondie avec l\'API MangaDx, utilisez:',
            'php bin/console app:clean-empty-chapters --dry-run'
        ]);

        return Command::SUCCESS;
    }
} 