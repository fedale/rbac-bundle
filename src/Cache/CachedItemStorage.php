<?php

namespace Fedale\RbacBundle\Cache;

use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Fedale\RbacBundle\Dto\AuthItem;
use Psr\Cache\CacheItemPoolInterface;

/**
 * PSR-6 decorator: materializes the ENTIRE static graph (auth_item +
 * auth_item_child adjacency) under a single key and serves
 * getItem/getChildren/getParents from in-memory maps. can() and the role
 * hierarchy query the graph on every decision: hitting the DB each time would
 * be unsustainable. Source-agnostic.
 *
 * Assignments (per-user, volatile) do NOT go through here: see
 * MemoizedAssignmentStorage.
 *
 * While the guard refuses caching (a database transaction is open), the graph is read from
 * the source on every call and neither stored nor memoized: it may hold items the transaction
 * has just written, and may yet roll back.
 */
final class CachedItemStorage implements ItemStorageInterface
{
    public const CACHE_KEY = 'fedale_rbac.items';

    /**
     * @var array{items: array<string, AuthItem>, children: array<string, string[]>, parents: array<string, string[]>}|null
     */
    private ?array $graph = null;

    public function __construct(
        private readonly ItemStorageInterface $inner,
        private readonly CacheItemPoolInterface $pool,
        private readonly ?int $ttl = null,
        private readonly string $key = self::CACHE_KEY,
        private readonly ?CacheWriteGuardInterface $guard = null,
    ) {
    }

    public function getItem(string $name): ?AuthItem
    {
        return $this->graph()['items'][$name] ?? null;
    }

    public function getChildren(string $name): array
    {
        return $this->graph()['children'][$name] ?? [];
    }

    public function getParents(string $name): array
    {
        return $this->graph()['parents'][$name] ?? [];
    }

    public function allItems(): iterable
    {
        return array_values($this->graph()['items']);
    }

    /**
     * @return array{items: array<string, AuthItem>, children: array<string, string[]>, parents: array<string, string[]>}
     */
    private function graph(): array
    {
        // Before the memo too: a graph memoized before the transaction misses its writes.
        if (null !== $this->guard && !$this->guard->allowsCaching()) {
            return $this->build();
        }

        if (null !== $this->graph) {
            return $this->graph;
        }

        $cacheItem = $this->pool->getItem($this->key);

        if ($cacheItem->isHit()) {
            return $this->graph = $cacheItem->get();
        }

        $graph = $this->build();

        $cacheItem->set($graph);

        if (null !== $this->ttl) {
            $cacheItem->expiresAfter($this->ttl);
        }

        $this->pool->save($cacheItem);

        return $this->graph = $graph;
    }

    /**
     * @return array{items: array<string, AuthItem>, children: array<string, string[]>, parents: array<string, string[]>}
     */
    private function build(): array
    {
        $items = [];
        $children = [];
        $parents = [];

        foreach ($this->inner->allItems() as $item) {
            $items[$item->name] = $item;
        }

        foreach (array_keys($items) as $name) {
            $childNames = $this->inner->getChildren($name);

            if ([] !== $childNames) {
                $children[$name] = $childNames;
            }

            foreach ($childNames as $child) {
                $parents[$child][] = $name;
            }
        }

        return ['items' => $items, 'children' => $children, 'parents' => $parents];
    }
}
