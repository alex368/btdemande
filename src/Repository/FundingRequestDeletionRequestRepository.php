<?php

namespace App\Repository;

use App\Entity\FundingRequest;
use App\Entity\FundingRequestDeletionRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FundingRequestDeletionRequest>
 */
class FundingRequestDeletionRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FundingRequestDeletionRequest::class);
    }

    public function hasPendingRequestForFundingRequest(FundingRequest $fundingRequest): bool
    {
        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.fundingRequest = :fundingRequest')
            ->andWhere('r.status = :status')
            ->setParameter('fundingRequest', $fundingRequest)
            ->setParameter('status', FundingRequestDeletionRequest::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
