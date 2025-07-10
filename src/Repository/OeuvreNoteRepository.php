<?php

namespace App\Repository;

use App\Entity\OeuvreNote;
use App\Entity\Oeuvre;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OeuvreNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OeuvreNote::class);
    }

    public function save(OeuvreNote $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OeuvreNote $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUserAndOeuvre(User $user, Oeuvre $oeuvre): ?OeuvreNote
    {
        return $this->createQueryBuilder('on')
            ->andWhere('on.user = :user')
            ->andWhere('on.oeuvre = :oeuvre')
            ->setParameter('user', $user)
            ->setParameter('oeuvre', $oeuvre)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getAverageNoteForOeuvre(Oeuvre $oeuvre): ?float
    {
        $result = $this->createQueryBuilder('on')
            ->select('AVG(on.note)')
            ->andWhere('on.oeuvre = :oeuvre')
            ->setParameter('oeuvre', $oeuvre)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float)$result, 2) : null;
    }

    public function countNotesForOeuvre(Oeuvre $oeuvre): int
    {
        return $this->createQueryBuilder('on')
            ->select('COUNT(on.id)')
            ->andWhere('on.oeuvre = :oeuvre')
            ->setParameter('oeuvre', $oeuvre)
            ->getQuery()
            ->getSingleScalarResult();
    }
} 