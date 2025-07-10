<?php

namespace App\Repository;

use App\Entity\Oeuvre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OeuvreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Oeuvre::class);
    }

    public function save(Oeuvre $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Oeuvre $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTitre(string $titre): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.auteur', 'a')
            ->addSelect('a')
            ->andWhere('o.titre LIKE :titre')
            ->setParameter('titre', '%' . $titre . '%')
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithRelations(int $limit = null, int $offset = 0, string $orderBy = 'updatedAt', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.auteur', 'a')
            ->addSelect('a')
            ->orderBy('o.' . $orderBy, $order);

        if ($limit !== null) {
            $qb->setMaxResults($limit)
               ->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.type = :type')
            ->setParameter('type', $type)
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByAuteur(int $auteurId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.auteur = :auteurId')
            ->setParameter('auteurId', $auteurId)
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTag(int $tagId, int $limit = null, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.tags', 't')
            ->leftJoin('o.auteur', 'a')
            ->addSelect('a')
            ->andWhere('t.id = :tagId')
            ->setParameter('tagId', $tagId)
            ->orderBy('o.titre', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit)
               ->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByTag(int $tagId): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->innerJoin('o.tags', 't')
            ->andWhere('t.id = :tagId')
            ->setParameter('tagId', $tagId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByUserCollection(int $userId): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.collections', 'c')
            ->andWhere('c.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserStatut(int $userId, string $statut): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.statuts', 's')
            ->andWhere('s.user = :userId')
            ->andWhere('s.nom = :statut')
            ->setParameter('userId', $userId)
            ->setParameter('statut', $statut)
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query = '', ?int $auteurId = null, ?int $tagId = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('DISTINCT o.id')
            ->leftJoin('o.auteur', 'a')
            ->leftJoin('o.tags', 't');

        $conditions = [];
        $parameters = [];

        // Recherche textuelle si query fourni (insensible à la casse)
        if (!empty($query)) {
            $conditions[] = '(
                LOWER(o.titre) LIKE :query OR 
                LOWER(a.nom) LIKE :query OR 
                LOWER(a.prenom) LIKE :query OR 
                LOWER(t.nom) LIKE :query
            )';
            $parameters['query'] = '%' . mb_strtolower($query) . '%';
        }

        // Filtre par auteur si fourni
        if ($auteurId) {
            $conditions[] = 'a.id = :auteurId';
            $parameters['auteurId'] = $auteurId;
        }

        // Filtre par tag si fourni
        if ($tagId) {
            $conditions[] = 't.id = :tagId';
            $parameters['tagId'] = $tagId;
        }

        // Appliquer les conditions
        if (!empty($conditions)) {
            $qb->where(implode(' AND ', $conditions));
        }

        // Appliquer les paramètres
        foreach ($parameters as $key => $value) {
            $qb->setParameter($key, $value);
        }

        // Récupérer les IDs des œuvres
        $oeuvreIds = $qb
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($oeuvreIds)) {
            return [];
        }

        // Récupérer les entités complètes avec les relations
        return $this->createQueryBuilder('o')
            ->leftJoin('o.auteur', 'a')
            ->leftJoin('o.tags', 't')
            ->addSelect('a', 't')
            ->where('o.id IN (:oeuvreIds)')
            ->setParameter('oeuvreIds', $oeuvreIds)
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée avec filtres multiples et pagination
     */
    public function searchWithFilters(
        ?string $query = null, 
        ?int $auteurId = null, 
        ?int $tagId = null, 
        ?string $type = null,
        int $limit = 20, 
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.auteur', 'a')
            ->leftJoin('o.tags', 't')
            ->addSelect('a');

        $conditions = [];
        $parameters = [];

        // Recherche textuelle sur titre, auteur et tags (insensible à la casse)
        if (!empty($query)) {
            $conditions[] = '(
                LOWER(o.titre) LIKE :query OR 
                LOWER(a.nom) LIKE :query OR 
                LOWER(a.nomPlume) LIKE :query OR 
                LOWER(t.nom) LIKE :query
            )';
            $parameters['query'] = '%' . mb_strtolower($query) . '%';
        }

        if ($auteurId) {
            $conditions[] = 'o.auteur = :auteurId';
            $parameters['auteurId'] = $auteurId;
        }

        if ($tagId) {
            $conditions[] = 't.id = :tagId';
            $parameters['tagId'] = $tagId;
        }

        if ($type) {
            $conditions[] = 'o.type = :type';
            $parameters['type'] = $type;
        }

        if (!empty($conditions)) {
            $qb->where(implode(' AND ', $conditions));
        }

        foreach ($parameters as $key => $value) {
            $qb->setParameter($key, $value);
        }

        return $qb
            ->distinct()
            ->orderBy('o.titre', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les résultats de recherche avec filtres
     */
    public function countSearchWithFilters(
        ?string $query = null, 
        ?int $auteurId = null, 
        ?int $tagId = null, 
        ?string $type = null
    ): int {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(DISTINCT o.id)')
            ->leftJoin('o.auteur', 'a')
            ->leftJoin('o.tags', 't');

        $conditions = [];
        $parameters = [];

        // Recherche textuelle sur titre, auteur et tags
        if (!empty($query)) {
            $conditions[] = '(o.titre LIKE :query OR a.nom LIKE :query OR a.nomPlume LIKE :query OR t.nom LIKE :query)';
            $parameters['query'] = '%' . $query . '%';
        }

        // Filtre par auteur
        if ($auteurId) {
            $conditions[] = 'o.auteur = :auteurId';
            $parameters['auteurId'] = $auteurId;
        }

        // Filtre par tag
        if ($tagId) {
            $conditions[] = 't.id = :tagId';
            $parameters['tagId'] = $tagId;
        }

        // Filtre par type
        if ($type) {
            $conditions[] = 'o.type = :type';
            $parameters['type'] = $type;
        }

        // Appliquer les conditions
        if (!empty($conditions)) {
            $qb->where(implode(' AND ', $conditions));
        }

        // Appliquer les paramètres
        foreach ($parameters as $key => $value) {
            $qb->setParameter($key, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findAllWithAuteurAndChapitres(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.auteur', 'a')
            ->leftJoin('o.chapitres', 'c')
            ->addSelect('a', 'c')
            ->orderBy('o.updatedAt', 'DESC')
            ->setMaxResults(100) // Limiter à 100 œuvres pour optimiser les performances
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les dernières œuvres avec un système de pagination optimisé
     */
    public function findLatestWithPagination(int $limit = 100, int $offset = 0): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.auteur', 'a')
            ->leftJoin('o.chapitres', 'c')
            ->addSelect('a', 'c')
            ->orderBy('o.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les œuvres avec leurs statistiques calculées en SQL pour optimiser les performances
     */
    public function findWithStats(int $limit = 100): array
    {
        $dateLimit = new \DateTimeImmutable('-7 days');
        
        return $this->createQueryBuilder('o')
            ->leftJoin('o.auteur', 'a')
            ->leftJoin('o.chapitres', 'c')
            ->addSelect('a')
            ->addSelect('COUNT(c.id) as chapitres_count')
            ->addSelect('MAX(c.createdAt) as latest_chapter_date')
            ->addSelect('SUM(CASE WHEN c.createdAt > :dateLimit THEN 1 ELSE 0 END) as new_chapters_count')
            ->setParameter('dateLimit', $dateLimit)
            ->groupBy('o.id', 'a.id')
            ->orderBy('latest_chapter_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 