<?php

namespace Fedale\RbacBundle\Cache;

use Fedale\RbacBundle\Contract\RuleStorageInterface;
use Fedale\RbacBundle\Dto\AuthRule;
use Psr\Cache\CacheItemPoolInterface;

/**
 * PSR-6 decorator for rule definitions (auth_rule), cached per name. Also
 * memoizes absence (a false sentinel) to avoid repeated queries on items with a
 * non-existent rule_name. Source-agnostic.
 */
final class CachedRuleStorage implements RuleStorageInterface
{
    public const CACHE_PREFIX = 'fedale_rbac.rule.';

    /** @var array<string, AuthRule|null> */
    private array $local = [];

    public function __construct(
        private readonly RuleStorageInterface $inner,
        private readonly CacheItemPoolInterface $pool,
        private readonly ?int $ttl = null,
        private readonly string $prefix = self::CACHE_PREFIX,
    ) {
    }

    public function getRule(string $name): ?AuthRule
    {
        if (\array_key_exists($name, $this->local)) {
            return $this->local[$name];
        }

        $cacheItem = $this->pool->getItem($this->prefix . sha1($name));

        if ($cacheItem->isHit()) {
            $value = $cacheItem->get();

            return $this->local[$name] = (false === $value ? null : $value);
        }

        $rule = $this->inner->getRule($name);

        $cacheItem->set($rule ?? false);

        if (null !== $this->ttl) {
            $cacheItem->expiresAfter($this->ttl);
        }

        $this->pool->save($cacheItem);

        return $this->local[$name] = $rule;
    }
}
