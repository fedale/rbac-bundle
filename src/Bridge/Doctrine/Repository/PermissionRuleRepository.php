<?php

namespace Fedale\AccessControlVoterBundle\Bridge\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Fedale\AccessControlVoterBundle\Bridge\Doctrine\Entity\PermissionRule;

final class PermissionRuleRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct(
            $registry,
            PermissionRule::class
        );
    }

    /**
     * @return PermissionRule[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.active = true')
            ->orderBy('pr.sort', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PermissionRule[]
     */
    public function findActiveByAttribute(string $attribute): array
    {
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.active = true')
            ->andWhere('pr.attribute = :attribute')
            ->setParameter('attribute', $attribute)
            ->orderBy('pr.sort', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
