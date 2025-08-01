<?php

namespace App\Command;

use App\Entity\Tag;
use App\Repository\OeuvreRepository;
use App\Repository\TagRepository;
use App\Service\MangaDxTagService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:sync-tags',
    description: 'Synchronise les tags/genres depuis MangaDx et associe automatiquement des genres aux œuvres',
)]
class SyncTagsCommand extends Command
{
    public function __construct(
        private MangaDxTagService $tagService,
        private TagRepository $tagRepository,
        private OeuvreRepository $oeuvreRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('create-popular', null, InputOption::VALUE_NONE, 'Créer les genres populaires manuellement')
            ->addOption('associate-existing', null, InputOption::VALUE_NONE, 'Associer des genres aux œuvres existantes basé sur les métadonnées')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forcer l\'opération même si les tags existent déjà')
            ->addOption('sync-all-oeuvres', null, InputOption::VALUE_NONE, 'Synchroniser les genres pour toutes les œuvres avec ID MangaDx')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $createPopular = $input->getOption('create-popular');
        $associateExisting = $input->getOption('associate-existing');
        $force = $input->getOption('force');
        $syncAllOeuvres = $input->getOption('sync-all-oeuvres');

        $io->title('🏷️ Synchronisation des Genres MangaDx');

        // Première étape : Essayer de synchroniser depuis l'API
        $io->section('📥 Synchronisation depuis l\'API MangaDx');
        $syncedTags = $this->tagService->syncAllTags();
        
        if (!empty($syncedTags)) {
            $io->success("✅ " . count($syncedTags) . " genres synchronisés depuis l'API MangaDx");
        } else {
            $io->warning("⚠️ Aucun genre synchronisé depuis l'API MangaDx");
        }

        // Deuxième étape : Créer les genres populaires manuellement
        if ($createPopular) {
            $io->section('🎯 Création des genres populaires');
            $created = $this->createPopularTags($force);
            $io->success("✅ {$created} genres populaires créés/mis à jour");
        }

        // Troisième étape : Associer des genres aux œuvres existantes
        if ($associateExisting) {
            $io->section('🔗 Association automatique de genres');
            $associated = $this->associateGenresBasedOnMetadata($io);
            $io->success("✅ Genres associés à {$associated} œuvres");
        }

        // Quatrième étape : Synchroniser les genres pour toutes les œuvres avec ID MangaDx
        if ($syncAllOeuvres) {
            $io->section('🔄 Synchronisation des genres pour toutes les œuvres');
            $synced = $this->syncGenresForAllOeuvres($io);
            $io->success("✅ Genres synchronisés pour {$synced} œuvres");
        }

        // Statistiques finales
        $io->section('📊 Statistiques finales');
        $totalTags = $this->tagRepository->count([]);
        $totalOeuvres = $this->oeuvreRepository->count([]);
        $oeuvresWithTags = $this->oeuvreRepository->createQueryBuilder('o')
            ->select('COUNT(DISTINCT o.id)')
            ->join('o.tags', 't')
            ->getQuery()
            ->getSingleScalarResult();

        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Genres disponibles', $totalTags],
                ['Œuvres totales', $totalOeuvres],
                ['Œuvres avec genres', $oeuvresWithTags],
                ['Couverture', $totalOeuvres > 0 ? round(($oeuvresWithTags / $totalOeuvres) * 100, 1) . '%' : '0%']
            ]
        );

        return Command::SUCCESS;
    }

    private function createPopularTags(bool $force): int
    {
        $popularGenres = [
            'Action' => 'Œuvres avec beaucoup d\'action et de combats',
            'Romance' => 'Histoires d\'amour et de relations romantiques',
            'Comédie' => 'Œuvres humoristiques et divertissantes',
            'Drame' => 'Histoires dramatiques et émotionnelles',
            'Fantastique' => 'Mondes imaginaires avec magie et créatures fantastiques',
            'École' => 'Histoires se déroulant dans un cadre scolaire',
            'Tranche de vie' => 'Histoires du quotidien réalistes',
            'Surnaturel' => 'Éléments paranormaux et mystérieux',
            'Ecchi' => 'Contenu suggestif et romantique',
            'Sport' => 'Œuvres centrées sur le sport et la compétition',
            'Mystère' => 'Énigmes et histoires de suspense',
            'Horreur' => 'Histoires effrayantes et terrifiantes',
            'Aventure' => 'Voyages et quêtes épiques',
            'Psychologique' => 'Exploration de l\'esprit humain',
            'Seinen' => 'Destiné à un public adulte masculin',
            'Shounen' => 'Destiné à un public jeune masculin',
            'Shoujo' => 'Destiné à un public jeune féminin',
            'Josei' => 'Destiné à un public adulte féminin'
        ];

        $created = 0;
        foreach ($popularGenres as $name => $description) {
            $existingTag = $this->tagRepository->findOneBy(['nom' => $name]);
            
            if (!$existingTag || $force) {
                if (!$existingTag) {
                    $tag = new Tag();
                    $tag->setNom($name);
                    $this->entityManager->persist($tag);
                    $created++;
                    $this->logger->info("Genre créé: {$name}");
                } else {
                    $this->logger->info("Genre existant: {$name}");
                }
            }
        }

        $this->entityManager->flush();
        return $created;
    }

    private function associateGenresBasedOnMetadata(SymfonyStyle $io): int
    {
        /** @var \App\Entity\Oeuvre[] $oeuvres */
        $oeuvres = $this->oeuvreRepository->findAll();
        
        $associated = 0;
        $progressBar = $io->createProgressBar(count($oeuvres));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | 📚 %message%');

        foreach ($oeuvres as $oeuvre) {
            $progressBar->setMessage($oeuvre->getTitre() ?? 'Sans titre');
            
            $genresAssociated = $this->associateGenresForOeuvre($oeuvre);
            if ($genresAssociated > 0) {
                $associated++;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        $this->entityManager->flush();
        return $associated;
    }

    private function associateGenresForOeuvre(\App\Entity\Oeuvre $oeuvre): int
    {
        // Ne pas réassocier si l'œuvre a déjà des genres
        if (count($oeuvre->getTags()) > 0) {
            return 0;
        }

        $genresAssociated = 0;
        $title = strtolower($oeuvre->getTitre() ?? '');
        $resume = strtolower($oeuvre->getResume() ?? '');
        $demographic = $oeuvre->getDemographic();
        $contentRating = $oeuvre->getContentRating();

        // Association basée sur le demographic
        if ($demographic) {
            $tag = $this->tagRepository->findOneBy(['nom' => ucfirst($demographic)]);
            if ($tag instanceof \App\Entity\Tag) {
                $oeuvre->addTag($tag);
                $genresAssociated++;
            }
        }

        // Association basée sur les mots-clés dans le titre et résumé
        $keywords = [
            'Action' => ['fight', 'battle', 'combat', 'war', 'action'],
            'Romance' => ['love', 'romance', 'romantic', 'relationship', 'amour'],
            'Comédie' => ['comedy', 'funny', 'humor', 'comic', 'gag'],
            'École' => ['school', 'student', 'class', 'university', 'academy', 'high school'],
            'Fantastique' => ['magic', 'fantasy', 'magical', 'wizard', 'dragon', 'elf'],
            'Aventure' => ['adventure', 'journey', 'quest', 'travel', 'explore'],
            'Mystère' => ['mystery', 'detective', 'investigation', 'crime', 'murder'],
            'Sport' => ['sport', 'football', 'basketball', 'tennis', 'baseball', 'soccer'],
        ];

        foreach ($keywords as $genre => $words) {
            foreach ($words as $word) {
                if (str_contains($title, $word) || str_contains($resume, $word)) {
                    $tag = $this->tagRepository->findOneBy(['nom' => $genre]);
                    if ($tag instanceof \App\Entity\Tag && !$oeuvre->getTags()->contains($tag)) {
                        $oeuvre->addTag($tag);
                        $genresAssociated++;
                        break; // Un seul mot-clé suffit pour ce genre
                    }
                }
            }
        }

        // Association basée sur le content rating
        if ($contentRating === 'suggestive' || $contentRating === 'erotica') {
            $tag = $this->tagRepository->findOneBy(['nom' => 'Ecchi']);
            if ($tag instanceof \App\Entity\Tag && !$oeuvre->getTags()->contains($tag)) {
                $oeuvre->addTag($tag);
                $genresAssociated++;
            }
        }

        // Ajouter au moins "Tranche de vie" si aucun genre trouvé
        if ($genresAssociated === 0) {
            $tag = $this->tagRepository->findOneBy(['nom' => 'Tranche de vie']);
            if ($tag instanceof \App\Entity\Tag) {
                $oeuvre->addTag($tag);
                $genresAssociated++;
            }
        }

        return $genresAssociated;
    }

    /**
     * Synchronise les genres pour toutes les œuvres avec ID MangaDx
     */
    private function syncGenresForAllOeuvres(SymfonyStyle $io): int
    {
        $oeuvres = $this->oeuvreRepository->createQueryBuilder('o')
            ->where('o.mangadxId IS NOT NULL')
            ->andWhere('o.mangadxId != :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult();

        if (empty($oeuvres)) {
            $io->info('Aucune œuvre avec ID MangaDx trouvée');
            return 0;
        }

        $io->text("📚 Trouvé " . count($oeuvres) . " œuvres avec ID MangaDx");

        $progressBar = $io->createProgressBar(count($oeuvres));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | 📚 %message%');

        $synced = 0;
        $errors = 0;

        foreach ($oeuvres as $oeuvre) {
            if (!$oeuvre instanceof \App\Entity\Oeuvre) {
                continue;
            }
            
            $progressBar->setMessage($oeuvre->getTitre() ?? 'Sans titre');
            
            try {
                // Récupérer les données depuis MangaDx
                $mangadxId = $oeuvre->getMangadxId();
                if (!$mangadxId) {
                    continue;
                }
                
                $response = $this->httpClient->request('GET', 'https://api.mangadex.org/manga/' . $mangadxId, [
                    'headers' => ['User-Agent' => 'MangaTheque/1.0 (Educational Project)']
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = $response->toArray();
                    $attributes = $data['data']['attributes'] ?? [];
                    
                    // Récupérer les tags depuis les attributs
                    $tags = $attributes['tags'] ?? [];
                    
                    // Traiter les tags
                    $tagRelations = array_filter($tags, function($tag) {
                        return isset($tag['attributes']) && $tag['attributes']['group'] === 'genre';
                    });

                    $tagsAdded = 0;
                    foreach ($tagRelations as $tagRelation) {
                        $mangadxId = $tagRelation['id'];
                        $attributes = $tagRelation['attributes'];
                        $tagName = $attributes['name']['fr'] ?? $attributes['name']['en'] ?? null;
                        
                        if ($tagName) {
                            // Chercher ou créer le tag
                            $tag = $this->tagRepository->findOneBy(['mangadxId' => $mangadxId]);
                            if (!$tag) {
                                $tag = $this->tagRepository->findOneBy(['nom' => $tagName]);
                                if (!$tag) {
                                    $tag = new Tag();
                                    $tag->setNom($tagName);
                                    $tag->setMangadxId($mangadxId);
                                    $this->entityManager->persist($tag);
                                } else if (!$tag->getMangadxId()) {
                                    $tag->setMangadxId($mangadxId);
                                }
                            }
                            
                            // Associer le tag à l'œuvre
                            if ($tag instanceof \App\Entity\Tag && !$oeuvre->getTags()->contains($tag)) {
                                $oeuvre->addTag($tag);
                                $tagsAdded++;
                            }
                        }
                    }
                    
                    if ($tagsAdded > 0) {
                        $synced++;
                        $this->logger->info("✅ {$oeuvre->getTitre()} - {$tagsAdded} genres ajoutés");
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->logger->error("❌ {$oeuvre->getTitre()} - Erreur: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        $this->entityManager->flush();

        if ($errors > 0) {
            $io->warning("⚠️ {$errors} erreurs rencontrées");
        }

        return $synced;
    }
} 