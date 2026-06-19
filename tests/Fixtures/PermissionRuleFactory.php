<?php

namespace Fedale\AccessControlVoterBundle\Tests\Fixtures;

use Fedale\AccessControlVoterBundle\Dto\PermissionRule;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

trait PermissionRuleFactory
{
    private function makeRule(
        string $attribute = 'EDIT_INVOICE',
        bool $allow = true,
        array $roles = [],
        ?string $subjectType = null,
        ?string $condition = null,
        int $sort = 0,
        int $id = 1,
    ): PermissionRule {
        return new PermissionRule(
            id: $id,
            name: 'rule-' . $id,
            reason: null,
            attribute: $attribute,
            subjectType: $subjectType,
            condition: $condition,
            roles: $roles,
            allow: $allow,
            sort: $sort,
            active: true,
        );
    }

    /**
     * Costruisce un Security reale con isGranted() controllabile, senza dover
     * cablare l'intero security system. Mappa $grantedRoles -> isGranted true.
     */
    private function makeSecurity(array $grantedRoles = [], ?UserInterface $user = null): Security
    {
        $tokenStorage = new TokenStorage();

        if (null !== $user) {
            $tokenStorage->setToken(
                new UsernamePasswordToken($user, 'main', $user->getRoles()),
            );
        }

        $authChecker = new class($grantedRoles) implements AuthorizationCheckerInterface {
            /** @param string[] $granted */
            public function __construct(private readonly array $granted) {}

            public function isGranted(mixed $attribute, mixed $subject = null): bool
            {
                return in_array($attribute, $this->granted, true);
            }
        };

        $psr = new class($tokenStorage, $authChecker) implements PsrContainerInterface {
            public function __construct(
                private readonly TokenStorage $tokenStorage,
                private readonly AuthorizationCheckerInterface $authChecker,
            ) {}

            public function get(string $id): mixed
            {
                return match ($id) {
                    'security.token_storage' => $this->tokenStorage,
                    'security.authorization_checker' => $this->authChecker,
                    default => throw new \RuntimeException("Servizio non previsto: $id"),
                };
            }

            public function has(string $id): bool
            {
                return in_array($id, ['security.token_storage', 'security.authorization_checker'], true);
            }
        };

        return new Security($psr);
    }

    private function makeUser(array $roles = ['ROLE_USER']): UserInterface
    {
        return new InMemoryUser('tester', null, $roles);
    }
}
