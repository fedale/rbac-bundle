<?php

namespace Fedale\AccessControlVoterBundle\Tests\Functional;

use Fedale\AccessControlVoterBundle\Config\VoterConfig;
use Fedale\AccessControlVoterBundle\Dto\PermissionRule;
use Fedale\AccessControlVoterBundle\Security\DynamicVoter;
use Fedale\AccessControlVoterBundle\Tests\Fixtures\InMemoryPermissionRuleProvider;
use Fedale\AccessControlVoterBundle\Tests\Fixtures\PermissionRuleFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Verifica che il DynamicVoter si innesti nello stack di autorizzazione nativo:
 * vero AccessDecisionManager + AuthorizationChecker. isGranted(ATTR) riproduce
 * cio' che fa #[IsGranted('ATTR')] a livello di controller.
 */
#[CoversNothing]
final class AuthorizationStackTest extends TestCase
{
    use PermissionRuleFactory;

    public function testIsGrantedTrueForEditorOnAllowRule(): void
    {
        $checker = $this->checker(
            [$this->makeRule(attribute: 'EDIT_INVOICE', allow: true, roles: ['ROLE_EDITOR'])],
            grantedRoles: ['ROLE_EDITOR'],
        );

        self::assertTrue($checker->isGranted('EDIT_INVOICE'));
    }

    public function testIsGrantedFalseForNonEditor(): void
    {
        $checker = $this->checker(
            [$this->makeRule(attribute: 'EDIT_INVOICE', allow: true, roles: ['ROLE_EDITOR'])],
            grantedRoles: [],
        );

        self::assertFalse($checker->isGranted('EDIT_INVOICE'));
    }

    public function testAbstainOnUnknownAttributeIsDeniedByAffirmativeStrategy(): void
    {
        // Nessuna regola per l'attributo: il voter si astiene e, con solo questo
        // voter nello stack, l'ADM nega (comportamento corretto end-to-end).
        $checker = $this->checker(
            [$this->makeRule(attribute: 'EDIT_INVOICE', allow: true)],
            grantedRoles: ['ROLE_EDITOR'],
        );

        self::assertFalse($checker->isGranted('DELETE_INVOICE'));
    }

    public function testSuperAdminIsGrantedDespiteDenyRule(): void
    {
        $checker = $this->checker(
            [$this->makeRule(attribute: 'EDIT_INVOICE', allow: false, roles: [])],
            grantedRoles: ['ROLE_SUPER_ADMIN'],
        );

        self::assertTrue($checker->isGranted('EDIT_INVOICE'));
    }

    /**
     * @param PermissionRule[] $rules
     */
    private function checker(array $rules, array $grantedRoles): AuthorizationChecker
    {
        $security = $this->makeSecurity(grantedRoles: $grantedRoles, user: $this->makeUser());

        $voter = new DynamicVoter(
            new InMemoryPermissionRuleProvider($rules),
            $security,
            new VoterConfig(enabled: true, superAdminRole: 'ROLE_SUPER_ADMIN'),
        );

        $adm = new AccessDecisionManager([$voter]);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(
            new UsernamePasswordToken($this->makeUser(), 'main', $grantedRoles),
        );

        return new AuthorizationChecker($tokenStorage, $adm);
    }
}
