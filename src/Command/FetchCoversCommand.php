<?php

namespace App\Command;

use App\Entity\Oeuvre;
use App\Service\CoverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'app:fetch-covers',
    description: 'Récupère automatiquement les images de couverture des œuvres qui n\'en ont pas'
)]
class FetchCoversCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CoverService $coverService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force le téléchargement même si une couverture existe déjà')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limite le nombre d\'œuvres à traiter', 10)
            ->setHelp('Cette commande recherche et télécharge automatiquement les images de couverture des œuvres en utilisant l\'API Google Books.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $limit = (int) $input->getOption('limit');

        $io->title('Récupération des images de couverture');

        // Récupérer les œuvres sans couverture (ou toutes si --force)
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Oeuvre::class, 'o')
            ->leftJoin('o.auteur', 'a');

        if (!$force) {
            $queryBuilder->where('o.couverture IS NULL OR o.couverture = :empty')
                ->setParameter('empty', '');
        }

        $oeuvres = $queryBuilder
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if (empty($oeuvres)) {
            $io->success('Aucune œuvre à traiter !');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Traitement de %d œuvre(s)...', count($oeuvres)));

        $progressBar = new ProgressBar($output, count($oeuvres));
        $progressBar->start();

        $successCount = 0;
        $failureCount = 0;

        foreach ($oeuvres as $oeuvre) {
            $progressBar->advance();
            
            $titre = $oeuvre->getTitre();
            $auteur = $oeuvre->getAuteur() ? $oeuvre->getAuteur()->getNom() : null;
            
            $io->writeln(''); // Nouvelle ligne pour les logs
            $io->text(sprintf('Recherche pour: "%s" par %s', $titre, $auteur ?? 'Auteur inconnu'));

            try {
                $coverPath = $this->coverService->searchAndDownloadCover($titre, $auteur);
                
                if ($coverPath) {
                    // Supprimer l'ancienne couverture si elle existe
                    if ($force && $oeuvre->getCouverture()) {
                        $this->coverService->deleteCover($oeuvre->getCouverture());
                    }
                    
                    $oeuvre->setCouverture($coverPath);
                    $this->entityManager->persist($oeuvre);
                    
                    $io->text(sprintf('✅ Couverture trouvée et téléchargée: %s', $coverPath));
                    $successCount++;
                } else {
                    $io->text('❌ Aucune couverture trouvée');
                    $failureCount++;
                }
                
                // Persister les changements par batch de 5
                if (($successCount + $failureCount) % 5 === 0) {
                    $this->entityManager->flush();
                }
                
                // Délai pour éviter de surcharger l'API
                sleep(1);
                
            } catch (\Exception $e) {
                $io->error(sprintf('Erreur pour "%s": %s', $titre, $e->getMessage()));
                $failureCount++;
            }
        }

        $progressBar->finish();
        $io->writeln(''); // Nouvelle ligne après la barre de progression

        // Persister les derniers changements
        $this->entityManager->flush();

        // Résumé
        $io->section('Résumé');
        $io->table(
            ['Statut', 'Nombre'],
            [
                ['Succès', $successCount],
                ['Échecs', $failureCount],
                ['Total', count($oeuvres)]
            ]
        );

        if ($successCount > 0) {
            $io->success(sprintf('%d couverture(s) téléchargée(s) avec succès !', $successCount));
        }

        if ($failureCount > 0) {
            $io->warning(sprintf('%d échec(s) lors du téléchargement', $failureCount));
        }

        return Command::SUCCESS;
    }
} 