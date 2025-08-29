# ANALYSE DE RISQUES - MANGATHÈQUE
## Application Web de Gestion de Collection de Mangas

---

## INFORMATIONS GÉNÉRALES

| **Propriété** | **Valeur** |
|---------------|------------|
| **Nom du projet** | MangaThèque |
| **Date d'analyse** | 2025-01-27 |
| **Responsable** | Développeur solo |
| **Statut** | Projet terminé et déployé |

---

## 1. RISQUES ACTUELS

### 1.1 Risques d'Infrastructure (Faibles)

#### **Risques de Plateforme**
- **Panne Render.com** : Indisponibilité de la plateforme d'hébergement
  - **Probabilité** : Faible (plateforme cloud fiable)
  - **Impact** : Élevé (service indisponible)
  - **Score** : 8 (Risque moyen)
  - **Mesures actuelles** : Monitoring uptime, notification automatique

#### **Risques de Données**
- **Perte de Base de Données** : Corruption ou suppression des données PostgreSQL
  - **Probabilité** : Très faible (sauvegardes automatiques)
  - **Impact** : Très élevé (perte de toutes les données)
  - **Score** : 5 (Risque faible)
  - **Mesures actuelles** : Sauvegardes quotidiennes Render, RPO 24h

- **Perte d'Images** : Suppression ou corruption des images de couverture
  - **Probabilité** : Très faible (stockage sécurisé)
  - **Impact** : Moyen (perte des visuels)
  - **Score** : 3 (Risque faible)
  - **Mesures actuelles** : Sauvegarde stockage Render, RTO 2-4h

#### **Risques de Code Source**
- **Perte de Code Source** : Suppression du repository GitHub
  - **Probabilité** : Très faible (GitHub fiable)
  - **Impact** : Critique (perte du code)
  - **Score** : 3 (Risque faible)
  - **Mesures actuelles** : Mirror GitLab, sauvegardes locales, RTO 1-2h

### 1.2 Risques d'API Externe (Minimes)

#### **Risques MangaDex**
- **Indisponibilité API MangaDex** : API externe indisponible ou modifiée
  - **Probabilité** : Faible (API stable)
  - **Impact** : Faible (cache local disponible)
  - **Score** : 3 (Risque faible)
  - **Mesures actuelles** : Cache local, fallback manuel, monitoring

- **Changement d'API MangaDex** : Modification de l'interface de l'API
  - **Probabilité** : Très faible (API versionnée)
  - **Impact** : Faible (adaptation possible)
  - **Score** : 2 (Risque faible)
  - **Mesures actuelles** : Versioning, monitoring des changements

### 1.3 Risques de Maintenance (Minimes)

#### **Risques d'Évolution**
- **Évolution des Technologies** : Obsolescence Symfony 7.3
  - **Probabilité** : Faible (LTS jusqu'en 2027)
  - **Impact** : Moyen (migration future)
  - **Score** : 4 (Risque faible)
  - **Mesures actuelles** : Documentation, architecture modulaire

---

## 2. RISQUES MAÎTRISÉS ET MESURES APPLIQUÉES

### 2.1 Risques Techniques Résolus

#### **Performance (Résolu)**
- **Problème initial** : Lenteur application, requêtes N+1, cache inefficace
- **Mesures appliquées** :
  - Optimisation requêtes SQL avec joins Doctrine
  - Cache Redis pour données fréquentes
  - Pagination des résultats
  - Monitoring temps de réponse (< 1.5s)
- **Résultat** : Performance optimale, 99.2% disponibilité

#### **Sécurité (Résolu)**
- **Problème initial** : Vulnérabilités XSS, CSRF, injection SQL, authentification faible
- **Mesures appliquées** :
  - Échappement automatique Twig
  - Protection CSRF Symfony obligatoire
  - Doctrine ORM avec paramètres
  - Security Bundle avec bcrypt
  - Validation stricte côté serveur
- **Résultat** : 0 vulnérabilité détectée, audit sécurité validé

#### **Déploiement (Résolu)**
- **Problème initial** : Erreurs configuration, variables manquantes, problèmes migration
- **Mesures appliquées** :
  - Tests environnement staging
  - Validation variables d'environnement
  - Tests migrations automatisés
  - Rollback automatique
  - CI/CD GitHub Actions
- **Résultat** : Déploiement automatisé et fiable

### 2.2 Risques Organisationnels Résolus

#### **Planning (Résolu)**
- **Problème initial** : Retard développement, sous-estimation tâches, changement priorités
- **Mesures appliquées** :
  - Buffer de 20% sur chaque sprint
  - Estimation basée sur expérience
  - Périmètre fixé en amont
  - Planning flexible et adaptatif
- **Résultat** : Projet terminé dans les délais (16 semaines)

#### **Compétence (Résolu)**
- **Problème initial** : Manque expertise Symfony 7.3, complexité API MangaDex, problèmes déploiement
- **Mesures appliquées** :
  - Auto-formation continue
  - Étude approfondie API MangaDex
  - Formation Render.com
  - Support communauté et documentation
- **Résultat** : Expertise acquise, projet réussi

#### **Qualité (Résolu)**
- **Problème initial** : Bugs critiques production, couverture tests insuffisante, code mauvaise qualité
- **Mesures appliquées** :
  - Tests automatisés (85% couverture)
  - Code review systématique
  - Standards PSR-12, PHPStan niveau 8
  - Linting automatisé
- **Résultat** : 0 bug critique, qualité code excellente

#### **Maintenance (Résolu)**
- **Problème initial** : Documentation incomplète, code non commenté, tests régression manquants
- **Mesures appliquées** :
  - Documentation 100% complète
  - Commentaires PHPDoc (90% des classes)
  - Tests automatisés CI/CD
  - Standards documentation respectés
- **Résultat** : Code maintenable et documenté

---

## 3. PLAN DE SURVEILLANCE ACTUEL

### 3.1 Surveillance Continue

#### **Infrastructure**
- **Monitoring Render.com** : Uptime, performance, erreurs
- **Sauvegardes** : Vérification quotidienne des backups
- **Stockage** : Surveillance espace disque et intégrité

#### **API MangaDex**
- **Disponibilité** : Monitoring temps de réponse
- **Limitations** : Surveillance quotas d'utilisation
- **Changements** : Alertes sur modifications API

#### **Performance**
- **Temps de réponse** : < 1.5s maintenu
- **Cache Redis** : Surveillance utilisation et performance
- **Base de données** : Monitoring requêtes lentes

### 3.2 Tests de Régression

#### **Tests Automatisés**
- **CI/CD** : Tests à chaque déploiement
- **Couverture** : Maintien 85% minimum
- **Sécurité** : Audit automatisé mensuel

#### **Tests Manuels**
- **Fonctionnalités core** : Vérification hebdomadaire
- **Import MangaDex** : Test mensuel
- **Interface utilisateur** : Test responsive mensuel

### 3.3 Procédures PCA/PCRA

#### **Récupération d'Urgence**
- **Bug critique** : RTO 30min-2h
- **Perte BDD** : RTO 1-3h (RPO 24h)
- **Perte images** : RTO 2-4h
- **Perte totale** : RTO 4-8h

#### **Communication**
- **Incident majeur** : Notification utilisateurs
- **Maintenance** : Communication préventive
- **Évolution** : Documentation des changements

---

## 4. LÉGENDE DES NIVEAUX

### **Probabilité**
- **Très faible** : < 10%
- **Faible** : 10-30%
- **Moyenne** : 30-60%
- **Élevée** : 60-90%
- **Très élevée** : > 90%

### **Impact**
- **Faible** : Impact mineur sur le projet
- **Moyen** : Impact modéré, retard possible
- **Important** : Impact significatif
- **Élevé** : Impact majeur, échec possible
- **Très élevé** : Impact critique
- **Critique** : Échec du projet

### **Score**
- **Calcul** : Probabilité × Impact (1-5)
- **Seuils** :
  - **1-5** : Risque faible
  - **6-10** : Risque moyen
  - **11-15** : Risque élevé
  - **16-25** : Risque critique

---

**Document créé le :** 2025-01-27  
**Version :** 2.0  
**Statut :** Projet terminé, risques maîtrisés

