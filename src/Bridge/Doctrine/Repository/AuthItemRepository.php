<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Fedale\RbacBundle\Bridge\Doctrine\Entity\AuthItem;

/**
 * @extends ServiceEntityRepository<AuthItem>
 */
final class AuthItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthItem::class);
    }
}
