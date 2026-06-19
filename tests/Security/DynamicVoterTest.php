<?php

namespace Fedale\AccessControlVoterBundle\Tests\Security;

use Fedale\AccessControlVoterBundle\Config\VoterConfig;
use Fedale\AccessControlVoterBundle\Dto\PermissionRule;
use Fedale\AccessControlVoterBundle\Security\DynamicVoter;
use Fedale\AccessControlVoterBundle\Tests\Fixtures\InMemoryPermissionRuleProvider;
use Fedale\AccessControlVoterBundle\Tests\Fixtures\PermissionRuleFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(DynamicVoter::class)]
final class DynamicVoterTest extends TestCase
{
    use PermissionRuleFactory;

    public function testGrantsWhenAllowRuleMatchesUserRole(): void
    {
        $voter = $this->voter(
            [$this->makeRule(attribute: 'EDIT_INVOICE', allow: true, roles: ['ROLE_EDITOR'])],
            $this->makeSecurity(grantedRoles: ['ROLE_EDITOR']),
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token(), null, ['EDIT_INVOICE']),
        );
    }

    public function testDeniesWhenAllowRuleRequiresAnUngrantedRole(): void
    {
        $voter = $this->voter(
            [$this->makeRule(attribute: 'EDIT_INVOICE', allow: true, roles: ['ROLE_EDITOR'])],
            $this->makeSecurity(grantedRoles: []),
        );

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token(), null, ['EDIT_INVOICE']),
        );
    }

    public function testDeniesWhenMatchingRuleIsDeny(): void
    {
        $voter = $this->voter(
            [$this->makeRule(attribute: 'EDIT_INVOICE', allow: false, roles: ['ROLE_EDITOR'])],
            $this->makeSecurity(grantedRoles: ['ROLE_EDITOR']),
        );

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token(), null, ['EDIT_INVOICE']),
        );
    }

    public function testEmptyRolesMatchUnconditionally(): void
    {
        $voter = $this->voter(
            [$this->makeRule(attribute: 'EDIT_INVOICE', allow: true, roles: [])],
            $this->makeSecurity(grantedRoles: []),
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token(), null, ['EDIT_INVOICE']),
        );
    }

    public function testAbstainsWhenNoRuleForAttribute(): void
    {
        $voter = $this->voter(
            [$this->makeRule(attribute: 'EDIT_INVOICE')],
            $this->makeSecurity(),
        );

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->token(), null, ['DELETE_INVOICE']),
        );
    }

    public function testAbstainsWhenBundleDisabled(): void
    {
        $voter = $this->voter(
            [$this->makeRule(attribute: 'EDIT_INVOICE', roles: [])],
            $this->makeSecurity(),
            enabled: false,
        );

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->token(), null, ['EDIT_INVOICE']),
        );
    }

    public function testSuperAdminBypassesDenyRule(): void
    {
        $voter = $this->voter(
            [$this->makeRule(attribute: 'EDIT_INVOICE', allow: false, roles: [])],
            $this->makeSecurity(grantedRoles: ['ROLE_SUPER_ADMIN']),
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token(), null, ['EDIT_INVOICE']),
        );
    }

    public function testFirstMatchWinsBySort(): void
    {
        // sort 0: deny per ROLE_EDITOR; sort 10: allow per ROLE_EDITOR.
        // Vince la prima applicabile (sort 0) -> deny.
        $voter = $this->voter(
            [
                $this->makeRule(attribute: 'EDIT_INVOICE', allow: false, roles: ['ROLE_EDITOR'], sort: 0, id: 1),
                $this->makeRule(attribute: 'EDIT_INVOICE', allow: true, roles: ['ROLE_EDITOR'], sort: 10, id: 2),
            ],
            $this->makeSecurity(grantedRoles: ['ROLE_EDITOR']),
        );

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token(), null, ['EDIT_INVOICE']),
        );
    }

    public function testSkipsNonApplicableRuleAndUsesNext(): void
    {
        // La prima regola richiede ROLE_MANAGER (non concesso) -> saltata.
        // La seconda (ROLE_EDITOR, concesso) -> allow.
        $voter = $this->voter(
            [
                $this->makeRule(attribute: 'EDIT_INVOICE', allow: false, roles: ['ROLE_MANAGER'], sort: 0, id: 1),
                $this->makeRule(attribute: 'EDIT_INVOICE', allow: true, roles: ['ROLE_EDITOR'], sort: 10, id: 2),
            ],
            $this->makeSecurity(grantedRoles: ['ROLE_EDITOR']),
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token(), null, ['EDIT_INVOICE']),
        );
    }

    /**
     * @param PermissionRule[] $rules
     */
    private function voter(array $rules, Security $security, bool $enabled = true): DynamicVoter
    {
        return new DynamicVoter(
            new InMemoryPermissionRuleProvider($rules),
            $security,
            new VoterConfig(enabled: $enabled, superAdminRole: 'ROLE_SUPER_ADMIN'),
        );
    }

    private function token(): TokenInterface
    {
        return new UsernamePasswordToken($this->makeUser(), 'main', ['ROLE_USER']);
    }
}
