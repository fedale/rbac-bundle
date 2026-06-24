<?php

namespace Fedale\RbacBundle\Security;

use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * RoleHierarchyInterface fed by the RBAC graph (auth_item + auth_item_child)
 * instead of the static security.yaml config. Decorates security.role_hierarchy
 * when override_role_hierarchy = true.
 *
 * Expands ONLY role->role edges (both ends type=role): permissions stay out
 * (AccessManager::can() resolves them), keeping a clean separation between
 * isGranted (roles) and can (permissions). The input roles are always included
 * in the result even if not modeled as auth_item.
 *
 * Note: it is provider-agnostic (depends on ItemStorageInterface, not on a
 * specific DB). The closure is memoized per request; the underlying graph is
 * cached at the ItemStorage level (see CachedItemStorage).
 */
final class RbacRoleHierarchy implements RoleHierarchyInterface
{
    /** @var array<string, string[]> */
    private array $memo = [];

    public function __construct(
        private readonly ItemStorageInterface $items,
    ) {
    }

    public function getReachableRoleNames(array $roles): array
    {
        $key = implode("\0", $roles);

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $reachable = [];
        $visited = [];
        $stack = $roles;

        while ([] !== $stack) {
            $name = array_pop($stack);

            if (isset($visited[$name])) {
                continue;
            }
            $visited[$name] = true;
            $reachable[$name] = true;

            $item = $this->items->getItem($name);

            // Expand only if it is a modeled role; unknown roles stay leaves
            // (present in the output but without RBAC descendants).
            if (null === $item || !$item->isRole()) {
                continue;
            }

            foreach ($this->items->getChildren($name) as $child) {
                if (isset($visited[$child])) {
                    continue;
                }

                $childItem = $this->items->getItem($child);

                // Only role->role edges.
                if (null !== $childItem && $childItem->isRole()) {
                    $stack[] = $child;
                }
            }
        }

        return $this->memo[$key] = array_keys($reachable);
    }
}
