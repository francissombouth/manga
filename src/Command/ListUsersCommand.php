<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\CollectionUserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-users',
    description: 'Liste tous les utilisateurs avec leurs IDs et statistiques de favoris',
)]
class ListUsersCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private CollectionUserRepository $collectionRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title("Liste des utilisateurs");

        $users = $this->userRepository->findAll();

        if (empty($users)) {
            $io->warning('Aucun utilisateur trouvé dans la base de données');
            return Command::SUCCESS;
        }

        $tableData = [];
        foreach ($users as $user) {
            $collectionCount = $this->collectionRepository->count(['user' => $user]);
            $roles = implode(', ', $user->getRoles());
            
            $tableData[] = [
                $user->getId(),
                $user->getNom(),
                $user->getEmail(),
                $roles,
                $collectionCount,
                $user->getCreatedAt() ? $user->getCreatedAt()->format('d/m/Y') : 'N/A'
            ];
        }

        $io->table(
            ['ID', 'Nom', 'Email', 'Rôles', 'Favoris', 'Créé le'],
            $tableData
        );

        $io->info("Total: " . count($users) . " utilisateur(s)");
        
        $io->section("💡 Utilisation");
        $io->text("Pour importer le catalogue dans vos favoris, utilisez :");
        $io->text("• Par ID utilisateur: <info>php bin/console app:import-catalog-to-collection --user-id=1</info>");
        $io->text("• Par email: <info>php bin/console app:import-catalog-to-collection --user-email=votre@email.com</info>");
        $io->text("• Mode simulation: <info>php bin/console app:import-catalog-to-collection --user-id=1 --dry-run</info>");

        return Command::SUCCESS;
    }
} 