<?php

namespace App\Command;

use App\Repository\CollectionUserRepository;
use App\Repository\OeuvreRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'app:manage-collection',
    description: 'Gère vos favoris (statistiques, nettoyage, etc.)',
)]
class ManageCollectionCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private CollectionUserRepository $collectionRepository,
        private OeuvreRepository $oeuvreRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user-id', 'u', InputOption::VALUE_REQUIRED, 'ID de l\'utilisateur')
            ->addOption('user-email', 'm', InputOption::VALUE_REQUIRED, 'Email de l\'utilisateur')
            ->addOption('stats', 's', InputOption::VALUE_NONE, 'Afficher les statistiques détaillées')
            ->addOption('clear', 'c', InputOption::VALUE_NONE, 'Vider complètement les favoris (ATTENTION!)')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'Lister toutes les œuvres favorites')
            ->setHelp('Cette commande permet de gérer vos favoris : afficher des statistiques, lister les œuvres, ou vider les favoris.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getOption('user-id');
        $userEmail = $input->getOption('user-email');
        $showStats = $input->getOption('stats');
        $clearCollection = $input->getOption('clear');
        $listCollection = $input->getOption('list');

        // Validation des paramètres
        if (!$userId && !$userEmail) {
            $io->error('Vous devez spécifier soit --user-id soit --user-email');
            return Command::FAILURE;
        }

        // Récupérer l'utilisateur
        $user = null;
        if ($userId) {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                $io->error("Utilisateur avec l'ID {$userId} non trouvé");
                return Command::FAILURE;
            }
        } elseif ($userEmail) {
            $user = $this->userRepository->findOneBy(['email' => $userEmail]);
            if (!$user) {
                $io->error("Utilisateur avec l'email {$userEmail} non trouvé");
                return Command::FAILURE;
            }
        }

        $io->title("Gestion des favoris de {$user->getNom()}");

        // Si aucune option spécifique, afficher les stats de base
        if (!$showStats && !$clearCollection && !$listCollection) {
            $showStats = true;
        }

        // Afficher les statistiques
        if ($showStats) {
            $this->displayStats($io, $user);
        }

        // Lister la collection
        if ($listCollection) {
            $this->listCollection($io, $user);
        }

        // Vider la collection
        if ($clearCollection) {
            return $this->clearCollection($io, $user, $input, $output);
        }

        return Command::SUCCESS;
    }

    private function displayStats(SymfonyStyle $io, $user): void
    {
        $io->section("📊 Statistiques de vos favoris");

        $collections = $this->collectionRepository->findBy(['user' => $user]);
        $totalOeuvres = count($collections);

        if ($totalOeuvres === 0) {
            $io->warning("Vous n'avez aucun favori pour le moment");
            return;
        }

        // Statistiques de base
        $io->text("⭐ Total d'œuvres favorites : {$totalOeuvres}");

        // Statistiques par genre/type
        $typeStats = [];
        $demographicStats = [];
        $statusStats = [];
        $totalChapters = 0;

        foreach ($collections as $collection) {
            $oeuvre = $collection->getOeuvre();
            
            // Types
            $type = $oeuvre->getType() ?? 'Non défini';
            $typeStats[$type] = ($typeStats[$type] ?? 0) + 1;

            // Demographics
            $demographic = $oeuvre->getDemographic() ?? 'Non défini';
            $demographicStats[$demographic] = ($demographicStats[$demographic] ?? 0) + 1;

            // Status
            $status = $oeuvre->getStatut() ?? 'Non défini';
            $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;

            // Chapitres
            $totalChapters += count($oeuvre->getChapitres());
        }

        $io->text("📄 Total de chapitres : {$totalChapters}");
        $io->newLine();

        // Tableau des types
        if (!empty($typeStats)) {
            $typeTable = [];
            arsort($typeStats);
            foreach ($typeStats as $type => $count) {
                $percentage = round(($count / $totalOeuvres) * 100, 1);
                $typeTable[] = [$type, $count, "{$percentage}%"];
            }

            $io->table(['Type', 'Nombre', 'Pourcentage'], $typeTable);
        }

        // Tableau des démographiques
        if (!empty($demographicStats)) {
            $demoTable = [];
            arsort($demographicStats);
            foreach ($demographicStats as $demo => $count) {
                $percentage = round(($count / $totalOeuvres) * 100, 1);
                $demoTable[] = [$demo, $count, "{$percentage}%"];
            }

            $io->table(['Démographie', 'Nombre', 'Pourcentage'], $demoTable);
        }

        // Tableau des statuts
        if (!empty($statusStats)) {
            $statusTable = [];
            arsort($statusStats);
            foreach ($statusStats as $status => $count) {
                $percentage = round(($count / $totalOeuvres) * 100, 1);
                $statusTable[] = [$status, $count, "{$percentage}%"];
            }

            $io->table(['Statut', 'Nombre', 'Pourcentage'], $statusTable);
        }
    }

    private function listCollection(SymfonyStyle $io, $user): void
    {
        $io->section("⭐ Liste de vos favoris");

        $collections = $this->collectionRepository->createQueryBuilder('c')
            ->join('c.oeuvre', 'o')
            ->where('c.user = :user')
            ->orderBy('o.titre', 'ASC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        if (empty($collections)) {
            $io->warning("Vous n'avez aucun favori pour le moment");
            return;
        }

        $tableData = [];
        foreach ($collections as $collection) {
            $oeuvre = $collection->getOeuvre();
            $tableData[] = [
                $oeuvre->getTitre(),
                $oeuvre->getAuteur() ? $oeuvre->getAuteur()->getNom() : 'Non défini',
                $oeuvre->getType() ?? 'Non défini',
                count($oeuvre->getChapitres()),
                $collection->getDateAjout()->format('d/m/Y')
            ];
        }

        $io->table(
            ['Titre', 'Auteur', 'Type', 'Chapitres', 'Ajouté le'],
            $tableData
        );

        $io->info("Total : " . count($collections) . " œuvre(s)");
    }

    private function clearCollection(SymfonyStyle $io, $user, InputInterface $input, OutputInterface $output): int
    {
        $io->section("🗑️ Vider les favoris");

        $collections = $this->collectionRepository->findBy(['user' => $user]);
        $totalOeuvres = count($collections);

        if ($totalOeuvres === 0) {
            $io->info("Vous n'avez aucun favori à supprimer");
            return Command::SUCCESS;
        }

        $io->warning("Cette action va supprimer TOUTES les {$totalOeuvres} œuvres de vos favoris !");
        $io->warning("Les œuvres elles-mêmes resteront en base de données, seuls les favoris seront supprimés.");

        $question = new ConfirmationQuestion(
            "Êtes-vous ABSOLUMENT SÛR de vouloir vider vos favoris ? (tapez 'oui' pour confirmer) ",
            false
        );

        if (!$io->askQuestion($question)) {
            $io->info("Opération annulée");
            return Command::SUCCESS;
        }

        // Supprimer toutes les entrées de collection
        foreach ($collections as $collection) {
            $this->entityManager->remove($collection);
        }

        $this->entityManager->flush();

        $io->success("🎉 Favoris vidés avec succès ! {$totalOeuvres} œuvre(s) supprimée(s) de vos favoris.");
        
        return Command::SUCCESS;
    }
} 