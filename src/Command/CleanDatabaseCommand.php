<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-database',
    description: 'Nettoie complètement la base de données en supprimant toutes les données',
)]
class CleanDatabaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧹 Nettoyage complet de la base de données');

        // Demander confirmation
        if (!$io->confirm('⚠️  ATTENTION ! Cette action va supprimer TOUTES les données de la base. Êtes-vous sûr ?', false)) {
            $io->warning('Opération annulée.');
            return Command::SUCCESS;
        }

        try {
            $io->section('Suppression des données...');

            // Supprimer dans l'ordre pour respecter les contraintes de clés étrangères
            $tables = [
                'App\Entity\Chapitre' => 'chapitres',
                'App\Entity\CollectionUser' => 'collections utilisateurs',
                'App\Entity\Oeuvre' => 'œuvres',
                'App\Entity\Auteur' => 'auteurs',
                'App\Entity\Tag' => 'tags',
                'App\Entity\Statut' => 'statuts'
            ];

            foreach ($tables as $entity => $label) {
                $count = $this->entityManager->createQuery("DELETE FROM $entity")->execute();
                $io->text("✅ {$count} {$label} supprimés");
            }

            // Vider le cache Doctrine
            $this->entityManager->clear();

            $io->success('🎉 Base de données nettoyée avec succès !');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du nettoyage : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 