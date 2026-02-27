<?php

namespace App\Repository;

use App\Entity\DocumentRagChunk;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DocumentRagChunkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentRagChunk::class);
    }

    // public function deleteByCourseId(int $bookId): void
    // {
    //     $this->createQueryBuilder('c')
    //         ->delete()
    //         ->where('c.book = :cid')
    //         ->setParameter('cid', $bookId)
    //         ->getQuery()
    //         ->execute();
    // }

    // public function findByCourseId(int $courseId): array
    // {
    //     return $this->createQueryBuilder('c')
    //         ->where('c.course = :cid')
    //         ->setParameter('cid', $courseId)
    //         ->orderBy('c.id', 'ASC')
    //         ->getQuery()
    //         ->getResult();
    // }

    // public function countByCourseId(int $courseId): int
    // {
    //     return (int) $this->createQueryBuilder('c')
    //         ->select('COUNT(c.id)')
    //         ->where('c.course = :cid')
    //         ->setParameter('cid', $courseId)
    //         ->getQuery()
    //         ->getSingleScalarResult();
    // }
}
