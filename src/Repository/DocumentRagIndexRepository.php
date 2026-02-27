<?php

namespace App\Repository;

use App\Entity\DocumentRagIndex;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class DocumentRagIndexRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentRagIndex::class);
    }

    // public function findOneByCourseId(int $courseId): ?CourseRagIndex
    // {
    //     return $this->createQueryBuilder('i')
    //         ->where('i.course = :cid')
    //         ->setParameter('cid', $courseId)
    //         ->getQuery()
    //         ->getOneOrNullResult();
    // }
}
