# 📚 MangaThèque

[![CI/CD Pipeline](https://github.com/votre-username/manga/actions/workflows/ci-cd.yml/badge.svg)](https://github.com/votre-username/manga/actions/workflows/ci-cd.yml)
[![Docker Image](https://img.shields.io/badge/docker-ready-blue?logo=docker)](https://hub.docker.com/r/votre-username/manga)
[![PHP Version](https://img.shields.io/badge/php-8.2+-purple?logo=php)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/symfony-7.3+-green?logo=symfony)](https://symfony.com)
[![License](https://img.shields.io/badge/license-MIT-yellow.svg)](LICENSE)

Une application web moderne pour gérer votre collection de mangas, développée avec Symfony 7.3 et Docker.

## 🚀 Fonctionnalités

### 👥 Gestion des utilisateurs
- **Inscription et connexion** : Système d'authentification sécurisé
- **Profils personnalisés** : Gestion des informations utilisateur
- **Rôles et permissions** : Système d'administration avec contrôle d'accès

### 📚 Gestion de collection
- **Catalogue d'œuvres** : Ajout, modification et organisation de mangas
- **Gestion des auteurs** : Association d'auteurs aux œuvres
- **Système de tags** : Catégorisation et recherche par tags
- **Statuts de lecture** : Suivi de progression (En cours, Terminé, etc.)

### 🔍 Recherche et navigation
- **Recherche avancée** : Filtrage par titre, auteur, tags, statut
- **Tri et pagination** : Navigation fluide dans les collections
- **Recherche en temps réel** : Interface responsive et intuitive

### 💬 Système social
- **Commentaires** : Partage d'avis et discussions
- **Système de likes** : Interactions entre utilisateurs
- **Notes et évaluations** : Système de notation des œuvres

### 🎨 Interface utilisateur
- **Design responsive** : Interface adaptée mobile et desktop
- **Thème moderne** : Interface utilisateur intuitive et esthétique
- **Navigation fluide** : Menu burger et navigation optimisée

### 🔐 Sécurité
- **Authentification robuste** : Protection des comptes utilisateurs
- **CSRF Protection** : Protection contre les attaques CSRF
- **Validation des données** : Sécurisation des formulaires

## 🛠️ Technologies

### Backend
- **Framework** : Symfony 7.3
- **Langage** : PHP 8.2+
- **ORM** : Doctrine 3.4
- **Base de données** : PostgreSQL 16
- **Authentification** : Symfony Security Bundle

### Frontend
- **Templates** : Twig 3.0
- **JavaScript** : ES6+, Stimulus.js
- **CSS** : AssetMapper, Bootstrap
- **UX** : Turbo.js pour la navigation fluide

### Infrastructure
- **Conteneurisation** : Docker & Docker Compose
- **Serveur web** : Nginx
- **CI/CD** : GitHub Actions
- **Tests** : PHPUnit, PHPStan, PHP CS Fixer

## 📦 Installation

### Prérequis

#### Pour l'installation Docker (Recommandée)
- Docker 20.10+
- Docker Compose 2.0+
- Git

#### Pour l'installation manuelle
- PHP 8.2+
- Composer 2.0+
- Node.js 18+
- PostgreSQL 16+
- Extensions PHP requises :
  - ext-ctype
  - ext-iconv
  - ext-pdo_pgsql
  - ext-mbstring
  - ext-xml
  - ext-curl

### Installation rapide avec Docker

```bash
# Cloner le projet
git clone https://github.com/votre-username/manga.git
cd manga

# Lancer l'application
docker-compose up -d

# Attendre que les services démarrent (environ 2-3 minutes)
# Puis accéder à l'application
open http://localhost:8082
```

### Installation manuelle détaillée

```bash
# 1. Cloner le projet
git clone https://github.com/votre-username/manga.git
cd manga

# 2. Installer les dépendances PHP
composer install

# 3. Installer les dépendances Node.js (si nécessaire)
npm install

# 4. Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos paramètres

# 5. Créer la base de données PostgreSQL
# Assurez-vous que PostgreSQL est démarré
createdb manga_db

# 6. Configurer la base de données dans .env.local
# DATABASE_URL="postgresql://username:password@localhost:5432/manga_db?serverVersion=16&charset=utf8"

# 7. Exécuter les migrations
php bin/console doctrine:migrations:migrate

# 8. Charger les données de test (optionnel)
php bin/console doctrine:fixtures:load

# 9. Compiler les assets
php bin/console asset-map:compile

# 10. Lancer le serveur de développement
symfony server:start
# Ou avec PHP intégré : php -S localhost:8000 -t public/
```

## 🔧 Configuration

### Variables d'environnement

Créez un fichier `.env.local` avec les configurations suivantes :

```env
# Base de données
DATABASE_URL="postgresql://username:password@localhost:5432/manga_db?serverVersion=16&charset=utf8"

# Sécurité
APP_SECRET="votre-clé-secrète-très-longue-et-aléatoire"

# Email (optionnel)
MAILER_DSN="smtp://localhost:1025"

# Base de données PostgreSQL
POSTGRES_DB=app
POSTGRES_USER=app
POSTGRES_PASSWORD=!ChangeMe!
POSTGRES_VERSION=16

# Environnement
APP_ENV=dev
APP_DEBUG=true
```

### Configuration de la base de données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger des données de test
php bin/console doctrine:fixtures:load --append

# Vérifier la connexion
php bin/console doctrine:query:sql "SELECT 1"
```

### Configuration des permissions

```bash
# Donner les permissions d'écriture aux dossiers
chmod -R 777 var/
chmod -R 777 public/uploads/
```

## 🎯 Guide d'utilisation

### Première connexion

1. **Accéder à l'application** : Ouvrez votre navigateur sur `http://localhost:8082`
2. **Créer un compte** : Cliquez sur "S'inscrire" et remplissez le formulaire
3. **Se connecter** : Utilisez vos identifiants pour vous connecter

### Gestion de votre collection

#### Ajouter une œuvre
1. Connectez-vous à votre compte
2. Cliquez sur "Ajouter une œuvre" dans le menu
3. Remplissez les informations :
   - **Titre** : Nom du manga
   - **Auteur** : Nom de l'auteur (créé automatiquement si inexistant)
   - **Description** : Synopsis de l'œuvre
   - **Tags** : Catégories (Action, Romance, etc.)
   - **Statut** : En cours, Terminé, Abandonné
   - **Image de couverture** : Upload d'une image

#### Gérer votre collection
- **Voir vos œuvres** : Accédez à "Ma Collection"
- **Modifier une œuvre** : Cliquez sur l'icône d'édition
- **Supprimer** : Utilisez l'icône de suppression (admin uniquement)
- **Changer le statut** : Utilisez le menu déroulant de statut

#### Rechercher des œuvres
- **Recherche simple** : Utilisez la barre de recherche
- **Filtres avancés** : Filtrez par auteur, tags, statut
- **Tri** : Triez par titre, date d'ajout, popularité

### Fonctionnalités sociales

#### Commentaires
- **Lire les commentaires** : Cliquez sur une œuvre pour voir les avis
- **Ajouter un commentaire** : Connectez-vous et utilisez le formulaire
- **Liker un commentaire** : Cliquez sur le cœur à côté du commentaire

#### Notes et évaluations
- **Noter une œuvre** : Utilisez le système d'étoiles (1-5)
- **Voir les moyennes** : Les notes moyennes sont affichées

### Administration (Rôle Admin)

#### Gestion des utilisateurs
1. Accédez au panneau d'administration
2. Section "Utilisateurs" pour gérer les comptes
3. Modifier les rôles et permissions

#### Import de données
- **Import MangaDx** : Import automatique depuis MangaDx
- **Import massif** : Import de fichiers CSV/JSON
- **Gestion des tags** : Synchronisation et nettoyage

#### Maintenance
- **Base de données** : Outils de diagnostic et nettoyage
- **Logs** : Consultation des logs d'erreur
- **Cache** : Gestion du cache de l'application

## 🧪 Tests

### Lancer les tests

```bash
# Tests unitaires
php bin/phpunit

# Tests avec couverture de code
php bin/phpunit --coverage-html var/coverage

# Tests spécifiques
php bin/phpunit tests/Controller/
php bin/phpunit tests/Entity/

# Analyser le code
vendor/bin/phpstan analyse src --level=8

# Vérifier le style de code
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/php-cs-fixer fix
```

### Tests d'intégration

```bash
# Tests de l'API
php bin/phpunit tests/Controller/Api/

# Tests des formulaires
php bin/phpunit tests/Form/

# Tests des services
php bin/phpunit tests/Service/
```

## 🚀 Déploiement

### Déploiement avec Docker

```bash
# Construire l'image de production
docker build -t manga:latest .

# Lancer en production
docker-compose -f docker-compose.prod.yml up -d

# Vérifier les logs
docker-compose logs -f backend
```

### Configuration de production

```env
# .env.prod
APP_ENV=prod
APP_DEBUG=false
DATABASE_URL="postgresql://user:pass@host:5432/db"
APP_SECRET="production-secret-key"
```

### Optimisations de production

```bash
# Vider le cache
php bin/console cache:clear --env=prod

# Compiler les assets
php bin/console asset-map:compile --env=prod

# Optimiser Doctrine
php bin/console doctrine:cache:clear-metadata --env=prod
php bin/console doctrine:cache:clear-query --env=prod
```

## 📁 Structure du projet

```
manga/
├── assets/                 # Assets frontend (CSS, JS, Stimulus)
│   ├── controllers/       # Contrôleurs Stimulus
│   ├── styles/           # Fichiers CSS
│   └── scripts/          # JavaScript personnalisé
├── bin/                   # Scripts Symfony
├── config/                # Configuration Symfony
│   ├── packages/         # Configuration des bundles
│   └── routes/           # Définition des routes
├── docker/                # Configuration Docker
│   ├── nginx/            # Configuration Nginx
│   └── php/              # Configuration PHP
├── migrations/            # Migrations de base de données
├── public/                # Fichiers publics
│   └── uploads/          # Images uploadées
├── src/                   # Code source PHP
│   ├── Command/          # Commandes console
│   ├── Controller/       # Contrôleurs
│   │   └── Api/         # API REST
│   ├── Entity/           # Entités Doctrine
│   ├── Form/             # Formulaires
│   ├── Repository/       # Repositories
│   ├── Service/          # Services métier
│   └── Security/         # Sécurité
├── templates/             # Templates Twig
│   ├── admin/           # Interface d'administration
│   ├── partials/        # Partiels réutilisables
│   └── pages/           # Pages principales
├── tests/                 # Tests
│   ├── Controller/       # Tests des contrôleurs
│   ├── Entity/           # Tests des entités
│   └── Service/          # Tests des services
└── var/                   # Fichiers temporaires
    ├── cache/            # Cache Symfony
    └── logs/             # Logs d'application
```

## 🔍 API REST

L'application expose une API REST pour les fonctionnalités principales :

### Endpoints disponibles

- `GET /api/oeuvres` - Liste des œuvres
- `POST /api/oeuvres/{id}/rate` - Noter une œuvre
- `POST /api/oeuvres/{id}/note` - Ajouter une note
- `GET /api/commentaires/{oeuvre_id}` - Commentaires d'une œuvre
- `POST /api/commentaires` - Créer un commentaire
- `POST /api/commentaires/{id}/like` - Liker un commentaire

### Authentification API

L'API utilise l'authentification Symfony standard. Incluez le token CSRF dans vos requêtes.

## 🐛 Dépannage

### Problèmes courants

#### Erreur de base de données
```bash
# Vérifier la connexion
php bin/console doctrine:query:sql "SELECT 1"

# Réinitialiser la base
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

#### Problèmes de cache
```bash
# Vider le cache
php bin/console cache:clear
rm -rf var/cache/*
```

#### Problèmes Docker
```bash
# Reconstruire les images
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

#### Problèmes de permissions
```bash
# Corriger les permissions
chmod -R 777 var/
chmod -R 777 public/uploads/
```

### Logs et debugging

```bash
# Voir les logs Symfony
tail -f var/logs/dev.log

# Logs Docker
docker-compose logs -f backend

# Mode debug
APP_DEBUG=true php bin/console server:start
```

## 🤝 Contribution

### Préparer l'environnement de développement

```bash
# Fork et cloner le projet
git clone https://github.com/votre-fork/manga.git
cd manga

# Installer les dépendances
composer install
npm install

# Configurer l'environnement de développement
cp .env .env.local
# Configurer .env.local

# Préparer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### Standards de code

- **PHP** : PSR-12, PHPStan niveau 8
- **JavaScript** : ESLint, Prettier
- **CSS** : Stylelint
- **Tests** : Couverture minimale 80%

### Processus de contribution

1. **Fork** le projet
2. **Créer** une branche feature (`git checkout -b feature/NouvelleFonctionnalite`)
3. **Développer** avec des tests
4. **Commit** avec des messages clairs (`git commit -m 'feat: ajouter nouvelle fonctionnalité'`)
5. **Push** vers la branche (`git push origin feature/NouvelleFonctionnalite`)
6. **Ouvrir** une Pull Request avec description détaillée

### Tests avant contribution

```bash
# Lancer tous les tests
php bin/phpunit

# Vérifier la qualité du code
vendor/bin/phpstan analyse src --level=8
vendor/bin/php-cs-fixer fix --dry-run --diff

# Tests de sécurité
composer audit
```

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👨‍💻 Auteur

**Votre Nom** - [GitHub](https://github.com/votre-username)

Projet développé dans le cadre du titre RNCP 39583 "Développeur d'Applications" chez Ynov.

## 🙏 Remerciements

- **Symfony** pour le framework exceptionnel
- **Doctrine** pour l'ORM puissant
- **Bootstrap** pour l'interface utilisateur
- **Docker** pour la conteneurisation
- **Ynov** pour la formation et l'accompagnement

---

⭐ Si ce projet vous plaît, n'hésitez pas à le star sur GitHub !

📧 **Support** : Ouvrez une issue sur GitHub pour toute question ou problème. 