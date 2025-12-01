<?php

namespace App\Repository;

use App\Entity\Opportunity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Opportunity>
 */
class OpportunityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Opportunity::class);
    }

    //    /**
    //     * @return Opportunity[] Returns an array of Opportunity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Opportunity
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function searchForKanban(array $filters = []): array
{
    $qb = $this->createQueryBuilder('o')
        ->leftJoin('o.contact', 'c')->addSelect('c')
        ->leftJoin('c.campany', 'cmp')->addSelect('cmp')
        ->leftJoin('o.user', 'u')->addSelect('u')
        ->orderBy('o.createdAt', 'DESC');

    if (!empty($filters['user'])) {
        $qb->andWhere('o.user = :user')
           ->setParameter('user', $filters['user']);
    }

    if (!empty($filters['campany'])) {
        $qb->andWhere('c.campany = :campany')
           ->setParameter('campany', $filters['campany']);
    }

    if (!empty($filters['search'])) {
        $search = '%' . mb_strtolower($filters['search']) . '%';

        $qb->andWhere(
            'LOWER(c.firstName) LIKE :q
             OR LOWER(c.lastName) LIKE :q
             OR LOWER(cmp.legalName) LIKE :q
             OR LOWER(o.leadSource) LIKE :q'
        )
        ->setParameter('q', $search);
    }

    return $qb->getQuery()->getResult();
}

}
