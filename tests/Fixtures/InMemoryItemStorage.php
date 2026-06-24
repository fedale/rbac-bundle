<?php

namespace Fedale\RbacBundle\Tests\Fixtures;

use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Fedale\RbacBundle\Dto\AuthItem;
use Fedale\RbacBundle\Enum\AuthItemType;

/**
 * In-memory ItemStorage for tests: no Doctrine.
 *
 * @internal
 */
final class InMemoryItemStorage implements ItemStorageInterface
{
    /** @var array<string, AuthItem> */
    private array $items = [];

    /** @var array<string, string[]> */
    private array $children = [];

    /** @var array<string, string[]> */
    private array $parents = [];

    public function role(string $name, ?string $ruleName = null): self
    {
        return $this->add(new AuthItem($name, AuthItemType::ROLE, ruleName: $ruleName));
    }

    public function permission(string $name, ?string $ruleName = null): self
    {
        return $this->add(new AuthItem($name, AuthItemType::PERMISSION, ruleName: $ruleName));
    }

    public function add(AuthItem $item): self
    {
        $this->items[$item->name] = $item;

        return $this;
    }

    public function child(string $parent, string $child): self
    {
        $this->children[$parent][] = $child;
        $this->parents[$child][] = $parent;

        return $this;
    }

    public function getItem(string $name): ?AuthItem
    {
        return $this->items[$name] ?? null;
    }

    public function getChildren(string $name): array
    {
        return $this->children[$name] ?? [];
    }

    public function getParents(string $name): array
    {
        return $this->parents[$name] ?? [];
    }

    public function allItems(): iterable
    {
        return array_values($this->items);
    }
}
