<?php

namespace App\Command;

use App\Entity\Auteur;
use App\Repository\AuteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-auteur-form',
    description: 'Teste la création d\'un auteur via le formulaire'
)]
class TestAuteurFormCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuteurRepository $auteurRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test du formulaire d\'auteur');

        try {
            // Créer un auteur de test
            $auteur = new Auteur();
            $auteur->setNom('Toriyama');
            $auteur->setPrenom('Akira');
            $auteur->setNomPlume('Akira Toriyama');
            $auteur->setNationalite('Japonais');
            $auteur->setDateNaissance(new \DateTime('1955-04-05'));
            $auteur->setBiographie('Akira Toriyama est un mangaka japonais célèbre, créateur de Dragon Ball et Dr. Slump. Il est considéré comme l\'un des artistes les plus influents du manga.');

            $this->entityManager->persist($auteur);
            $this->entityManager->flush();

            $io->success([
                '✅ Auteur créé avec succès !',
                '📝 Nom: ' . $auteur->getNom(),
                '📝 Prénom: ' . $auteur->getPrenom(),
                '📝 Nom de plume: ' . $auteur->getNomPlume(),
                '🌍 Nationalité: ' . $auteur->getNationalite(),
                '📅 Date de naissance: ' . $auteur->getDateNaissance()->format('d/m/Y'),
                '📚 Biographie: ' . substr($auteur->getBiographie(), 0, 50) . '...',
                '🆔 ID: ' . $auteur->getId()
            ]);

            // Vérifier le nombre total d'auteurs
            $totalAuteurs = $this->auteurRepository->count([]);
            $io->info("📊 Total d'auteurs en base: $totalAuteurs");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de la création de l\'auteur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 