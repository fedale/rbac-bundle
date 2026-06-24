<?php

namespace Fedale\RbacBundle\Contract;

use Fedale\RbacBundle\Dto\AuthItem;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Counterpart of Yii2 RBAC's "Rule" (a class with
 * execute($user, $item, $params): bool attached to an item via rule_name and
 * evaluated by user->can($item, $params)).
 *
 * An AuthItem can reference an AuthRule via `ruleName`; the rule is executed
 * during AccessManager::can()'s upward walk: if it returns false, the branch is
 * pruned (exactly like Yii2's checkAccessRecursive).
 *
 * $params is the $subject passed to can()/isGranted() (scalar, object or map).
 */
interface RuleInterface
{
    public function execute(TokenInterface $token, AuthItem $item, mixed $params = null): bool;
}
