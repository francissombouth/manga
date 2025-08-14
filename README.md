# 📚 MangaThèque

[![CI/CD Pipeline](https://github.com/votre-username/manga/actions/workflows/ci-cd.yml/badge.svg)](https://github.com/votre-username/manga/actions/workflows/ci-cd.yml)
[![Docker Image](https://img.shields.io/badge/docker-ready-blue?logo=docker)](https://hub.docker.com/r/votre-username/manga)
[![PHP Version](https://img.shields.io/badge/php-8.2+-purple?logo=php)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/symfony-6.4+-green?logo=symfony)](https://symfony.com)
[![License](https://img.shields.io/badge/license-MIT-yellow.svg)](LICENSE)

Une application web moderne pour gérer votre collection de mangas, développée avec Symfony 7.3 et Docker.

## 🌐 Application en ligne
https://manga-1-9wga.onrender.com/oeuvres
## 🚀 Fonctionnalités

- 📚 **Gestion de collection** : Ajoutez, modifiez et organisez vos mangas
- 🔍 **Recherche avancée** : Trouvez rapidement vos œuvres préférées
- 👥 **Système utilisateur** : Comptes personnalisés avec favoris
- 📱 **Design responsive** : Interface adaptée mobile et desktop
- 🔐 **Sécurité** : Authentification et autorisation robustes
- 🐳 **Dockerisé** : Déploiement simplifié avec Docker Compose

## 🛠️ Technologies

- **Backend** : Symfony 7.3 , PHP 8.2, Doctrine ORM
- **Frontend** : Twig, JavaScript ES6+, AssetMapper
- **Base de données** : PostgreSQL
- **Conteneurisation** : Docker & Docker Compose
- **CI/CD** : GitHub Actions
- **Tests** : PHPUnit, PHPStan, PHP CS Fixer

## 💻 Développement local
> ⚠️ Cette section est destinée aux développeurs souhaitant contribuer ou tester en local.

### Prérequis
- Docker et Docker Compose
- Git

### Installation rapide
```bash
# Cloner le projet
git clone https://github.com/votre-username/manga.git
cd manga

# Lancer l'application
docker-compose up -d

# Accéder à l'application
open http://localhost:8082
```

### Installation manuelle
```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances Node.js
npm install

# Configurer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Compiler les assets
php bin/console asset-map:compile

# Lancer le serveur
symfony server:start
```

## 🧪 Tests

```bash
# Lancer les tests unitaires
php bin/phpunit

# Analyser le code avec PHPStan
vendor/bin/phpstan analyse src --level=8

# Vérifier le style de code
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## 🚀 Déploiement

### Avec Docker
```bash
# Construire l'image
docker build -t manga .

# Lancer avec Docker Compose
docker-compose up -d
```

### CI/CD automatique
Le projet utilise GitHub Actions pour :
- ✅ Tests automatiques
- 🔍 Analyse de code
- 🐳 Construction d'image Docker
- 🚀 Déploiement automatique sur Render.com

## 📁 Structure du projet

```
manga/
├── assets/                 # Assets frontend (CSS, JS)
├── bin/                   # Scripts Symfony
├── config/                # Configuration
├── docker/                # Configuration Docker
├── migrations/            # Migrations de base de données
├── public/                # Fichiers publics
├── src/                   # Code source PHP
├── templates/             # Templates Twig
├── tests/                 # Tests
└── .github/workflows/     # CI/CD GitHub Actions
```

## 🔧 Configuration technique
> Informations techniques pour comprendre l'architecture

### Variables d'environnement
Copiez `.env` vers `.env.local` et configurez :
```env
DATABASE_URL="postgresql://user:pass@localhost:5432/manga"
APP_SECRET="your-secret-key"
# Ajouter ici vos clés API (MangaDx, etc.)
```

### Clés API requises
Pour le bon fonctionnement de l'application, vous aurez besoin des clés API suivantes :

- **MangaDx API** : Obtenez votre clé sur [https://api.mangadex.org/](https://api.mangadex.org/)

### Base de données
```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger des données de test
php bin/console doctrine:fixtures:load
```
