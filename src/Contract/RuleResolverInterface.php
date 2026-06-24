<?php

namespace Fedale\RbacBundle\Contract;

/**
 * Resolves a rule name (auth_item.rule_name) into the corresponding executable
 * RuleInterface:
 *   - if the AuthRule has `serviceId` -> the service tagged `fedale_rbac.rule`;
 *   - if it has `expression`          -> a built-in ExpressionRule.
 *
 * Returns null if the rule does not exist.
 */
interface RuleResolverInterface
{
    public function resolve(string $ruleName): ?RuleInterface;
}
