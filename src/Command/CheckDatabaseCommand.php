<?php

namespace App\Command;

use App\Entity\Oeuvre;
use App\Entity\Auteur;
use App\Entity\Tag;
use App\Entity\Chapitre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-database',
    description: 'Vérifie l\'état de la base de données',
)]
class CheckDatabaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔍 Vérification de la base de données');

        try {
            // Compter les entités
            $oeuvreCount = $this->entityManager->getRepository(Oeuvre::class)->count([]);
            $auteurCount = $this->entityManager->getRepository(Auteur::class)->count([]);
            $tagCount = $this->entityManager->getRepository(Tag::class)->count([]);
            $chapitreCount = $this->entityManager->getRepository(Chapitre::class)->count([]);

            $io->section('📊 Statistiques de la base de données');
            $io->table(
                ['Entité', 'Nombre'],
                [
                    ['Œuvres', $oeuvreCount],
                    ['Auteurs', $auteurCount],
                    ['Tags', $tagCount],
                    ['Chapitres', $chapitreCount],
                ]
            );

            if ($oeuvreCount > 0) {
                $io->section('📚 Dernières œuvres ajoutées');
                
                $oeuvres = $this->entityManager->getRepository(Oeuvre::class)
                    ->createQueryBuilder('o')
                    ->orderBy('o.createdAt', 'DESC')
                    ->setMaxResults(5)
                    ->getQuery()
                    ->getResult();

                $tableData = [];
                foreach ($oeuvres as $oeuvre) {
                    $tableData[] = [
                        $oeuvre->getTitre(),
                        $oeuvre->getMangadxId(),
                        $oeuvre->getType(),
                        $oeuvre->getCreatedAt()->format('d/m/Y H:i')
                    ];
                }

                $io->table(
                    ['Titre', 'MangaDx ID', 'Type', 'Créé le'],
                    $tableData
                );
            }

            $io->success('✅ Vérification terminée');

            // Inspection des URLs de pages de chapitres
            $io->section('Inspection des URLs de pages');
            $chapitres = $this->entityManager->getRepository(Chapitre::class)->findAll();
            $found = false;
            foreach ($chapitres as $chapitre) {
                $pages = $chapitre->getPages();
                if (!empty($pages)) {
                    $found = true;
                    $io->text('Chapitre: ' . $chapitre->getTitre() . ' (ID: ' . $chapitre->getId() . ')');
                    for ($i = 0; $i < min(2, count($pages)); $i++) {
                        $url = $pages[$i];
                        $io->text("Page " . ($i + 1) . ": " . $url);
                        if (str_contains($url, '/proxy/image')) {
                            $io->error("⚠️  URL avec proxy trouvée !");
                        } else {
                            $io->success("✅ URL directe");
                        }
                    }
                    break;
                }
            }
            if (!$found) {
                $io->warning('Aucun chapitre avec pages trouvé');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la vérification : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 