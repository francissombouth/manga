<?php

namespace App\Repository;

use App\Entity\CollectionUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CollectionUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectionUser::class);
    }

    public function save(CollectionUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CollectionUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOeuvre(int $oeuvreId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.oeuvre = :oeuvreId')
            ->setParameter('oeuvreId', $oeuvreId)
            ->orderBy('c.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndOeuvre(int $userId, int $oeuvreId): ?CollectionUser
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :userId')
            ->andWhere('c.oeuvre = :oeuvreId')
            ->setParameter('userId', $userId)
            ->setParameter('oeuvreId', $oeuvreId)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 