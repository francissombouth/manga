<?php

namespace App\Command;

use App\Repository\ChapitreRepository;
use App\Service\MangaDxImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-chapter-pages',
    description: 'Met à jour les pages des chapitres qui n\'en ont pas',
)]
class UpdateChapterPagesCommand extends Command
{
    public function __construct(
        private ChapitreRepository $chapitreRepository,
        private MangaDxImportService $importService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('oeuvre-id', null, InputOption::VALUE_OPTIONAL, 'ID de l\'œuvre spécifique à traiter')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre maximum de chapitres à traiter', 10)
            ->setHelp('Cette commande met à jour les pages des chapitres qui n\'en ont pas encore.
            
Exemples :
- Mettre à jour 10 chapitres : php bin/console app:update-chapter-pages
- Mettre à jour une œuvre spécifique : php bin/console app:update-chapter-pages --oeuvre-id=123
- Mettre à jour 50 chapitres : php bin/console app:update-chapter-pages --limit=50')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $oeuvreId = $input->getOption('oeuvre-id');
        $limit = (int) $input->getOption('limit');

        $io->title('🔄 Mise à jour des pages de chapitres');

        // Récupérer les chapitres sans pages mais avec un ID MangaDx
        $criteria = [];
        if ($oeuvreId) {
            $criteria['oeuvre'] = $oeuvreId;
        }

        $chapitres = $this->chapitreRepository->findBy($criteria, ['id' => 'ASC'], $limit);
        
        // Filtrer les chapitres qui ont un ID MangaDx mais pas de pages
        $chapitresToUpdate = array_filter($chapitres, function($chapitre) {
            return $chapitre->getMangadxChapterId() && empty($chapitre->getPages());
        });
        
        if (empty($chapitresToUpdate)) {
            $io->success('Aucun chapitre à mettre à jour trouvé.');
            return Command::SUCCESS;
        }

        $io->section('📋 Chapitres à traiter');
        $io->table(
            ['ID', 'Titre', 'Œuvre', 'Ordre', 'MangaDx ID', 'Pages actuelles'],
            array_map(function($chapitre) {
                return [
                    $chapitre->getId(),
                    $chapitre->getTitre(),
                    $chapitre->getOeuvre()->getTitre(),
                    $chapitre->getOrdre(),
                    $chapitre->getMangadxChapterId(),
                    count($chapitre->getPages())
                ];
            }, $chapitresToUpdate)
        );

        if (!$io->confirm(sprintf('Voulez-vous mettre à jour %d chapitre(s) ?', count($chapitresToUpdate)))) {
            $io->note('Opération annulée');
            return Command::SUCCESS;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($chapitresToUpdate as $chapitre) {
            $io->text(sprintf('Traitement du chapitre: %s', $chapitre->getTitre()));
            
            try {
                $mangadxChapterId = $chapitre->getMangadxChapterId();
                
                // Récupérer les pages
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

                // Délai pour éviter le rate limiting
                sleep(1);

            } catch (\Exception $e) {
                $io->error(sprintf('  ❌ Erreur: %s', $e->getMessage()));
                $errorCount++;
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
                ['📊 Total', count($chapitresToUpdate)]
            ]
        );

        if ($successCount > 0) {
            $io->success(sprintf('%d chapitre(s) mis à jour avec succès !', $successCount));
        }

        if ($errorCount > 0) {
            $io->warning(sprintf('%d chapitre(s) ont rencontré des erreurs.', $errorCount));
        }

        return Command::SUCCESS;
    }
}
