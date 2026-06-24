<?php

namespace Fedale\RbacBundle\Tests\Fixtures;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * AuthorizationChecker stub: grants only the listed attributes. In
 * AccessManager it is only needed for the super_admin short-circuit.
 *
 * @internal
 */
final class StubAuthorizationChecker implements AuthorizationCheckerInterface
{
    /**
     * @param string[] $granted
     */
    public function __construct(
        private readonly array $granted = [],
    ) {
    }

    public function isGranted(mixed $attribute, mixed $subject = null): bool
    {
        return \in_array($attribute, $this->granted, true);
    }
}
