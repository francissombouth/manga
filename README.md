# 📚 MangaThèque - Bibliothèque de Mangas

Une application web moderne pour gérer et lire vos mangas favoris, développée avec Symfony et intégrée à l'API MangaDx.

## 🚀 Fonctionnalités

- 📖 **Catalogue de 100 mangas populaires** depuis MangaDx
- ⭐ **Système de favoris** pour retrouver facilement vos mangas préférés
- 📄 **Lecture en ligne** des chapitres avec navigation intuitive
- 👤 **Système d'authentification** avec gestion des utilisateurs
- 🔧 **Interface d'administration** pour la gestion du contenu
- 🖼️ **Proxy d'images** pour l'affichage sécurisé des couvertures
- 🌐 **API REST** complète pour les intégrations externes

## 📋 Prérequis

- **PHP 8.2+** avec les extensions :
  - `curl`, `json`, `mbstring`, `xml`, `zip`, `gd`
- **Composer** (gestionnaire de dépendances PHP)
- **MySQL/MariaDB** ou **SQLite**
- **Symfony CLI** (recommandé)

## 🛠️ Installation

### 1. Cloner le projet
```bash
git clone <url-du-repo>
cd biblio
```

### 2. Installer les dépendances
```bash
composer install
```

### 3. Configuration de la base de données
Créez le fichier `.env.local` et configurez votre base de données :
```env
# Base de données MySQL
DATABASE_URL="mysql://username:password@127.0.0.1:3306/mangatheque"

# Ou SQLite pour un développement simple
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

### 4. Créer la base de données et les tables
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Charger les données de test (optionnel)
```bash
php bin/console doctrine:fixtures:load
```

## 🚀 Lancement du serveur

### Méthode 1 : Symfony CLI (recommandée)
```bash
symfony server:start
```
L'application sera accessible à : **https://127.0.0.1:8000**

### Méthode 2 : Serveur PHP intégré
```bash
php -S localhost:8000 -t public/
```
L'application sera accessible à : **http://localhost:8000**

### Mode daemon (arrière-plan)
```bash
symfony server:start -d
```

### Arrêter le serveur
```bash
symfony server:stop
```

## 📚 Import du catalogue

### Import des 100 mangas populaires
```bash
php bin/console app:import-simple-100
```
Cette commande :
- Vide la base de données actuelle
- Importe les 100 mangas les plus populaires de MangaDx
- Les ajoute automatiquement à vos favoris

### Autres commandes utiles
```bash
# Gérer vos favoris
php bin/console app:manage-collection --user-id=36 --stats

# Lister les utilisateurs
php bin/console app:list-users

# Compter les mangas disponibles sur MangaDx
php bin/console app:count-mangas

# Debug des couvertures
php bin/console app:debug-covers
```

## 👤 Comptes utilisateur

### Compte administrateur par défaut
- **Email :** admin@mangabook.com
- **Mot de passe :** admin123

### Compte utilisateur de test
- **Email :** test@gmail.com  
- **Mot de passe :** password123

### Créer un nouveau compte
Rendez-vous sur : `/register`

## 🗂️ Structure de l'application

### Pages principales
- **🏠 Accueil :** `/` - Page d'accueil
- **📚 Catalogue :** `/mangadx/` - Vos 100 mangas populaires
- **🔍 Recherche :** `/mangadx/search` - Rechercher dans le catalogue
- **⭐ Favoris :** `/oeuvres` - Vos mangas favoris
- **🔧 Administration :** `/admin` - Interface d'administration

### API REST
- **Documentation :** `/api/docs` - Swagger UI
- **Endpoint :** `/api/` - API RESTful complète

## 🔧 Administration

L'interface d'administration permet de :
- Gérer les œuvres et leurs métadonnées
- Gérer les chapitres et leurs pages
- Voir les statistiques d'utilisation
- Gérer les utilisateurs

Accès : **https://127.0.0.1:8000/admin** (compte admin requis)

## 🎯 Utilisation

### 1. Découvrir le catalogue
- Visitez `/mangadx/` pour voir vos 100 mangas populaires
- Utilisez la pagination pour naviguer dans le catalogue
- Cliquez sur un manga pour voir ses détails et chapitres

### 2. Lire un manga
- Sur la page d'un manga, cliquez sur "📖 Lire" pour un chapitre
- Utilisez les boutons de navigation pour passer aux chapitres suivants/précédents
- Les images sont chargées automatiquement via le proxy sécurisé

### 3. Gérer vos favoris
- Depuis la page d'un manga, cliquez sur "⭐ Ajouter aux favoris"
- Consultez tous vos favoris sur `/oeuvres`
- Les favoris sont synchronisés avec votre catalogue

### 4. Rechercher
- Utilisez la barre de recherche sur `/mangadx/search`
- Recherchez par titre de manga
- Les résultats sont récupérés en temps réel depuis MangaDx

## 🛠️ Développement

### Vider le cache
```bash
php bin/console cache:clear
```

### Voir les logs
```bash
# Windows PowerShell
Get-Content var/log/dev.log -Tail 50 -Wait

# Linux/Mac
tail -f var/log/dev.log
```

### Migrations de base de données
```bash
# Créer une nouvelle migration
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate
```

### Tests
```bash
# Lancer les tests
php bin/phpunit

# Lancer un test spécifique
php bin/console app:debug-covers
```

## 🔒 Sécurité

- **CSRF Protection** : Activé sur tous les formulaires
- **Rate Limiting** : Implémenté pour les appels API
- **Proxy d'images** : Les images MangaDx passent par un proxy sécurisé
- **Authentification** : Système de connexion sécurisé
- **Validation** : Toutes les données sont validées côté serveur

## 🌐 APIs externes

### MangaDx API
- **URL :** https://api.mangadx.org
- **Documentation :** https://api.mangadx.org/docs/
- **Rate limits :** Respectés automatiquement par l'application
- **Langues supportées :** Français et Anglais

## 📁 Architecture

```
biblio/
├── src/
│   ├── Controller/       # Contrôleurs Symfony
│   ├── Entity/          # Entités Doctrine
│   ├── Repository/      # Repositories Doctrine  
│   ├── Service/         # Services métier
│   ├── Command/         # Commandes console
│   └── Security/        # Configuration sécurité
├── templates/           # Templates Twig
├── public/             # Assets publics
├── migrations/         # Migrations DB
├── var/               # Cache et logs
└── config/            # Configuration Symfony
```

## 🐛 Dépannage

### Les images ne s'affichent pas
1. Vérifiez que le serveur est bien démarré
2. Testez le proxy : `/proxy/image`
3. Vérifiez les logs d'erreur

### Erreur de base de données
1. Vérifiez la configuration dans `.env.local`
2. Recréez la base : `php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create`
3. Réappliquez les migrations : `php bin/console doctrine:migrations:migrate`

### Problèmes d'authentification
1. Videz le cache : `php bin/console cache:clear`
2. Vérifiez la configuration CSRF dans `config/packages/csrf.yaml`

### Import échoue
1. Vérifiez votre connexion internet
2. Les serveurs MangaDx peuvent être temporairement indisponibles
3. Réessayez plus tard : `php bin/console app:import-simple-100`

## 📝 Changelog

### Version actuelle
- ✅ Catalogue de 100 mangas populaires
- ✅ Système de favoris unifié
- ✅ Liens cohérents entre administration et catalogue
- ✅ Proxy d'images fonctionnel
- ✅ Interface d'administration complète

## 📄 Licence

Ce projet est un projet éducatif. Veuillez respecter les conditions d'utilisation de l'API MangaDx.

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Créer une Pull Request

---

**Bon développement ! 🚀** 