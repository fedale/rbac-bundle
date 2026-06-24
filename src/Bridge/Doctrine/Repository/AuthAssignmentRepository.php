<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Fedale\RbacBundle\Bridge\Doctrine\Entity\AuthAssignment;

/**
 * @extends ServiceEntityRepository<AuthAssignment>
 */
final class AuthAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthAssignment::class);
    }

    /**
     * Names of the items assigned to a user.
     *
     * @return string[]
     */
    public function findItemNamesByUser(string $userIdentifier): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.item) AS name')
            ->andWhere('a.userId = :uid')
            ->setParameter('uid', $userIdentifier)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): string => $row['name'], $rows);
    }
}
