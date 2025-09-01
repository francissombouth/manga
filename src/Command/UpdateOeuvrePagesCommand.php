<?php

namespace App\Command;

use App\Repository\OeuvreRepository;
use App\Service\MangaDxImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-oeuvre-pages',
    description: 'Met à jour les pages des chapitres d\'une œuvre spécifique',
)]
class UpdateOeuvrePagesCommand extends Command
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository,
        private MangaDxImportService $importService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('oeuvre-id', null, InputOption::VALUE_REQUIRED, 'ID de l\'œuvre à traiter')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre maximum de chapitres à traiter par lot', 5)
            ->setHelp('Cette commande met à jour les pages des chapitres d\'une œuvre spécifique.
            
Exemples :
- Mettre à jour une œuvre : php bin/console app:update-oeuvre-pages --oeuvre-id=225
- Traiter 10 chapitres par lot : php bin/console app:update-oeuvre-pages --oeuvre-id=225 --limit=10')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $oeuvreId = $input->getOption('oeuvre-id');
        $limit = (int) $input->getOption('limit');

        if (!$oeuvreId) {
            $io->error('L\'option --oeuvre-id est obligatoire');
            return Command::FAILURE;
        }

        $oeuvre = $this->oeuvreRepository->find($oeuvreId);
        if (!$oeuvre) {
            $io->error(sprintf('Œuvre avec l\'ID %s non trouvée', $oeuvreId));
            return Command::FAILURE;
        }

        $io->title(sprintf('🔄 Mise à jour des pages pour l\'œuvre : %s', $oeuvre->getTitre()));

        $chapitres = $oeuvre->getChapitres();
        $chapitresWithoutPages = array_filter($chapitres->toArray(), function($chapitre) {
            return $chapitre->getMangadxChapterId() && empty($chapitre->getPages());
        });

        if (empty($chapitresWithoutPages)) {
            $io->success('Tous les chapitres ont déjà leurs pages !');
            return Command::SUCCESS;
        }

        $io->section(sprintf('📋 %d chapitre(s) à traiter sur %d au total', count($chapitresWithoutPages), count($chapitres)));

        $successCount = 0;
        $errorCount = 0;
        $processedCount = 0;

        foreach ($chapitresWithoutPages as $chapitre) {
            if ($processedCount >= $limit) {
                $io->note(sprintf('Limite de %d chapitres atteinte. Relancez la commande pour continuer.', $limit));
                break;
            }

            $io->text(sprintf('Traitement du chapitre: %s', $chapitre->getTitre()));
            
            try {
                $mangadxChapterId = $chapitre->getMangadxChapterId();
                $pages = $this->importService->getChapterPages($mangadxChapterId);
                
                if (!empty($pages)) {
                    $chapitre->setPages($pages);
                    $this->entityManager->persist($chapitre);
                    $successCount++;
                    $io->text(sprintf('  ✅ %d pages récupérées', count($pages)));
                } else {
                    $io->warning('  ⚠️ Aucune page récupérée');
                    $errorCount++;
                }

                $processedCount++;
                
                // Délai réduit pour éviter le rate limiting
                usleep(500000); // 0.5 seconde

            } catch (\Exception $e) {
                $io->error(sprintf('  ❌ Erreur: %s', $e->getMessage()));
                $errorCount++;
                $processedCount++;
            }
        }

        // Sauvegarder les modifications
        $this->entityManager->flush();

        $io->section('📊 Résultats');
        $io->table(
            ['Statut', 'Nombre'],
            [
                ['✅ Succès', $successCount],
                ['❌ Erreurs', $errorCount],
                ['📊 Traités', $processedCount],
                ['📚 Total à traiter', count($chapitresWithoutPages)]
            ]
        );

        if ($successCount > 0) {
            $io->success(sprintf('%d chapitre(s) mis à jour avec succès !', $successCount));
        }

        if (count($chapitresWithoutPages) > $processedCount) {
            $io->note(sprintf('💡 %d chapitre(s) restent à traiter. Relancez la commande pour continuer.', count($chapitresWithoutPages) - $processedCount));
        }

        return Command::SUCCESS;
    }
}
