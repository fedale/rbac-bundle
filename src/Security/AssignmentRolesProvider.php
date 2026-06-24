<?php

namespace Fedale\RbacBundle\Security;

use Fedale\RbacBundle\Contract\AssignmentStorageInterface;
use Fedale\RbacBundle\Contract\ItemStorageInterface;

/**
 * Extracts from auth_assignment only the role-type items assigned to a user.
 * It is the bridge to populate the token with RBAC roles: used both by the
 * primary mechanism (User::getRoles() via AssignedRolesUserProvider) and by the
 * fallback (AssignedRolesInjector).
 */
final class AssignmentRolesProvider
{
    public function __construct(
        private readonly AssignmentStorageInterface $assignments,
        private readonly ItemStorageInterface $items,
    ) {
    }

    /**
     * @return string[] role names assigned to the user
     */
    public function getRolesFor(string $userIdentifier): array
    {
        $roles = [];

        foreach ($this->assignments->getAssignments($userIdentifier) as $name) {
            $item = $this->items->getItem($name);

            if (null !== $item && $item->isRole()) {
                $roles[] = $name;
            }
        }

        return array_values(array_unique($roles));
    }
}
