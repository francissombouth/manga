<?php

namespace App\DataFixtures;

use App\Entity\Auteur;
use App\Entity\Chapitre;
use App\Entity\Oeuvre;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        
        // Créer des utilisateurs
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setEmail($faker->email);
            $user->setNom($faker->name);
            $user->setPassword(password_hash('password', PASSWORD_DEFAULT));
            $user->setRoles(['ROLE_USER']);
            
            $manager->persist($user);
            $users[] = $user;
        }

        // Créer des tags pour mangas/manhwas
        $tags = [];
        $tagNames = ['Shônen', 'Shôjo', 'Seinen', 'Josei', 'Isekai', 'Mecha', 'Slice of Life', 'Yaoi', 'Yuri', 'Ecchi', 'Harem', 'Supernatural', 'School Life', 'Romance', 'Action', 'Aventure', 'Comédie', 'Drame', 'Fantastique', 'Horreur'];
        foreach ($tagNames as $tagName) {
            $tag = new Tag();
            $tag->setNom($tagName);
            $manager->persist($tag);
            $tags[] = $tag;
        }

        // Créer des auteurs de mangas/manhwas
        $auteurs = [];
        $auteursData = [
            ['nom' => 'Toriyama', 'prenom' => 'Akira', 'nomPlume' => null, 'nationalite' => 'Japonais'],
            ['nom' => 'Kishimoto', 'prenom' => 'Masashi', 'nomPlume' => null, 'nationalite' => 'Japonais'],
            ['nom' => 'Oda', 'prenom' => 'Eiichiro', 'nomPlume' => null, 'nationalite' => 'Japonais'],
            ['nom' => 'Kubo', 'prenom' => 'Tite', 'nomPlume' => null, 'nationalite' => 'Japonais'],
            ['nom' => 'Miura', 'prenom' => 'Kentaro', 'nomPlume' => null, 'nationalite' => 'Japonais'],
            ['nom' => 'Aoyama', 'prenom' => 'Gosho', 'nomPlume' => null, 'nationalite' => 'Japonais'],
            ['nom' => 'SIU', 'prenom' => null, 'nomPlume' => 'SIU', 'nationalite' => 'Coréen'],
            ['nom' => 'Chugong', 'prenom' => null, 'nomPlume' => 'Chugong', 'nationalite' => 'Coréen'],
        ];

        foreach ($auteursData as $auteurData) {
            $auteur = new Auteur();
            $auteur->setNom($auteurData['nom']);
            $auteur->setPrenom($auteurData['prenom']);
            $auteur->setNomPlume($auteurData['nomPlume']);
            $auteur->setNationalite($auteurData['nationalite']);
            $auteur->setBiographie($faker->paragraph(3));
            $auteur->setDateNaissance($faker->dateTimeBetween('-100 years', '-30 years'));
            
            $manager->persist($auteur);
            $auteurs[] = $auteur;
        }

        // Créer des mangas/manhwas
        $oeuvresData = [
            [
                'titre' => 'Dragon Ball',
                'type' => 'Manga',
                'auteur' => 0, // Toriyama
                'resume' => 'Les aventures de Son Goku, un guerrier Saiyan qui protège la Terre contre de puissants ennemis.',
                'tags' => [0, 14, 15], // Shônen, Action, Aventure
                'chapitres' => [
                    'L\'enfant mystérieux',
                    'Kamé Sennin',
                    'Le 21e tournoi',
                    'L\'armée du Red Ribbon'
                ]
            ],
            [
                'titre' => 'Naruto',
                'type' => 'Manga',
                'auteur' => 1, // Kishimoto
                'resume' => 'L\'histoire de Naruto Uzumaki, un jeune ninja qui rêve de devenir Hokage.',
                'tags' => [0, 14, 12], // Shônen, Action, School Life
                'chapitres' => [
                    'Uzumaki Naruto',
                    'L\'épreuve de survie',
                    'Le pays des Vagues',
                    'L\'examen Chûnin'
                ]
            ],
            [
                'titre' => 'One Piece',
                'type' => 'Manga',
                'auteur' => 2, // Oda
                'resume' => 'Monkey D. Luffy part à l\'aventure pour devenir le Roi des Pirates.',
                'tags' => [0, 14, 15, 16], // Shônen, Action, Aventure, Comédie
                'chapitres' => [
                    'Romance Dawn',
                    'Orange Town',
                    'Syrup Village',
                    'Baratie'
                ]
            ],
            [
                'titre' => 'Bleach',
                'type' => 'Manga',
                'auteur' => 3, // Kubo
                'resume' => 'Ichigo Kurosaki devient un Shinigami pour protéger le monde des Hollows.',
                'tags' => [0, 14, 11], // Shônen, Action, Supernatural
                'chapitres' => [
                    'Death & Strawberry',
                    'Goodbye Parakeet',
                    'Memories in the Rain',
                    'Quincy Archer'
                ]
            ],
            [
                'titre' => 'Berserk',
                'type' => 'Manga',
                'auteur' => 4, // Miura
                'resume' => 'L\'épopée sombre de Guts, un mercenaire dans un monde de fantasy médiéval.',
                'tags' => [2, 14, 17, 19], // Seinen, Action, Drame, Horreur
                'chapitres' => [
                    'L\'Épée noire',
                    'La Marque',
                    'L\'Éclipse',
                    'La Chasse'
                ]
            ],
            [
                'titre' => 'Detective Conan',
                'type' => 'Manga',
                'auteur' => 5, // Aoyama
                'resume' => 'Shinichi Kudo, transformé en enfant, résout des crimes sous l\'identité de Conan Edogawa.',
                'tags' => [0, 12], // Shônen, School Life
                'chapitres' => [
                    'Le détective ressuscité',
                    'L\'enlèvement de la fille du patron',
                    'L\'affaire du meurtre de l\'idole',
                    'Le code secret'
                ]
            ],
            [
                'titre' => 'Tower of God',
                'type' => 'Manhwa',
                'auteur' => 6, // SIU
                'resume' => 'Bam escalade la Tour mystérieuse pour retrouver son amie Rachel.',
                'tags' => [4, 14, 18], // Isekai, Action, Fantastique
                'chapitres' => [
                    'Headon\'s Floor',
                    'Evankhell\'s Floor',
                    'Test Floor',
                    'Crown Game'
                ]
            ],
            [
                'titre' => 'Solo Leveling',
                'type' => 'Manhwa',
                'auteur' => 7, // Chugong
                'resume' => 'Sung Jin-Woo, le chasseur le plus faible, gagne le pouvoir de monter en niveau.',
                'tags' => [4, 14, 18], // Isekai, Action, Fantastique
                'chapitres' => [
                    'Le Chasseur le plus faible',
                    'Le Donjon en double',
                    'Le Système',
                    'La Re-Évaluation'
                ]
            ]
        ];

        $oeuvres = [];
        foreach ($oeuvresData as $index => $oeuvreData) {
            $oeuvre = new Oeuvre();
            $oeuvre->setTitre($oeuvreData['titre']);
            $oeuvre->setType($oeuvreData['type']);
            $oeuvre->setAuteur($auteurs[$oeuvreData['auteur']]);
            $oeuvre->setResume($oeuvreData['resume']);
            $oeuvre->setDatePublication($faker->dateTimeBetween('-50 years', '-1 year'));
            
            // Ajouter des tags
            foreach ($oeuvreData['tags'] as $tagIndex) {
                $oeuvre->addTag($tags[$tagIndex]);
            }
            
            $manager->persist($oeuvre);
            $oeuvres[] = $oeuvre;
            
            // Créer des chapitres
            foreach ($oeuvreData['chapitres'] as $chapterIndex => $chapitreTitle) {
                $chapitre = new Chapitre();
                $chapitre->setTitre($chapitreTitle);
                $chapitre->setOrdre($chapterIndex + 1);
                $chapitre->setOeuvre($oeuvre);
                $chapitre->setResume($faker->paragraph(2));
                // Pour les mangas/manhwas, les pages sont des images
                $chapitre->setPages([
                    'page1' => 'https://picsum.photos/800/1200?random=' . ($index * 10 + $chapterIndex + 1),
                    'page2' => 'https://picsum.photos/800/1200?random=' . ($index * 10 + $chapterIndex + 2),
                    'page3' => 'https://picsum.photos/800/1200?random=' . ($index * 10 + $chapterIndex + 3),
                    'page4' => 'https://picsum.photos/800/1200?random=' . ($index * 10 + $chapterIndex + 4)
                ]);
                
                // Pour certains chapitres, les marquer comme récents (pour tester la pastille NEW)
                if ($index < 3 && $chapterIndex === count($oeuvreData['chapitres']) - 1) {
                    // Dernier chapitre des 3 premières œuvres = récent
                    // Forcer une date récente pour ce chapitre
                    $reflection = new \ReflectionClass($chapitre);
                    $createdAtProperty = $reflection->getProperty('createdAt');
                    $createdAtProperty->setAccessible(true);
                    $createdAtProperty->setValue($chapitre, new \DateTimeImmutable('-' . rand(1, 6) . ' days'));
                    
                    $updatedAtProperty = $reflection->getProperty('updatedAt');
                    $updatedAtProperty->setAccessible(true);
                    $updatedAtProperty->setValue($chapitre, new \DateTimeImmutable('-' . rand(1, 6) . ' days'));
                }
                
                $manager->persist($chapitre);
            }
        }

        $manager->flush();
        
        echo "Fixtures chargées avec succès !\n";
        echo "- " . count($users) . " utilisateurs créés\n";
        echo "- " . count($tags) . " tags créés\n"; 
        echo "- " . count($auteurs) . " auteurs créés\n";
        echo "- " . count($oeuvres) . " œuvres créées\n";
        echo "- Chapitres créés pour chaque œuvre\n";
        echo "Quelques œuvres ont des chapitres récents pour tester la pastille NEW.\n";
    }
}
