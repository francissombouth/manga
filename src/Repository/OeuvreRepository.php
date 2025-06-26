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
            ->andWhere('o.titre LIKE :titre')
            ->setParameter('titre', '%' . $titre . '%')
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
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

    public function findByTag(int $tagId): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.tags', 't')
            ->andWhere('t.id = :tagId')
            ->setParameter('tagId', $tagId)
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
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

    public function search(string $query): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.auteur', 'a')
            ->leftJoin('o.tags', 't')
            ->andWhere('o.titre LIKE :query')
            ->orWhere('a.nom LIKE :query')
            ->orWhere('a.nomPlume LIKE :query')
            ->orWhere('t.nom LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithAuteurAndChapitres(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.auteur', 'a')
            ->leftJoin('o.chapitres', 'c')
            ->addSelect('a', 'c')
            ->orderBy('o.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 