<?php

namespace App\Repository;

use App\Entity\Auteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuteurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Auteur::class);
    }

    public function save(Auteur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Auteur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByNom(string $nom): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.nom LIKE :nom')
            ->setParameter('nom', '%' . $nom . '%')
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByNomPlume(string $nomPlume): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.nomPlume LIKE :nomPlume')
            ->setParameter('nomPlume', '%' . $nomPlume . '%')
            ->orderBy('a.nomPlume', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 