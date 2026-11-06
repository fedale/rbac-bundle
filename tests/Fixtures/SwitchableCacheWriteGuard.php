<?php

namespace Fedale\RbacBundle\Tests\Fixtures;

use Fedale\RbacBundle\Cache\CacheWriteGuardInterface;

/**
 * Stands for a database transaction: open() refuses caching, close() allows it again.
 */
final class SwitchableCacheWriteGuard implements CacheWriteGuardInterface
{
    private bool $open = false;

    public function open(): void
    {
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function allowsCaching(): bool
    {
        return !$this->open;
    }
}
