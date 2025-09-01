# Architecture MangaThèque - Explication des Choix

## Vue d'ensemble de l'Architecture

### **Philosophie de conception**
L'architecture MangaThèque suit les principes **SOLID** et le pattern **Domain-Driven Design (DDD)** pour créer une application robuste, maintenable et évolutive.

---

## Analyse du Diagramme UML

### **1. Entités Principales (Core Domain)**

#### **User (Utilisateur)**
```php
class User {
    -id: int
    -email: string
    -roles: array
    -password: string
    -nom: string
    -createdAt: DateTimeImmutable
    -updatedAt: DateTimeImmutable
}
```

**Choix architecturaux :**
- **Sécurité** : `roles` en array pour supporter plusieurs rôles (ROLE_USER, ROLE_ADMIN)
- **Audit** : `createdAt` et `updatedAt` pour traçabilité
- **Flexibilité** : `nom` séparé de `email` pour l'affichage
- **Immutabilité** : `DateTimeImmutable` pour éviter les modifications accidentelles

#### **Oeuvre (Entité Centrale)**
```php
class Oeuvre {
    -id: int
    -titre: string
    -type: string
    -couverture: string
    -resume: text
    -datePublication: DateTime
    -mangadxId: string
    -statut: string
    -originalLanguage: text
    -demographic: text
    -contentRating: text
    -alternativeTitles: json
    -lastVolume: text
    -lastChapter: text
    -year: int
    -createdAt: DateTimeImmutable
    -updatedAt: DateTimeImmutable
}
```

**Choix architecturaux :**
- **Flexibilité** : `type` pour supporter mangas, animes, light novels, etc.
- **Intégration API** : `mangadxId` pour synchronisation avec MangaDex
- **Métadonnées riches** : `demographic`, `contentRating`, `alternativeTitles` pour une expérience complète
- **Performance** : `lastVolume` et `lastChapter` pour éviter les requêtes coûteuses
- **Internationalisation** : `originalLanguage` pour le contenu multilingue
- **Audit** : `createdAt` et `updatedAt` pour traçabilité

#### **Chapitre (Contenu)**
```php
class Chapitre {
    -id: int
    -titre: string
    -ordre: int
    -resume: text
    -pages: json
    -mangadxChapterId: string
    -createdAt: DateTimeImmutable
    -updatedAt: DateTimeImmutable
}
```

**Choix architecturaux :**
- **Navigation** : `ordre` pour le tri chronologique
- **Contenu** : `pages` en JSON pour stocker les URLs des images
- **Synchronisation** : `mangadxChapterId` pour l'import automatique
- **Flexibilité** : `resume` optionnel pour les descriptions
- **Audit** : `createdAt` et `updatedAt` pour traçabilité

---

### **2. Entités de Relation (Supporting Domain)**

#### **CollectionUser (Collection Personnelle)**
```php
class CollectionUser {
    -id: int
    -dateAjout: DateTimeImmutable
    -notePersonnelle: text
    -createdAt: DateTimeImmutable
}
```

**Choix architecturaux :**
- **Entité de liaison** : Relation many-to-many entre User et Oeuvre
- **Personnalisation** : `notePersonnelle` pour les commentaires privés
- **Historique** : `dateAjout` pour le suivi des ajouts

#### **Statut (Suivi de Lecture)**
```php
class Statut {
    -id: int
    -nom: string
    -createdAt: DateTimeImmutable
    -updatedAt: DateTimeImmutable
}
```

**Choix architecturaux :**
- **Flexibilité** : `nom` pour différents statuts (Lu, En cours, À lire, Abandonné)
- **Temporalité** : `updatedAt` pour le suivi des changements de statut

#### **Commentaire (Social)**
```php
class Commentaire {
    -id: int
    -contenu: text
    -createdAt: DateTimeImmutable
    -updatedAt: DateTimeImmutable
}
```

**Choix architecturaux :**
- **Hiérarchie** : Auto-référence pour les réponses (commentaires imbriqués)
- **Social** : Système de likes via `CommentaireLike`
- **Modération** : `createdAt` et `updatedAt` pour l'historique et la traçabilité

---

## Relations et Contraintes

### **Relations Many-to-Many**
```plantuml
User ||--o{ CollectionUser : "possède"
Oeuvre ||--o{ CollectionUser : "dans collection"
```

**Justification :**
- Un utilisateur peut avoir plusieurs œuvres dans sa collection
- Une œuvre peut être dans plusieurs collections d'utilisateurs
- L'entité `CollectionUser` stocke les métadonnées de la relation

### **Relations One-to-Many**
```plantuml
Oeuvre }o--|| Auteur : "écrit par"
Oeuvre ||--o{ Chapitre : "contient"
```

**Justification :**
- Une œuvre a un seul auteur principal (simplification)
- Une œuvre peut avoir plusieurs chapitres
- Navigation bidirectionnelle pour les performances

### **Relations Many-to-Many avec Tags**
```plantuml
Oeuvre }o--o{ Tag : "tagué par"
```

**Justification :**
- Système de tags flexible pour la catégorisation
- Tags réutilisables entre les œuvres
- Recherche et filtrage optimisés

---

## Patterns de Conception

### **1. Repository Pattern**
```php
interface OeuvreRepositoryInterface {
    public function findByTitre(string $titre): array;
    public function search(string $query): array;
    public function findByAuteur(Auteur $auteur): array;
}
```

**Avantages :**
- Abstraction de la couche de données
- Tests unitaires facilités
- Changement de base de données transparent

### **2. Service Layer**
```php
class MangaDxImportService {
    public function importOeuvre(string $mangadxId): Oeuvre;
    public function importChapitres(Oeuvre $oeuvre): void;
    public function syncTags(): void;
}
```

**Avantages :**
- Logique métier centralisée
- Réutilisabilité
- Séparation des responsabilités

### **3. Value Objects**
```php
class Email {
    private string $value;
    
    public function __construct(string $email) {
        // Validation
    }
}
```

**Avantages :**
- Validation centralisée
- Immutabilité
- Type safety

---

## Choix Techniques

### **1. Base de Données : PostgreSQL**
**Justification :**
- Support JSON natif pour les métadonnées flexibles
- Performances excellentes pour les requêtes complexes
- Support des transactions ACID
- Extensions utiles (Full-text search, etc.)

### **2. Framework : Symfony 7.3**
**Justification :**
- Maturité et stabilité
- Écosystème riche (Doctrine, Security, etc.)
- Performance optimisée
- Documentation excellente
- Support long terme (LTS)

### **3. ORM : Doctrine**
**Justification :**
- Mapping objet-relationnel puissant
- Migrations automatiques
- Requêtes optimisées
- Cache intégré

### **4. API : REST Native Symfony**
**Justification :**
- Simplicité d'implémentation
- Performance native
- Pas de surcharge de framework API
- Intégration facile avec le frontend

---

## Sécurité et Performance

### **Sécurité**
- **Authentification** : Symfony Security avec JWT
- **Autorisation** : Rôles et permissions granulaires
- **Validation** : Symfony Validator pour les données
- **CSRF** : Protection automatique Symfony

### **Performance**
- **Cache** : Redis pour les données fréquemment accédées
- **Indexation** : Index sur les champs de recherche
- **Pagination** : Pour les listes volumineuses
- **Lazy Loading** : Doctrine pour les relations

---

## Évolutivité

### **Scalabilité Horizontale**
- **Load Balancing** : Nginx + PHP-FPM
- **Base de données** : Read replicas pour les requêtes
- **Cache** : Distribution avec Redis Cluster
- **CDN** : Render.com pour les assets statiques

### **Microservices Potentiels**
- **Service d'import** : Séparé pour l'import MangaDex
- **Service de recherche** : Elasticsearch dédié
- **Service de notifications** : WebSockets pour le temps réel
- **Service de fichiers** : Stockage cloud (R2, S3)

---

## Avantages de cette Architecture

### **✅ Maintenabilité**
- Code modulaire et bien structuré
- Séparation claire des responsabilités
- Tests unitaires facilités

### **✅ Évolutivité**
- Ajout facile de nouvelles fonctionnalités
- Support de nouveaux types de contenu
- Intégration d'APIs externes

### **✅ Performance**
- Requêtes optimisées
- Cache intelligent
- Pagination efficace

### **✅ Sécurité**
- Validation stricte des données
- Authentification robuste
- Protection contre les attaques courantes

---