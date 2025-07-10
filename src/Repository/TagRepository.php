<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function save(Tag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByNom(string $nom): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.nom LIKE :nom')
            ->setParameter('nom', '%' . $nom . '%')
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOrCreate(string $nom): Tag
    {
        $tag = $this->findOneBy(['nom' => $nom]);
        
        if (!$tag) {
            $tag = new Tag();
            $tag->setNom($nom);
            $this->save($tag, true);
        }
        
        return $tag;
    }

    /**
     * Trouve les tags les plus populaires (les plus utilisés)
     */
    public function findPopularTags(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->join('t.oeuvres', 'o')
            ->groupBy('t.id')
            ->orderBy('COUNT(o.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 