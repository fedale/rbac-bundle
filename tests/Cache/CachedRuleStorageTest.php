<?php

namespace Fedale\RbacBundle\Tests\Cache;

use Fedale\RbacBundle\Cache\CachedRuleStorage;
use Fedale\RbacBundle\Contract\RuleStorageInterface;
use Fedale\RbacBundle\Dto\AuthRule;
use Fedale\RbacBundle\Tests\Fixtures\SwitchableCacheWriteGuard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(CachedRuleStorage::class)]
final class CachedRuleStorageTest extends TestCase
{
    public function testNothingReadInsideATransactionReachesTheCache(): void
    {
        $source = new class implements RuleStorageInterface {
            /** @var array<string, AuthRule> */
            public array $rules = [];

            public function getRule(string $name): ?AuthRule
            {
                return $this->rules[$name] ?? null;
            }
        };
        $pool = new ArrayAdapter();
        $guard = new SwitchableCacheWriteGuard();
        $storage = new CachedRuleStorage($source, $pool, guard: $guard);
        $key = CachedRuleStorage::CACHE_PREFIX.sha1('is_author');

        $guard->open();
        // Absent inside the transaction: neither the absence nor anything else is stored.
        self::assertNull($storage->getRule('is_author'));
        self::assertFalse($pool->getItem($key)->isHit());
        $guard->close();

        $source->rules['is_author'] = new AuthRule('is_author', expression: 'subject.author == user');
        self::assertNotNull($storage->getRule('is_author'));
        self::assertTrue($pool->getItem($key)->isHit());
    }
}
