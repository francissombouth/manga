# PLAN DE GESTION DE PROJET - MANGATHÈQUE
## Application Web de Gestion de Collection de Mangas

---

## 1. ORGANISATION EN SPRINTS

### 1.1 Planning des Sprints

#### **Sprint 1 (Semaines 1-2) : Fondations**
**Objectif** : Mise en place de l'environnement et architecture de base
**Jalons** :
- [ ] Environnement Docker fonctionnel
- [ ] Structure Symfony 7.3 configurée
- [ ] Base de données PostgreSQL initialisée
- [ ] Système d'authentification de base

#### **Sprint 2 (Semaines 3-4) : Entités Core**
**Objectif** : Développement des entités principales
**Jalons** :
- [ ] Entités User, Oeuvre, Auteur, Tag créées
- [ ] Relations Doctrine configurées
- [ ] Interface admin fonctionnelle
- [ ] Validation des formulaires

#### **Sprint 3 (Semaines 5-6) : Collections et Relations**
**Objectif** : Gestion des collections utilisateur
**Jalons** :
- [ ] Entité CollectionUser et Statut
- [ ] Collections personnelles fonctionnelles
- [ ] Statuts de lecture (À lire, En cours, Terminé, Abandonné)
- [ ] Recherche basique

#### **Sprint 4 (Semaines 7-8) : API MangaDex**
**Objectif** : Intégration de l'API externe
**Jalons** :
- [ ] Service MangaDex configuré
- [ ] Import automatique d'œuvres
- [ ] Gestion des erreurs API
- [ ] Cache des données importées

#### **Sprint 5 (Semaines 9-10) : Fonctionnalités Sociales**
**Objectif** : Commentaires et interactions
**Jalons** :
- [ ] Entités Commentaire, CommentaireLike, OeuvreNote
- [ ] Système de commentaires publics
- [ ] Système de likes sur commentaires
- [ ] Notation et évaluation des œuvres

#### **Sprint 6 (Semaines 11-12) : Interface Utilisateur**
**Objectif** : Amélioration de l'expérience utilisateur
**Jalons** :
- [ ] Templates Twig responsives
- [ ] JavaScript Stimulus.js intégré
- [ ] Navigation fluide avec Turbo.js
- [ ] Design Bootstrap finalisé

#### **Sprint 7 (Semaines 13-14) : Tests et Optimisation**
**Objectif** : Qualité et performance
**Jalons** :
- [ ] Tests unitaires PHPUnit (> 80% couverture)
- [ ] Tests d'intégration
- [ ] Optimisation des requêtes Doctrine
- [ ] Audit de sécurité

#### **Sprint 8 (Semaines 15-16) : Déploiement**
**Objectif** : Mise en production
**Jalons** :
- [ ] Configuration Render.com avec PostgreSQL intégré
- [ ] Déploiement automatique GitHub Actions (CI/CD)
- [ ] Monitoring et alertes (disponibilité 99%)
- [ ] Documentation complète et procédures PCA/PCRA

### 1.2 Jalons Majeurs
- **Jalon 1** (Fin Sprint 2) : MVP avec entités core et authentification
- **Jalon 2** (Fin Sprint 4) : Import MangaDex API intégré et fonctionnel
- **Jalon 3** (Fin Sprint 6) : Interface utilisateur complète et responsive
- **Jalon 4** (Fin Sprint 8) : Application déployée en production sur Render.com

---

## 2. ATTRIBUTION DES RÔLES ET RESPONSABILITÉS

### 2.1 Rôles

#### **Développeur Full-Stack**
**Responsable** : Développeur solo
**Responsabilités** :
- Développement backend (PHP 8.2+/Symfony 7.3)
- Développement frontend (Twig 3.0/Stimulus.js)
- Tests unitaires et d'intégration (PHPUnit)
- Configuration des environnements (Docker)
- Documentation technique et utilisateur

#### **Product Owner**
**Responsable** : Développeur solo
**Responsabilités** :
- Définition des priorités fonctionnelles
- Validation des livrables et critères d'acceptation
- Gestion du backlog produit
- Communication avec les parties prenantes (encadrant académique)

---

## 3. OUTILS DE SUIVI UTILISÉS

### 3.1 Gestion de Projet

#### **GitHub Projects**
**Utilisation** : Gestion des tâches et sprints
**Configuration** :
- Board Kanban avec colonnes : Backlog, En cours, Review, Terminé
- Labels : Bug, Feature, Documentation, Test, API, Frontend, Backend
- Milestones : Par sprint (Sprint 1 à Sprint 8)
- Assignees : Développeur solo

#### **GitHub Issues**
**Utilisation** : Suivi des bugs et demandes
**Configuration** :
- Templates pour bugs et features
- Labels automatiques (Symfony, PHP, Twig, JavaScript, Docker)
- Liens avec les commits et pull requests
- Estimation de temps par tâche

### 3.2 Gestion du Code

#### **Git (GitHub)**
**Utilisation** : Versioning et collaboration
**Configuration** :
- Branches : main, develop, feature/*, hotfix/*
- Commits conventionnels (feat:, fix:, docs:, style:, refactor:)
- Pull requests obligatoires avec review
- Protection de la branche main

#### **GitHub Actions**
**Utilisation** : CI/CD automatisé
**Configuration** :
- Tests automatiques à chaque push (PHPUnit, PHPStan)
- Build et déploiement automatique sur Render.com
- Analyse de qualité du code
- Notifications Slack/Email

### 3.3 Qualité du Code

#### **PHPUnit**
**Utilisation** : Tests unitaires et d'intégration
**Configuration** :
- Couverture de code > 80%
- Tests automatisés pour entités, services, controllers
- Intégration CI/CD avec GitHub Actions
- Tests de base de données avec fixtures

#### **PHPStan**
**Utilisation** : Analyse statique du code
**Configuration** :
- Niveau 8 (maximum)
- Intégration CI/CD avec GitHub Actions
- Analyse des erreurs de type et de logique
- Configuration personnalisée pour Symfony

### 3.4 Outils de Développement

#### **Docker & Docker Compose**
**Utilisation** : Environnements de développement
**Configuration** :
- Environnement reproductible avec PHP 8.2, PostgreSQL 16, Nginx
- Services : Backend Symfony, Base de données, Serveur web
- Volumes persistants pour les données
- Scripts d'initialisation automatique

#### **Composer**
**Utilisation** : Gestion des dépendances PHP
**Configuration** :
- Dépendances de production et développement
- Scripts automatiques (post-install, post-update)
- Optimisation autoloader pour production
- Gestion des packages Symfony

---

**Document créé le :** 2025-01-27  
**Version :** 1.0  
**Statut :** Plan de gestion initial validé

