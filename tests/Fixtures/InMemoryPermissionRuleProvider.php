<?php

namespace Fedale\AccessControlVoterBundle\Tests\Fixtures;

use Fedale\AccessControlVoterBundle\Contract\PermissionRuleProviderInterface;
use Fedale\AccessControlVoterBundle\Dto\PermissionRule;

/**
 * Provider di regole in-memory: usato nei test come sorgente custom
 * (provider: app.rules), in modo da non dipendere da Doctrine.
 *
 * @internal
 */
final class InMemoryPermissionRuleProvider implements PermissionRuleProviderInterface
{
    public int $calls = 0;

    /** @param PermissionRule[] $rules */
    public function __construct(private readonly array $rules)
    {
    }

    public function findActive(): iterable
    {
        ++$this->calls;

        return $this->rules;
    }

    public function findByAttribute(string $attribute): iterable
    {
        ++$this->calls;

        return array_values(array_filter(
            $this->rules,
            static fn (PermissionRule $rule): bool => $rule->attribute === $attribute,
        ));
    }
}
