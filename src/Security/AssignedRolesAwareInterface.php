<?php

namespace Fedale\RbacBundle\Security;

/**
 * To be implemented on the app's User class for the PRIMARY token integration
 * mechanism (single auth_assignment source). AssignedRolesUserProvider injects
 * the RBAC roles on user load/refresh; getRoles() exposes them.
 *
 * Example:
 *
 *   class User implements UserInterface, AssignedRolesAwareInterface
 *   {
 *       private array $assignedRoles = [];
 *
 *       public function setAssignedRoles(array $roles): void
 *       {
 *           $this->assignedRoles = $roles;
 *       }
 *
 *       public function getRoles(): array
 *       {
 *           return array_values(array_unique([...$this->assignedRoles]));
 *       }
 *   }
 */
interface AssignedRolesAwareInterface
{
    /**
     * @param string[] $roles
     */
    public function setAssignedRoles(array $roles): void;
}
