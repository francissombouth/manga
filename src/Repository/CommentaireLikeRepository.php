<?php

namespace App\Repository;

use App\Entity\CommentaireLike;
use App\Entity\Commentaire;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentaireLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentaireLike::class);
    }

    public function save(CommentaireLike $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CommentaireLike $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUserAndCommentaire(User $user, Commentaire $commentaire): ?CommentaireLike
    {
        return $this->createQueryBuilder('cl')
            ->andWhere('cl.user = :user')
            ->andWhere('cl.commentaire = :commentaire')
            ->setParameter('user', $user)
            ->setParameter('commentaire', $commentaire)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByCommentaire(Commentaire $commentaire): int
    {
        return $this->createQueryBuilder('cl')
            ->select('COUNT(cl.id)')
            ->andWhere('cl.commentaire = :commentaire')
            ->setParameter('commentaire', $commentaire)
            ->getQuery()
            ->getSingleScalarResult();
    }
} 