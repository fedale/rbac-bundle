<?php

namespace Fedale\RbacBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * UserProvider decorator for the PRIMARY mechanism: on every user load/refresh
 * it injects the roles read from auth_assignment (if the user implements
 * AssignedRolesAwareInterface). This way the token is always fresh, with no
 * session lag.
 *
 * Opt-in: in the app, decorate your own user provider with this service.
 *
 * @template T of UserInterface
 * @implements UserProviderInterface<T>
 */
final class AssignedRolesUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserProviderInterface $inner,
        private readonly AssignmentRolesProvider $roles,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->inject($this->inner->loadUserByIdentifier($identifier));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->inject($this->inner->refreshUser($user));
    }

    public function supportsClass(string $class): bool
    {
        return $this->inner->supportsClass($class);
    }

    private function inject(UserInterface $user): UserInterface
    {
        if ($user instanceof AssignedRolesAwareInterface) {
            $user->setAssignedRoles($this->roles->getRolesFor($user->getUserIdentifier()));
        }

        return $user;
    }
}
