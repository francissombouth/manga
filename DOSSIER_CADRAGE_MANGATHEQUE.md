# DOSSIER DE CADRAGE - MANGATHÈQUE
## Application Web de Gestion de Collection de Mangas

---

## INFORMATIONS GÉNÉRALES

| **Propriété** | **Valeur** |
|---------------|------------|
| **Nom du projet** | MangaThèque |
| **Version** | 2.0 (Production Ready) |
| **Date de création** | 2025-01-27 |
| **Responsable** | Développeur solo |
| **Statut** | Cadrage technique validé |
| **Licence** | MIT |

---

## 1. ÉTUDE TECHNIQUE

### 1.1 Stack Technologique Choisie

#### 1.1.1 Backend

**Framework : Symfony 7.3**
- **Justification** : Contrainte académique imposée
- **Avantages** : Framework mature, écosystème riche

**Langage : PHP 8.2+**
- **Justification** : Contrainte académique imposée
- **Avantages** : Langage web par excellence, large communauté

**ORM : Doctrine 3.4**
- **Justification** : Intégration native avec Symfony
- **Avantages** : Abstraction base de données, migrations automatiques

**Base de Données : PostgreSQL 16**
- **Justification** : Robustesse, fonctionnalités avancées
- **Avantages** : ACID, JSON natif, performances élevées

#### 1.1.2 Frontend

**Templates : Twig 3.0**
- **Justification** : Moteur de template par défaut de Symfony
- **Avantages** : Sécurité, performance, syntaxe claire

**JavaScript : ES6+ avec Stimulus.js**
- **Justification** : Framework léger, intégration Symfony
- **Avantages** : Simplicité, pas de build complexe

**CSS : AssetMapper + Bootstrap**
- **Justification** : Intégration native Symfony, composants prêts
- **Avantages** : Rapidité de développement, responsive

#### 1.1.3 Infrastructure

**Environnement de Développement : Docker & Docker Compose**
- **Justification** : Standardisation des environnements de développement
- **Avantages** : Reproducibilité, isolation, facilité de développement

**Serveur Web : Nginx**
- **Justification** : Performance, configuration flexible
- **Avantages** : Haute performance, faible consommation mémoire

**CI/CD : GitHub Actions**
- **Justification** : Intégration native avec GitHub
- **Avantages** : Gratuit, facile à configurer

**Hébergement : Render.com**
- **Justification** : Plateforme cloud moderne, intégration GitHub
- **Avantages** : Déploiement automatique, base de données PostgreSQL incluse, serveur web intégré

### 1.2 Architecture Technique

#### 1.2.1 Architecture en Couches

**Présentation (Controllers)** : Gestion des requêtes HTTP, validation des données
**Logique Métier (Services)** : Centralisation de la logique applicative
**Accès aux Données (Repositories)** : Abstraction de la couche de données
**Entités (Domain)** : Modèles de données, validation métier

#### 1.2.2 Patterns de Conception

**Repository Pattern** : Abstraction de l'accès aux données
**Service Layer Pattern** : Centralisation de la logique métier
**Value Objects** : Validation centralisée, immutabilité des données

### 1.3 Modèle de Données

**Entités principales** : Utilisateurs, Œuvres, Auteurs, Tags, Chapitres
**Relations** : Collections personnelles, commentaires, notes, statuts de lecture
**Contraintes** : Authentification, validation, intégrité référentielle
**Fonctionnalités** : Import MangaDex, collections personnelles, système de statuts


---

## 2. CONTRAINTES ET RISQUES IDENTIFIÉS

### 2.1 Contraintes Techniques

#### 2.1.1 Contraintes Académiques
- **Framework imposé** : Symfony 7.3 obligatoire
- **Langage imposé** : PHP 8.2+ obligatoire
- **Durée limitée** : 16 semaines maximum
- **Équipe réduite** : 1 développeur uniquement

#### 2.1.2 Contraintes de Performance
- **Temps de réponse** : < 2 secondes par page
- **Temps de chargement** : < 3 secondes
- **Disponibilité** : 99% minimum (selon PCA/PCRA)
- **Erreurs 500** : < 0.1%

#### 2.1.3 Contraintes de Sécurité
- **Authentification** : Système sécurisé obligatoire
- **Protection CSRF** : Obligatoire sur tous les formulaires
- **Validation** : Validation stricte des données
- **Hachage** : Mots de passe hachés avec bcrypt

#### 2.1.4 Contraintes de Continuité (PCA/PCRA)
- **Temps de rétablissement maximal** : 4 heures
- **Perte de données maximale** : 24 heures
- **Objectif de disponibilité** : 99% (~7h d'arrêt/mois tolérés)
- **Sauvegarde automatique** : Quotidienne

### 2.2 Contraintes Fonctionnelles

#### 2.2.1 Contraintes Métier
- **Import MangaDex** : Intégration API obligatoire
- **Fonctionnalités sociales** : Commentaires et likes obligatoires
- **Interface responsive** : Adaptation mobile/desktop obligatoire
- **Gestion des collections** : CRUD complet obligatoire

#### 2.2.2 Contraintes Utilisateur
- **Temps d'apprentissage** : < 10 minutes
- **Taux de satisfaction** : > 4/5
- **Accessibilité** : Standards WCAG 2.1 AA
- **Internationalisation** : Support multilingue (futur)

### 2.3 Contraintes de Ressources

#### 2.3.1 Ressources Humaines
- **Équipe** : 1 développeur full-stack
- **Expertise** : PHP/Symfony, JavaScript, CSS
- **Disponibilité** : 100% sur le projet
- **Formation** : Auto-formation si nécessaire

#### 2.3.2 Ressources Matérielles
- **Serveur de développement** : Machine locale avec Docker
- **Base de données** : PostgreSQL local (dev) / Render.com (prod)
- **Outils de développement** : IDE, Git, Docker
- **Budget** : 0€ (projet académique)

### 2.4 Risques Techniques (PCA/PCRA)

#### 2.4.1 Risques d'Infrastructure
- **Panne Render.com** : Probabilité moyenne, gravité élevée, RTO 4h
- **Perte de Base de Données** : Probabilité faible, gravité très élevée, RPO 24h

#### 2.4.2 Risques de Performance
- **Lenteur application** : Probabilité moyenne, impact élevé
- **Mitigation** : Optimisation requêtes, cache, indexation

#### 2.4.3 Risques de Sécurité
- **Vulnérabilités** : Probabilité faible, impact élevé
- **Mitigation** : Audit sécurité, validation stricte, protection CSRF

#### 2.4.4 Risques d'API MangaDex
- **Indisponibilité API** : Probabilité faible, impact moyen
- **Mitigation** : Cache local, fallback manuel

#### 2.4.5 Risques de Stockage
- **Perte d'Images** : Probabilité faible, gravité moyenne, RTO 2-4h

### 2.5 Risques de Projet

#### 2.5.1 Risques de Planning
- **Retard développement** : Probabilité moyenne, impact élevé
- **Mitigation** : Buffer 20%, priorisation, adaptation continue

#### 2.5.2 Risques de Qualité
- **Bugs critiques** : Probabilité faible, impact moyen
- **Mitigation** : Tests automatisés, revues de code

#### 2.5.3 Risques de Compétence
- **Manque expertise** : Probabilité faible, impact moyen
- **Mitigation** : Auto-formation, documentation, communauté

### 2.6 Risques Opérationnels (PCA/PCRA)

#### 2.6.1 Risques de Déploiement
- **Problèmes déploiement** : Probabilité faible, impact moyen, RTO 30min-2h
- **Mitigation** : Tests staging, rollback automatique

#### 2.6.2 Risques de Maintenance
- **Difficultés maintenance** : Probabilité faible, impact faible
- **Mitigation** : Documentation complète, code commenté

#### 2.6.3 Risques de Code Source
- **Perte GitHub** : Probabilité très faible, gravité critique, RTO 1-2h
- **Mitigation** : Sauvegarde locale, mirror GitLab

#### 2.6.4 Risques de Configuration
- **Perte Variables d'Environnement** : Probabilité faible, gravité importante, RTO 1-3h
- **Mitigation** : Export chiffré, gestionnaire de secrets

---

## 3. PLAN D'ATTÉNUATION DES RISQUES (PCA/PCRA)

### 3.1 Stratégies de Mitigation

#### 3.1.1 Mitigation des Risques d'Infrastructure (PCA/PCRA)

**Sauvegardes Automatisées**
- **Code Source** : GitHub (push à chaque modification)
- **Base de Données** : Backup quotidien automatique Render (7 jours)
- **Images** : Sauvegarde des uploads dans le stockage Render
- **Configuration** : Export chiffré des variables d'environnement

**Monitoring et Alertes**
- **Logs Render** : Surveillance des erreurs
- **GitHub Actions** : Tests automatisés et déploiement

**Procédures de Restauration**
- **Bug Applicatif** : 30 min - 2h
- **Perte BDD** : 1-3h (restauration backup Render)
- **Perte Images** : 1-4h (restauration depuis stockage Render)
- **Perte Totale** : 4-8h

#### 3.1.2 Mitigation des Risques Techniques

**Performance** : Optimisation continue des requêtes, monitoring
**Sécurité** : Audit sécurité automatisé, tests de sécurité
**API MangaDex** : Cache local, fallback manuel, monitoring

#### 3.1.2 Mitigation des Risques de Projet

**Planning** : Buffer de 20% sur chaque phase, planning flexible
**Qualité** : Tests automatisés (100% couverture), PHPUnit, PHPStan
**Compétence** : Auto-formation continue, documentation, communauté

#### 3.1.3 Mitigation des Risques Opérationnels

**Déploiement** : Environnement de staging, Render.com staging
**Maintenance** : Documentation complète, README, documentation API

### 3.2 Plan de Contingence (PCA/PCRA)

#### 3.2.1 Scénarios de Contingence

**Incident Majeur (> 2h)** : Activation procédures PCA/PCRA, communication utilisateurs
**Retard Technique Majeur** : Réduction du scope fonctionnel, fonctionnalités core en priorité
**Problème Critique de Sécurité** : Correction immédiate, déploiement d'urgence
**Indisponibilité API MangaDex** : Activation du mode fallback manuel
**Perte Totale d'Infrastructure** : Migration vers VPS, RTO 4-8h



### 3.3 Monitoring et Suivi (PCA/PCRA)

#### 3.3.1 Indicateurs de Suivi

**Indicateurs Techniques** : Temps de réponse, taux d'erreur 500, couverture de tests
**Indicateurs de Projet** : Pourcentage d'avancement, tâches en retard, qualité du code
**Indicateurs Opérationnels (PCA/PCRA)** : Disponibilité 99%, RTO < 4h, RPO < 24h, satisfaction > 4/5
**Indicateurs de Continuité** : GitHub Actions, backup status, tests de restauration mensuels

#### 3.3.2 Reporting

**Rapport Quotidien** : Tâches terminées, problèmes rencontrés, status PCA/PCRA
**Rapport Hebdomadaire** : Avancement global, risques identifiés, tests PCA/PCRA
**Rapport Mensuel** : Bilan complet, évolution des risques, audit PCA/PCRA
**Rapport Trimestriel (PCA/PCRA)** : Simulation panne complète, validation plan de continuité

