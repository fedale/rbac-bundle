<?php

namespace Fedale\RbacBundle\Bridge\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Fedale\RbacBundle\Cache\CacheWriteGuardInterface;

/**
 * Refuses caching while the connection the RBAC tables live on has a transaction open: an item
 * added inside it (a host app creating the permissions of a new object in the same transaction)
 * is not committed yet, and a rollback must not leave it in the cache.
 */
final class TransactionCacheWriteGuard implements CacheWriteGuardInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function allowsCaching(): bool
    {
        return !$this->em->getConnection()->isTransactionActive();
    }
}
