<?php

namespace Fedale\RbacBundle\Security;

use Fedale\RbacBundle\Config\VoterConfig;
use Fedale\RbacBundle\Contract\AccessManagerInterface;
use Fedale\RbacBundle\Contract\AssignmentStorageInterface;
use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Fedale\RbacBundle\Contract\RuleResolverInterface;
use Fedale\RbacBundle\Dto\AuthItem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Yii2-style auth manager. can($item, $subject) faithfully mirrors
 * checkAccessRecursive: it walks up from the requested node toward the seeds
 * (the user's assignments + the token's roles), running each node's rule as a gate.
 *
 *   - super_admin_role granted -> true (short-circuit);
 *   - seeds = the user's auth_assignment UNION the token's roles;
 *   - per node: if it has a rule and the rule fails -> branch pruned;
 *     if the node is a seed -> granted; otherwise walk up to the parents
 *     (auth_item_child edges), with a cycle guard.
 */
final class AccessManager implements AccessManagerInterface
{
    public function __construct(
        private readonly ItemStorageInterface $items,
        private readonly AssignmentStorageInterface $assignments,
        private readonly RuleResolverInterface $rules,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $authChecker,
        private readonly VoterConfig $config,
    ) {
    }

    public function can(string $item, mixed $subject = null): bool
    {
        if (!$this->config->enabled) {
            return false;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return false;
        }

        if (
            '' !== $this->config->superAdminRole
            && $this->authChecker->isGranted($this->config->superAdminRole)
        ) {
            return true;
        }

        $seeds = [];

        $user = $token->getUser();
        if (null !== $user) {
            $seeds = $this->assignments->getAssignments($user->getUserIdentifier());
        }

        // The token's roles (already fresh from auth_assignment via User::getRoles)
        // act as seeds anyway, for robustness.
        foreach ($token->getRoleNames() as $role) {
            $seeds[] = $role;
        }

        return $this->check($item, $subject, array_fill_keys($seeds, true), $token, []);
    }

    /**
     * @param array<string, true> $seeds
     * @param array<string, true> $visited
     */
    private function check(string $name, mixed $subject, array $seeds, TokenInterface $token, array $visited): bool
    {
        if (isset($visited[$name])) {
            return false;
        }
        $visited[$name] = true;

        $item = $this->items->getItem($name);

        if (null === $item) {
            // Unmodeled item (e.g. a role coming from the token but not in
            // auth_item): no rule, no parents -> counts only as a seed.
            return isset($seeds[$name]);
        }

        // Gate on the node's rule (like executeRule in checkAccessRecursive).
        if (null !== $item->ruleName && !$this->passesRule($item, $token, $subject)) {
            return false;
        }

        if (isset($seeds[$name])) {
            return true;
        }

        foreach ($this->items->getParents($name) as $parent) {
            if ($this->check($parent, $subject, $seeds, $token, $visited)) {
                return true;
            }
        }

        return false;
    }

    private function passesRule(AuthItem $item, TokenInterface $token, mixed $subject): bool
    {
        $rule = $this->rules->resolve((string) $item->ruleName);

        // Fail-closed: rule_name set but not resolvable -> deny.
        if (null === $rule) {
            return false;
        }

        return $rule->execute($token, $item, $subject);
    }
}
