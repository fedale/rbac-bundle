<?php

namespace Fedale\RbacBundle\Tests\Fixtures;

use Fedale\RbacBundle\Contract\RuleInterface;
use Fedale\RbacBundle\Contract\RuleResolverInterface;

/**
 * In-memory RuleResolver: maps rule name -> RuleInterface.
 *
 * @internal
 */
final class MapRuleResolver implements RuleResolverInterface
{
    /**
     * @param array<string, RuleInterface> $rules
     */
    public function __construct(
        private readonly array $rules = [],
    ) {
    }

    public function resolve(string $ruleName): ?RuleInterface
    {
        return $this->rules[$ruleName] ?? null;
    }
}
