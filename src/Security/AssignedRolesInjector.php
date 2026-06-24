<?php

namespace Fedale\RbacBundle\Security;

use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

/**
 * Fallback (inject_assigned_roles: true, default OFF) for apps that cannot
 * modify their own User class. On authentication token creation it replaces the
 * token with one enriched with the RBAC roles read from auth_assignment, so
 * isGranted(ROLE_*) reflects them.
 *
 * Known limitation: it handles PostAuthenticationToken (standard form/json
 * login); with a stateful firewall the injected roles stay in the session token
 * until re-login. Prefer the UserProvider mechanism (token always fresh).
 */
final class AssignedRolesInjector
{
    public function __construct(
        private readonly AssignmentRolesProvider $roles,
    ) {
    }

    public function __invoke(AuthenticationTokenCreatedEvent $event): void
    {
        $token = $event->getAuthenticatedToken();

        if (!$token instanceof PostAuthenticationToken) {
            return;
        }

        $user = $token->getUser();

        if (null === $user) {
            return;
        }

        $assigned = $this->roles->getRolesFor($user->getUserIdentifier());

        if ([] === $assigned) {
            return;
        }

        $merged = array_values(array_unique([...$token->getRoleNames(), ...$assigned]));

        $new = new PostAuthenticationToken($user, $token->getFirewallName(), $merged);
        $new->setAttributes($token->getAttributes());

        $event->setToken($new);
    }
}
