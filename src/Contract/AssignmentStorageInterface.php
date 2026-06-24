<?php

namespace Fedale\RbacBundle\Contract;

/**
 * Source-agnostic access to user->item assignments (auth_assignment). In Yii2
 * it is the single user->role/permission source; same here (see README:
 * User::getRoles() reads this same source).
 */
interface AssignmentStorageInterface
{
    /**
     * Names of the items (roles or permissions) directly assigned to the user.
     *
     * @return string[]
     */
    public function getAssignments(string $userIdentifier): array;
}
