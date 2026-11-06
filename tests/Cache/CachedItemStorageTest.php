<?php

namespace Fedale\RbacBundle\Tests\Cache;

use Fedale\RbacBundle\Cache\CachedItemStorage;
use Fedale\RbacBundle\Tests\Fixtures\InMemoryItemStorage;
use Fedale\RbacBundle\Tests\Fixtures\SwitchableCacheWriteGuard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(CachedItemStorage::class)]
final class CachedItemStorageTest extends TestCase
{
    public function testCachesTheGraphRead(): void
    {
        $source = (new InMemoryItemStorage())->role('ROLE_EDITOR');
        $pool = new ArrayAdapter();

        self::assertNotNull((new CachedItemStorage($source, $pool))->getItem('ROLE_EDITOR'));
        self::assertTrue($pool->getItem(CachedItemStorage::CACHE_KEY)->isHit());
    }

    public function testNothingReadInsideATransactionReachesTheCache(): void
    {
        $source = (new InMemoryItemStorage())->role('ROLE_EDITOR');
        $pool = new ArrayAdapter();
        $guard = new SwitchableCacheWriteGuard();
        $storage = new CachedItemStorage($source, $pool, guard: $guard);

        $guard->open();
        // An item the transaction adds is seen inside it...
        $source->permission('record.product.read');
        self::assertNotNull($storage->getItem('record.product.read'));
        self::assertFalse($pool->getItem(CachedItemStorage::CACHE_KEY)->isHit(), 'a graph read in a transaction is not stored');
        $guard->close();

        // ...and, once committed, read and cached afterwards.
        self::assertNotNull($storage->getItem('record.product.read'));
        self::assertTrue($pool->getItem(CachedItemStorage::CACHE_KEY)->isHit());
    }

    public function testAGraphMemoizedBeforeATransactionDoesNotHideItsWrites(): void
    {
        $source = (new InMemoryItemStorage())->role('ROLE_EDITOR');
        $guard = new SwitchableCacheWriteGuard();
        $storage = new CachedItemStorage($source, new ArrayAdapter(), guard: $guard);
        self::assertNull($storage->getItem('record.product.read'));

        $guard->open();
        $source->permission('record.product.read');

        self::assertNotNull($storage->getItem('record.product.read'));
    }
}
