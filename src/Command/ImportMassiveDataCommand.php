<?php

namespace App\Command;

use App\Entity\Oeuvre;
use App\Entity\Auteur;
use App\Entity\Tag;
use App\Entity\Chapitre;
use App\Repository\OeuvreRepository;
use App\Repository\AuteurRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-massive-data',
    description: 'Importe massivement des données simulées dans la base de données',
)]
class ImportMassiveDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OeuvreRepository $oeuvreRepository,
        private AuteurRepository $auteurRepository,
        private TagRepository $tagRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre maximum d\'œuvres à créer', 50)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Supprimer les données existantes avant l\'import')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Simulation sans import réel')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        $io->title('🚀 Import Massif de Données Simulées');

        if ($dryRun) {
            $io->note('🔍 Mode simulation activé - Aucun import ne sera effectué');
        }

        if ($force && !$dryRun) {
            $io->warning('⚠️ Mode force activé - Les données existantes seront supprimées');
            if (!$io->confirm('Êtes-vous sûr de vouloir continuer ?', false)) {
                return Command::SUCCESS;
            }
            
            $this->clearExistingData($io);
        }

        // Générer les données massives
        $massiveData = $this->generateMassiveData($limit);

        $io->section(sprintf('📊 Génération de %d œuvres avec leurs données', count($massiveData)));
        
        if ($dryRun) {
            $this->displayDataPreview($io, $massiveData);
            return Command::SUCCESS;
        }

        // Import réel
        $progressBar = $io->createProgressBar(count($massiveData));
        $progressBar->start();

        $imported = 0;
        $errors = 0;

        // Pré-créer tous les tags et auteurs pour éviter les conflits
        $this->preCreateTagsAndAuthors($massiveData);

        foreach ($massiveData as $data) {
            $progressBar->setMessage($data['titre']);
            
            try {
                $this->createOeuvreFromData($data);
                $imported++;
                
                // Flush par batch pour optimiser
                if ($imported % 5 === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear(); // Nettoyer le cache
                }
                
            } catch (\Exception $e) {
                $errors++;
                $io->error(sprintf('Erreur pour "%s": %s', $data['titre'], $e->getMessage()));
                // Continuer même en cas d'erreur
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        // Flush final
        $this->entityManager->flush();
        
        $progressBar->finish();
        $io->newLine(2);

        // Résumé
        $io->section('📊 Résumé de l\'import');
        $io->table(
            ['Statut', 'Nombre'],
            [
                ['✅ Importés', $imported],
                ['❌ Erreurs', $errors],
                ['📚 Total traité', count($massiveData)]
            ]
        );

        if ($imported > 0) {
            $io->success(sprintf(
                '🎉 %d œuvre(s) ont été importées avec succès !',
                $imported
            ));
            
            $io->text([
                '💡 Votre base de données est maintenant remplie avec de nombreuses œuvres.',
                '🔄 Vous pouvez consulter les données dans l\'interface d\'administration.',
            ]);
        }

        return $errors === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function clearExistingData(SymfonyStyle $io): void
    {
        $io->text('🗑️ Suppression des données existantes...');
        
        // Supprimer dans l'ordre pour respecter les contraintes
        $this->entityManager->createQuery('DELETE FROM App\Entity\Chapitre')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CollectionUser')->execute(); 
        $this->entityManager->createQuery('DELETE FROM App\Entity\Statut')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Oeuvre')->execute();
        
        $io->text('✅ Données supprimées');
    }

    private function generateMassiveData(int $limit): array
    {
        $genres = ['Action', 'Aventure', 'Comédie', 'Drame', 'Fantastique', 'Romance', 'Mystère', 'Horreur', 'Sci-Fi', 'Slice of Life', 'Sport', 'Surnaturel', 'Thriller', 'Historique', 'Mecha', 'Musical', 'Psychologique'];
        $types = ['Manga', 'Manhwa', 'Manhua', 'Webtoon', 'Light Novel'];
        $statuts = ['En cours', 'Terminé', 'En pause', 'Annulé'];
        
        $prefixesTitres = [
            'The Chronicles of', 'Legend of', 'Tales of', 'Adventures of', 'Story of', 'World of',
            'Mystery of', 'Secret of', 'Return of', 'Rise of', 'Fall of', 'Quest for',
            'Battle of', 'War of', 'Peace of', 'Dragon', 'Phoenix', 'Shadow', 'Light',
            'Dark', 'Golden', 'Silver', 'Crystal', 'Magic', 'Sacred', 'Lost', 'Hidden'
        ];
        
        $suffixesTitres = [
            'Academy', 'Kingdom', 'Empire', 'Realm', 'Land', 'Island', 'City', 'Tower',
            'Castle', 'Temple', 'School', 'University', 'Guild', 'Order', 'Clan',
            'Hunter', 'Warrior', 'Mage', 'Knight', 'Prince', 'Princess', 'King', 'Queen',
            'Master', 'Hero', 'Legend', 'Saga', 'Chronicles', 'Dreams', 'Nightmares'
        ];

        $auteurs = [
            'Akira Toriyama', 'Masashi Kishimoto', 'Eiichiro Oda', 'Tite Kubo', 'Hajime Isayama',
            'Kentaro Miura', 'Naoki Urasawa', 'Makoto Yukimura', 'Hiromu Arakawa', 'Koyoharu Gotouge',
            'SIU', 'Chugong', 'TurtleMe', 'Sang-Shik Lim', 'Yongje Park', 'Moonjo', 'Redice Studio',
            'Studio Ppatta', 'Carnby Kim', 'Ylab', 'Naver Webtoon', 'Kakao Webtoon', 'LINE Webtoon'
        ];

        $data = [];
        
        for ($i = 1; $i <= $limit; $i++) {
            $titre = $prefixesTitres[array_rand($prefixesTitres)] . ' ' . $suffixesTitres[array_rand($suffixesTitres)];
            
            // Éviter les doublons de titre
            $titre .= ' ' . ($i > 30 ? 'Vol. ' . rand(1, 10) : '');
            
            $type = $types[array_rand($types)];
            $auteur = $auteurs[array_rand($auteurs)];
            $statut = $statuts[array_rand($statuts)];
            
            // Sélectionner 2-5 genres aléatoires
            $genreCount = rand(2, 5);
            $selectedGenres = array_rand(array_flip($genres), $genreCount);
            if (!is_array($selectedGenres)) {
                $selectedGenres = [$selectedGenres];
            }
            
            // Générer une date de publication aléatoire (entre 2000 et 2024)
            $year = rand(2000, 2024);
            $month = rand(1, 12);
            $day = rand(1, 28);
            
            // Générer un résumé aléatoire
            $resumeTemplates = [
                "Dans un monde où [CONCEPT], [PROTAGONISTE] doit [OBJECTIF] pour [RAISON]. Avec l'aide de [ALLIES], il/elle affronte [ANTAGONISTE] dans une quête épique.",
                "[PROTAGONISTE] découvre qu'il/elle possède [POUVOIR]. Maintenant, il/elle doit apprendre à maîtriser ce don pour [OBJECTIF] et sauver [LIEU].",
                "L'histoire suit [PROTAGONISTE], un(e) [PROFESSION] qui se retrouve impliqué(e) dans [SITUATION]. Entre [CONFLIT] et [ROMANCE], une aventure commence.",
                "Dans l'académie de [LIEU], [PROTAGONISTE] doit prouver sa valeur. Mais quand [MENACE] apparaît, tout change et une bataille pour [ENJEU] commence."
            ];
            
            $concepts = ['la magie existe', 'les monstres menacent l\'humanité', 'les dimensions se chevauchent', 'la technologie a évolué'];
            $protagonistes = ['un jeune héros', 'une guerrière déterminée', 'un étudiant ordinaire', 'une princesse rebelle'];
            $objectifs = ['sauver le monde', 'retrouver sa famille', 'maîtriser ses pouvoirs', 'découvrir la vérité'];
            $raisons = ['protéger ses amis', 'venger sa famille', 'accomplir son destin', 'sauver son royaume'];
            
            $resume = str_replace(
                ['[CONCEPT]', '[PROTAGONISTE]', '[OBJECTIF]', '[RAISON]'],
                [
                    $concepts[array_rand($concepts)],
                    $protagonistes[array_rand($protagonistes)],
                    $objectifs[array_rand($objectifs)],
                    $raisons[array_rand($raisons)]
                ],
                $resumeTemplates[array_rand($resumeTemplates)]
            );
            
            $data[] = [
                'titre' => $titre,
                'auteur' => $auteur,
                'type' => $type,
                'resume' => $resume,
                'datePublication' => "$year-$month-$day",
                'couverture' => '/covers/generated/' . strtolower(str_replace(' ', '-', $titre)) . '.jpg',
                'tags' => $selectedGenres,
                'statut' => $statut,
                'chapitres' => rand(5, 200) // Nombre de chapitres aléatoire
            ];
        }
        
        return $data;
    }

    private function displayDataPreview(SymfonyStyle $io, array $data): void
    {
        $io->section('📋 Aperçu des données qui seraient créées');
        
        $preview = array_slice($data, 0, 5);
        foreach ($preview as $item) {
            $io->text(sprintf(
                '• %s (%s) par %s - %d chapitres - Tags: %s',
                $item['titre'],
                $item['type'],
                $item['auteur'],
                $item['chapitres'],
                implode(', ', $item['tags'])
            ));
        }
        
        if (count($data) > 5) {
            $io->text(sprintf('... et %d autres œuvres', count($data) - 5));
        }
    }

    private function preCreateTagsAndAuthors(array $massiveData): void
    {
        // Extraire tous les tags uniques
        $allTags = [];
        $allAuthors = [];
        
        foreach ($massiveData as $data) {
            $allAuthors[] = $data['auteur'];
            $allTags = array_merge($allTags, $data['tags']);
        }
        
        $allTags = array_unique($allTags);
        $allAuthors = array_unique($allAuthors);
        
        // Créer tous les auteurs manquants
        foreach ($allAuthors as $authorName) {
            $auteur = $this->auteurRepository->findOneBy(['nom' => $authorName]);
            if (!$auteur) {
                $auteur = new Auteur();
                $auteur->setNom($authorName);
                $this->entityManager->persist($auteur);
            }
        }
        
        // Créer tous les tags manquants
        foreach ($allTags as $tagName) {
            $tag = $this->tagRepository->findOneBy(['nom' => $tagName]);
            if (!$tag) {
                $tag = new Tag();
                $tag->setNom($tagName);
                $this->entityManager->persist($tag);
            }
        }
        
        $this->entityManager->flush();
    }

    private function createOeuvreFromData(array $data): void
    {
        // Récupérer l'auteur (doit exister maintenant)
        $auteur = $this->auteurRepository->findOneBy(['nom' => $data['auteur']]);

        // Créer l'œuvre
        $oeuvre = new Oeuvre();
        $oeuvre->setTitre($data['titre']);
        $oeuvre->setType($data['type']);
        $oeuvre->setResume($data['resume']);
        $oeuvre->setCouverture($data['couverture']);
        $oeuvre->setDatePublication(new \DateTime($data['datePublication']));
        $oeuvre->setAuteur($auteur);

        // Ajouter les tags (doivent exister maintenant)
        foreach ($data['tags'] as $tagName) {
            $tag = $this->tagRepository->findOneBy(['nom' => $tagName]);
            if ($tag) {
                $oeuvre->addTag($tag);
            }
        }

        $this->entityManager->persist($oeuvre);

        // Créer quelques chapitres
        $nombreChapitres = min($data['chapitres'], 20); // Limiter pour éviter de trop charger
        for ($i = 1; $i <= $nombreChapitres; $i++) {
            $chapitre = new Chapitre();
            $chapitre->setTitre("Chapitre $i");
            $chapitre->setOrdre($i);
            $chapitre->setOeuvre($oeuvre);
            $chapitre->setPages([]); // Pages vides
            
            $this->entityManager->persist($chapitre);
        }
    }
} 