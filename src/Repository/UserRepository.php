<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


        /**
     * Retourne l'utilisateur par son ID s'il possède un rôle donné.
     */
    public function findOneByIdAndRole(int $id, string $role): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.id = :id')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('id', $id)
            ->setParameter('role', '%"' . $role . '"%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function getQueryByRole(string $role)
{
    return $this->createQueryBuilder('u')
        ->where('JSON_CONTAINS(u.roles, :role) = 1')
        ->setParameter('role', json_encode($role))
        ->getQuery();
}


    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

public function findByRole(string $role): array
{
    $users = $this->createQueryBuilder('u')
        ->getQuery()
        ->getResult();

    // Filtrage PHP (pas idéal pour des gros volumes)
    return array_filter($users, function ($user) use ($role) {
        return in_array($role, $user->getRoles(), true);
    });
}
public function getQueryBuilderByRoleAndSearch(string $role, string $search): \Doctrine\ORM\QueryBuilder
{
    $qb = $this->createQueryBuilder('u')
        ->where('u.roles LIKE :role')
        ->setParameter('role', '%"'.$role.'"%');

    if (!empty($search)) {
        $qb->andWhere('u.name LIKE :search OR u.email LIKE :search')
           ->setParameter('search', '%'.$search.'%');
    }

    return $qb;
}


}
