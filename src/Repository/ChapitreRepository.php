<?php

namespace App\Repository;

use App\Entity\Chapitre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChapitreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chapitre::class);
    }

    public function save(Chapitre $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Chapitre $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByOeuvre(int $oeuvreId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.oeuvre = :oeuvreId')
            ->setParameter('oeuvreId', $oeuvreId)
            ->orderBy('c.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextChapitre(Chapitre $chapitre): ?Chapitre
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.oeuvre = :oeuvreId')
            ->andWhere('c.ordre > :ordre')
            ->setParameter('oeuvreId', $chapitre->getOeuvre()->getId())
            ->setParameter('ordre', $chapitre->getOrdre())
            ->orderBy('c.ordre', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPreviousChapitre(Chapitre $chapitre): ?Chapitre
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.oeuvre = :oeuvreId')
            ->andWhere('c.ordre < :ordre')
            ->setParameter('oeuvreId', $chapitre->getOeuvre()->getId())
            ->setParameter('ordre', $chapitre->getOrdre())
            ->orderBy('c.ordre', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByTitre(string $titre): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.titre LIKE :titre')
            ->setParameter('titre', '%' . $titre . '%')
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByMangadxChapterId(string $mangadxChapterId): ?Chapitre
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.mangadxChapterId = :mangadxChapterId')
            ->setParameter('mangadxChapterId', $mangadxChapterId)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 