<?php

namespace Fedale\RbacBundle\Cache;

/**
 * Tells the cache decorators whether what they read from the source may be cached right now.
 *
 * A source read inside an open database transaction sees that transaction's own writes, which
 * may still be rolled back: cached, they would outlive it, and every later request would see
 * items that were never committed (a permission "already there" is then never created).
 */
interface CacheWriteGuardInterface
{
    public function allowsCaching(): bool;
}
