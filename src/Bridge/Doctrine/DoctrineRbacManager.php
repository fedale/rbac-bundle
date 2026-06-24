<?php

namespace Fedale\RbacBundle\Bridge\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Fedale\RbacBundle\Bridge\Doctrine\Entity\AuthAssignment;
use Fedale\RbacBundle\Bridge\Doctrine\Entity\AuthItem;
use Fedale\RbacBundle\Bridge\Doctrine\Entity\AuthItemChild;
use Fedale\RbacBundle\Bridge\Doctrine\Entity\AuthRule;
use Fedale\RbacBundle\Cache\CachedItemStorage;
use Fedale\RbacBundle\Cache\CachedRuleStorage;
use Fedale\RbacBundle\Contract\RbacManagerInterface;
use Fedale\RbacBundle\Enum\AuthItemType;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Doctrine implementation of the RBAC write API. Each mutation flushes and
 * invalidates the affected cache (item graph / rule) so subsequent decisions
 * immediately see the new state.
 *
 * Fail-loud: inconsistent operations (missing item, duplicates) throw — this is
 * an administrative tool, not a silent API.
 */
final class DoctrineRbacManager implements RbacManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ?CacheItemPoolInterface $cache = null,
    ) {
    }

    public function addItem(string $name, AuthItemType $type, ?string $description = null, ?string $ruleName = null): void
    {
        if (null !== $this->em->find(AuthItem::class, $name)) {
            throw new \RuntimeException(sprintf('auth_item "%s" already exists.', $name));
        }

        $item = (new AuthItem())
            ->setName($name)
            ->setType($type)
            ->setDescription($description);

        if (null !== $ruleName) {
            $item->setRule($this->requireRule($ruleName));
        }

        $this->em->persist($item);
        $this->em->flush();

        $this->invalidateGraph();
    }

    public function removeItem(string $name): void
    {
        $item = $this->requireItem($name);

        // FK ON DELETE CASCADE removes related edges and assignments.
        $this->em->remove($item);
        $this->em->flush();

        $this->invalidateGraph();
    }

    public function addChild(string $parent, string $child): void
    {
        $parentItem = $this->requireItem($parent);
        $childItem = $this->requireItem($child);

        if (null !== $this->em->find(AuthItemChild::class, ['parent' => $parentItem, 'child' => $childItem])) {
            throw new \RuntimeException(sprintf('Edge "%s" -> "%s" already exists.', $parent, $child));
        }

        $this->em->persist(new AuthItemChild($parentItem, $childItem));
        $this->em->flush();

        $this->invalidateGraph();
    }

    public function removeChild(string $parent, string $child): void
    {
        $edge = $this->em->find(AuthItemChild::class, [
            'parent' => $this->requireItem($parent),
            'child' => $this->requireItem($child),
        ]);

        if (null === $edge) {
            throw new \RuntimeException(sprintf('Edge "%s" -> "%s" does not exist.', $parent, $child));
        }

        $this->em->remove($edge);
        $this->em->flush();

        $this->invalidateGraph();
    }

    public function assign(string $userId, string $item): void
    {
        $authItem = $this->requireItem($item);

        if (null !== $this->em->find(AuthAssignment::class, ['item' => $authItem, 'userId' => $userId])) {
            throw new \RuntimeException(sprintf('"%s" is already assigned to "%s".', $item, $userId));
        }

        $this->em->persist(new AuthAssignment($authItem, $userId));
        $this->em->flush();
        // Assignments are not persistently cached (per-request memoization only).
    }

    public function revoke(string $userId, string $item): void
    {
        $assignment = $this->em->find(AuthAssignment::class, [
            'item' => $this->requireItem($item),
            'userId' => $userId,
        ]);

        if (null === $assignment) {
            throw new \RuntimeException(sprintf('"%s" is not assigned to "%s".', $item, $userId));
        }

        $this->em->remove($assignment);
        $this->em->flush();
    }

    public function addRule(string $name, ?string $serviceId = null, ?string $expression = null): void
    {
        if ((null === $serviceId) === (null === $expression)) {
            throw new \RuntimeException('Provide exactly one of serviceId or expression.');
        }

        if (null !== $this->em->find(AuthRule::class, $name)) {
            throw new \RuntimeException(sprintf('auth_rule "%s" already exists.', $name));
        }

        $rule = (new AuthRule())
            ->setName($name)
            ->setServiceId($serviceId)
            ->setExpression($expression);

        $this->em->persist($rule);
        $this->em->flush();

        $this->invalidateRule($name);
    }

    public function removeRule(string $name): void
    {
        $rule = $this->em->find(AuthRule::class, $name);

        if (null === $rule) {
            throw new \RuntimeException(sprintf('auth_rule "%s" does not exist.', $name));
        }

        // FK rule_name ON DELETE SET NULL detaches the rule from its items.
        $this->em->remove($rule);
        $this->em->flush();

        $this->invalidateRule($name);
        $this->invalidateGraph();
    }

    private function requireItem(string $name): AuthItem
    {
        $item = $this->em->find(AuthItem::class, $name);

        if (null === $item) {
            throw new \RuntimeException(sprintf('auth_item "%s" does not exist.', $name));
        }

        return $item;
    }

    private function requireRule(string $name): AuthRule
    {
        $rule = $this->em->find(AuthRule::class, $name);

        if (null === $rule) {
            throw new \RuntimeException(sprintf('auth_rule "%s" does not exist.', $name));
        }

        return $rule;
    }

    private function invalidateGraph(): void
    {
        $this->cache?->deleteItem(CachedItemStorage::CACHE_KEY);
    }

    private function invalidateRule(string $name): void
    {
        $this->cache?->deleteItem(CachedRuleStorage::CACHE_PREFIX . sha1($name));
    }
}
