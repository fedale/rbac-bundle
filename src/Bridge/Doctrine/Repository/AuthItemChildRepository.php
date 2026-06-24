<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Fedale\RbacBundle\Bridge\Doctrine\Entity\AuthItemChild;

/**
 * @extends ServiceEntityRepository<AuthItemChild>
 */
final class AuthItemChildRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthItemChild::class);
    }

    /**
     * Names of the direct children of $parent.
     *
     * @return string[]
     */
    public function findChildNames(string $parent): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.child) AS name')
            ->andWhere('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): string => $row['name'], $rows);
    }

    /**
     * Names of the direct parents of $child.
     *
     * @return string[]
     */
    public function findParentNames(string $child): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.parent) AS name')
            ->andWhere('c.child = :child')
            ->setParameter('child', $child)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): string => $row['name'], $rows);
    }
}
