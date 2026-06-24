<?php

namespace Fedale\RbacBundle\Security;

use Fedale\RbacBundle\Config\VoterConfig;
use Fedale\RbacBundle\Contract\AccessManagerInterface;
use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Thin bridge between Symfony's native flow (#[IsGranted] / isGranted() /
 * AccessDecisionManager) and the RBAC AccessManager.
 *
 * Scoped only to attributes that are known PERMISSIONS (auth_item type=permission):
 * this way roles (ROLE_*) do not fall in here and isGranted($role) — used in the
 * hierarchy — does not trigger recursion. On attributes that are not its own the
 * voter abstains, leaving room for the other voters (including your custom ones).
 */
final class DynamicVoter extends Voter
{
    public function __construct(
        private readonly AccessManagerInterface $accessManager,
        private readonly ItemStorageInterface $items,
        private readonly VoterConfig $config,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$this->config->enabled) {
            return false;
        }

        $item = $this->items->getItem($attribute);

        return null !== $item && $item->isPermission();
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->accessManager->can($attribute, $subject);
    }
}
