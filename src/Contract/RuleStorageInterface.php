<?php

namespace Fedale\RbacBundle\Contract;

use Fedale\RbacBundle\Dto\AuthRule;

/**
 * Source-agnostic access to rule definitions (auth_rule). Returns the
 * definition (serviceId | expression); turning it into an executable
 * RuleInterface is RuleResolverInterface's job.
 */
interface RuleStorageInterface
{
    public function getRule(string $name): ?AuthRule;
}
