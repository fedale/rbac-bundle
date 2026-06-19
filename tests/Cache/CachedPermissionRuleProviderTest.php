<?php

namespace Fedale\AccessControlVoterBundle\Tests\Cache;

use Fedale\AccessControlVoterBundle\Cache\CachedPermissionRuleProvider;
use Fedale\AccessControlVoterBundle\Dto\PermissionRule;
use Fedale\AccessControlVoterBundle\Tests\Fixtures\InMemoryPermissionRuleProvider;
use Fedale\AccessControlVoterBundle\Tests\Fixtures\PermissionRuleFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(CachedPermissionRuleProvider::class)]
final class CachedPermissionRuleProviderTest extends TestCase
{
    use PermissionRuleFactory;

    public function testFetchesFromInnerOnMissThenServesFromCache(): void
    {
        $inner = new InMemoryPermissionRuleProvider([$this->makeRule(attribute: 'EDIT_INVOICE', id: 7)]);
        $cached = new CachedPermissionRuleProvider($inner, new ArrayAdapter());

        $first = $this->toArray($cached->findActive());
        $second = $this->toArray($cached->findActive());

        self::assertSame(1, $inner->calls, 'il provider sottostante va interrogato una sola volta');
        self::assertEquals($first, $second);
    }

    public function testFindByAttributeFiltersTheCachedSetWithoutHittingInner(): void
    {
        $inner = new InMemoryPermissionRuleProvider([
            $this->makeRule(attribute: 'EDIT_INVOICE', id: 1),
            $this->makeRule(attribute: 'DELETE_INVOICE', id: 2),
        ]);
        $cached = new CachedPermissionRuleProvider($inner, new ArrayAdapter());

        $edit = $this->toArray($cached->findByAttribute('EDIT_INVOICE'));
        $delete = $this->toArray($cached->findByAttribute('DELETE_INVOICE'));
        $missing = $this->toArray($cached->findByAttribute('NOPE'));

        // Tre chiamate ma una sola materializzazione: findByAttribute filtra il
        // set cachato e non delega mai findByAttribute al provider interno.
        self::assertSame(1, $inner->calls);
        self::assertCount(1, $edit);
        self::assertSame(1, $edit[0]->id);
        self::assertCount(1, $delete);
        self::assertSame(2, $delete[0]->id);
        self::assertSame([], $missing);
    }

    public function testPreservesRulesAcrossCacheRoundTrip(): void
    {
        $rule = $this->makeRule(attribute: 'EDIT_INVOICE', allow: false, roles: ['ROLE_X'], id: 42);
        $cached = new CachedPermissionRuleProvider(
            new InMemoryPermissionRuleProvider([$rule]),
            new ArrayAdapter(),
        );

        $cached->findActive();
        $fromCache = $this->toArray($cached->findActive());

        self::assertCount(1, $fromCache);
        $restored = $fromCache[0];
        self::assertSame(42, $restored->id);
        self::assertSame('EDIT_INVOICE', $restored->attribute);
        self::assertFalse($restored->allow);
        self::assertSame(['ROLE_X'], $restored->roles);
    }

    public function testStoresUnderTheConventionalKey(): void
    {
        $pool = new ArrayAdapter();
        $cached = new CachedPermissionRuleProvider(
            new InMemoryPermissionRuleProvider([$this->makeRule()]),
            $pool,
        );

        self::assertFalse($pool->hasItem(CachedPermissionRuleProvider::CACHE_KEY));

        $cached->findActive();

        self::assertTrue($pool->hasItem(CachedPermissionRuleProvider::CACHE_KEY));
    }

    /** @return PermissionRule[] */
    private function toArray(iterable $rules): array
    {
        return is_array($rules) ? $rules : iterator_to_array($rules);
    }
}
