# Documentation Docker - Projet Manga

## 📋 Vue d'ensemble

Ce projet utilise Docker pour créer un environnement de développement et de production complet avec :

- **Backend** : Symfony 7.3 avec PHP 8.2, Nginx
- **Frontend** : React 19 avec Material-UI
- **Base de données** : PostgreSQL 16
- **Mailer** : Mailpit pour le développement

## 🏗️ Architecture

```
📦 Projet Manga
├── 🐳 Backend (Port 8000)
│   ├── PHP 8.2 + Symfony 7.3
│   ├── Nginx
│   └── Supervisor
├── ⚛️ Frontend (Port 3000)
│   ├── React 19
│   └── Nginx
├── 🐘 PostgreSQL (Port 5432)
└── 📧 Mailpit (Ports 1025/8025)
```

## 🚀 Installation et démarrage

### 1. Configuration des variables d'environnement

Copiez le fichier de configuration :

```bash
cp docker/environment.txt .env
```

Modifiez le fichier `.env` avec vos paramètres :

```env
# Changez ces valeurs en production
APP_SECRET=votre-clé-secrète-symfony
POSTGRES_DB=manga_db
POSTGRES_USER=manga_user
POSTGRES_PASSWORD=votre-mot-de-passe-securisé
```

### 2. Démarrage des services

```bash
# Construire et démarrer tous les services
docker-compose up -d --build

# Ou démarrer sans rebuild
docker-compose up -d
```

### 3. Initialisation de la base de données

```bash
# Créer la base de données
docker-compose exec backend php bin/console doctrine:database:create

# Exécuter les migrations
docker-compose exec backend php bin/console doctrine:migrations:migrate

# Charger les données de test (optionnel)
docker-compose exec backend php bin/console doctrine:fixtures:load
```

## 🌐 Accès aux services

| Service | URL | Description |
|---------|-----|-------------|
| **Frontend** | http://localhost:3000 | Application React |
| **Backend** | http://localhost:8000 | API Symfony |
| **Mailpit** | http://localhost:8025 | Interface web du mailer |
| **PostgreSQL** | localhost:5432 | Base de données |

## 📊 Gestion des services

### Commandes utiles

```bash
# Voir les logs
docker-compose logs -f

# Redémarrer un service
docker-compose restart backend

# Arrêter tous les services
docker-compose down

# Arrêter et supprimer les volumes
docker-compose down -v

# Reconstruire un service
docker-compose build backend

# Accéder au shell d'un conteneur
docker-compose exec backend bash
docker-compose exec frontend sh
```

### Commandes Symfony dans le conteneur

```bash
# Console Symfony
docker-compose exec backend php bin/console

# Clear cache
docker-compose exec backend php bin/console cache:clear

# Créer une migration
docker-compose exec backend php bin/console make:migration

# Installer des dépendances
docker-compose exec backend composer install
```

### Commandes React dans le conteneur

```bash
# Installer des dépendances
docker-compose exec frontend npm install

# Rebuild l'application
docker-compose exec frontend npm run build
```

## 🔧 Développement

### Mode développement

Pour le développement, vous pouvez utiliser les volumes pour le hot reload :

```yaml
# Ajoutez dans compose.override.yaml
services:
  backend:
    volumes:
      - .:/var/www/html:rw
      - ./var/cache:/var/www/html/var/cache:rw
      - ./var/log:/var/www/html/var/log:rw
    environment:
      - APP_ENV=dev
      - APP_DEBUG=1
```

### Debugging

```bash
# Voir les logs d'un service spécifique
docker-compose logs -f backend
docker-compose logs -f frontend
docker-compose logs -f database

# Inspecter un conteneur
docker-compose exec backend bash
docker-compose exec frontend sh
```

## 🔐 Sécurité

### Variables d'environnement sensibles

En production, modifiez ces valeurs :

```env
APP_SECRET=générez-une-clé-secrète-forte
POSTGRES_PASSWORD=mot-de-passe-fort
JWT_PASSPHRASE=passphrase-jwt-sécurisée
```

### Ports en production

Modifiez les ports exposés en production :

```yaml
services:
  backend:
    ports:
      - "80:80"  # Au lieu de 8000:80
  
  frontend:
    ports:
      - "443:80"  # Avec SSL
```

## 📦 Builds optimisés

Les Dockerfiles utilisent le multi-stage build pour optimiser les tailles des images :

- **Backend** : Build stage séparé pour les dépendances
- **Frontend** : Build React puis nginx pour servir les assets statiques

## 🐛 Dépannage

### Problèmes courants

1. **Port déjà utilisé** :
```bash
# Vérifier les ports
netstat -tulpn | grep :8000
# Changer le port dans compose.yaml
```

2. **Erreur de permissions** :
```bash
# Corriger les permissions
docker-compose exec backend chown -R www-data:www-data /var/www/html/var
```

3. **Base de données non accessible** :
```bash
# Vérifier le statut de la base
docker-compose exec database pg_isready -U manga_user
```

4. **Rebuild complet** :
```bash
# Nettoyer et reconstruire
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

## 🚀 Déploiement

### Production

1. Utilisez les variables d'environnement sécurisées
2. Configurez un reverse proxy (Nginx/Apache)
3. Utilisez des volumes persistants pour les données
4. Configurez la sauvegarde de la base de données
5. Utilisez HTTPS avec Let's Encrypt

### Exemple de configuration Nginx pour production

```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    
    location / {
        proxy_pass http://localhost:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
    
    location /api {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## 📚 Ressources supplémentaires

- [Documentation Docker Compose](https://docs.docker.com/compose/)
- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Documentation React](https://react.dev/)

## 🤝 Contribution

Pour contribuer au projet :

1. Forkez le projet
2. Créez une branche pour votre feature
3. Testez avec Docker
4. Soumettez une Pull Request

---

*Cette documentation a été générée pour faciliter l'utilisation de Docker avec le projet Manga.* 