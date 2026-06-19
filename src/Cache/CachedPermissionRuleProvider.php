<?php

namespace Fedale\AccessControlVoterBundle\Cache;

use Fedale\AccessControlVoterBundle\Contract\PermissionRuleProviderInterface;
use Fedale\AccessControlVoterBundle\Dto\PermissionRule;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Decorator PSR-6: memorizza l'INTERO set di regole attive sotto un'unica
 * chiave (il voter le interroga ad ogni voto, quindi colpire il DB ogni volta
 * sarebbe insostenibile). findByAttribute() filtra in memoria il set cachato,
 * cosi' non servono ne' query ne' chiavi per-attributo. Sorgente-agnostico.
 */
final class CachedPermissionRuleProvider implements PermissionRuleProviderInterface
{
    public const CACHE_KEY = 'fedale_access_control_voter.rules';

    public function __construct(
        private readonly PermissionRuleProviderInterface $inner,
        private readonly CacheItemPoolInterface $pool,
        private readonly ?int $ttl = null,
        private readonly string $key = self::CACHE_KEY,
    ) {
    }

    public function findActive(): iterable
    {
        return $this->all();
    }

    public function findByAttribute(string $attribute): iterable
    {
        $matches = [];

        foreach ($this->all() as $rule) {
            if ($rule->attribute === $attribute) {
                $matches[] = $rule;
            }
        }

        return $matches;
    }

    /**
     * @return PermissionRule[]
     */
    private function all(): array
    {
        $item = $this->pool->getItem($this->key);

        if ($item->isHit()) {
            return $item->get();
        }

        $rules = $this->materialize();

        $item->set($rules);

        if (null !== $this->ttl) {
            $item->expiresAfter($this->ttl);
        }

        $this->pool->save($item);

        return $rules;
    }

    /**
     * @return PermissionRule[]
     */
    private function materialize(): array
    {
        $rules = [];

        foreach ($this->inner->findActive() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }
}
