<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur test admin
        $user = new User();
        $user->setEmail('admin@mangabook.com');
        $user->setNom('Admin Test');
        $user->setRoles(['ROLE_ADMIN']);
        
        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'password123'
        );
        $user->setPassword($hashedPassword);

        $manager->persist($user);

        // Créer un utilisateur normal
        $user2 = new User();
        $user2->setEmail('user@mangabook.com');
        $user2->setNom('Utilisateur Test');
        $user2->setRoles(['ROLE_USER']);
        
        $hashedPassword2 = $this->passwordHasher->hashPassword(
            $user2,
            'password123'
        );
        $user2->setPassword($hashedPassword2);

        $manager->persist($user2);

        $manager->flush();
    }
} 