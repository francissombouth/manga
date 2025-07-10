<?php

namespace App\Command;

use App\Entity\Oeuvre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-genres',
    description: 'Vérifie les genres associés aux œuvres',
)]
class CheckGenresCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🎭 Vérification des genres des œuvres');

        try {
            $oeuvres = $this->entityManager->getRepository(Oeuvre::class)
                ->createQueryBuilder('o')
                ->orderBy('o.createdAt', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            if (empty($oeuvres)) {
                $io->warning('Aucune œuvre trouvée');
                return Command::SUCCESS;
            }

            $tableData = [];
            foreach ($oeuvres as $oeuvre) {
                $genres = [];
                foreach ($oeuvre->getTags() as $tag) {
                    $genres[] = $tag->getNom();
                }
                
                $tableData[] = [
                    $oeuvre->getTitre(),
                    count($genres),
                    implode(', ', array_slice($genres, 0, 3)) . (count($genres) > 3 ? '...' : '')
                ];
            }

            $io->table(
                ['Œuvre', 'Nb Genres', 'Genres (3 premiers)'],
                $tableData
            );

            // Statistiques
            $totalOeuvres = count($oeuvres);
            $oeuvresAvecGenres = 0;
            $totalGenres = 0;

            foreach ($oeuvres as $oeuvre) {
                $nbGenres = count($oeuvre->getTags());
                if ($nbGenres > 0) {
                    $oeuvresAvecGenres++;
                }
                $totalGenres += $nbGenres;
            }

            $io->section('📊 Statistiques');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Œuvres avec genres', $oeuvresAvecGenres . '/' . $totalOeuvres],
                    ['Moyenne genres/œuvre', $totalOeuvres > 0 ? round($totalGenres / $totalOeuvres, 1) : 0],
                    ['Total associations', $totalGenres]
                ]
            );

            if ($oeuvresAvecGenres < $totalOeuvres) {
                $io->warning("⚠️ " . ($totalOeuvres - $oeuvresAvecGenres) . " œuvre(s) n'ont pas de genres");
                $io->note("Les genres sont importés automatiquement depuis MangaDx lors de l'import des œuvres");
            } else {
                $io->success("✅ Toutes les œuvres ont des genres associés !");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la vérification : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 