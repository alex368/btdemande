<?php

namespace App\Repository;

use App\Entity\Contact;
use App\Entity\ContactStageHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactStageHistory>
 */
class ContactStageHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactStageHistory::class);
    }

    /**
     * @return ContactStageHistory[]
     */
    public function findByContactOrdered(Contact $contact): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.contact = :contact')
            ->setParameter('contact', $contact)
            ->orderBy('s.occurredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasStage(Contact $contact, string $stage): bool
    {
        $count = (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.contact = :contact')
            ->andWhere('s.stage = :stage')
            ->setParameter('contact', $contact)
            ->setParameter('stage', $stage)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function findOneByContactAndStage(Contact $contact, string $stage): ?ContactStageHistory
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.contact = :contact')
            ->andWhere('s.stage = :stage')
            ->setParameter('contact', $contact)
            ->setParameter('stage', $stage)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
