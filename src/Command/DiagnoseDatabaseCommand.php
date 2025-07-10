<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:diagnose-database',
    description: 'Diagnostique les problèmes de connexion à la base de données',
)]
class DiagnoseDatabaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔧 Diagnostic de la base de données');

        try {
            $connection = $this->entityManager->getConnection();
            
            // Test de connexion
            $io->section('🔌 Test de connexion');
            $connection->connect();
            $io->success('✅ Connexion réussie à la base de données');

            // Informations de connexion
            $io->section('📊 Informations de connexion');
            $params = $connection->getParams();
            $io->table(
                ['Paramètre', 'Valeur'],
                [
                    ['Driver', $params['driver'] ?? 'N/A'],
                    ['Host', $params['host'] ?? 'N/A'],
                    ['Port', $params['port'] ?? 'N/A'],
                    ['Database', $params['dbname'] ?? 'N/A'],
                    ['User', $params['user'] ?? 'N/A'],
                ]
            );

            // Test de requête simple
            $io->section('🔍 Test de requête');
            $result = $connection->executeQuery('SELECT version()')->fetchOne();
            $io->text("Version PostgreSQL : " . $result);

            // Test des tables
            $io->section('📋 Tables disponibles');
            $tables = $connection->createSchemaManager()->listTableNames();
            if (empty($tables)) {
                $io->warning('⚠️ Aucune table trouvée - Exécutez les migrations !');
                $io->note('Commande : php bin/console doctrine:migrations:migrate');
            } else {
                $io->listing($tables);
            }

            // Test des données
            if (in_array('oeuvre', $tables)) {
                $io->section('📚 Données dans la table oeuvre');
                $count = $connection->executeQuery('SELECT COUNT(*) FROM oeuvre')->fetchOne();
                $io->text("Nombre d'œuvres : " . $count);

                if ($count > 0) {
                    $oeuvres = $connection->executeQuery('SELECT titre, mangadx_id FROM oeuvre LIMIT 3')->fetchAllAssociative();
                    $io->table(['Titre', 'MangaDx ID'], array_map(function($row) {
                        return [$row['titre'], $row['mangadx_id']];
                    }, $oeuvres));
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur de connexion : ' . $e->getMessage());
            
            $io->section('🔧 Solutions possibles');
            $io->listing([
                'Vérifiez que PostgreSQL est démarré',
                'Vérifiez les paramètres de connexion dans .env',
                'Exécutez les migrations : php bin/console doctrine:migrations:migrate',
                'Vérifiez que la base "manga" existe dans pgAdmin4'
            ]);

            return Command::FAILURE;
        }
    }
} 