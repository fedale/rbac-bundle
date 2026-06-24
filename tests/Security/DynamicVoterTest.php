<?php

namespace Fedale\RbacBundle\Tests\Security;

use Fedale\RbacBundle\Config\VoterConfig;
use Fedale\RbacBundle\Contract\AccessManagerInterface;
use Fedale\RbacBundle\Security\DynamicVoter;
use Fedale\RbacBundle\Tests\Fixtures\InMemoryItemStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

#[CoversClass(DynamicVoter::class)]
final class DynamicVoterTest extends TestCase
{
    public function testGrantsWhenAccessManagerAllowsAPermissionAttribute(): void
    {
        $voter = $this->voter(canResult: true);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token(), null, ['EDIT_POST']),
        );
    }

    public function testDeniesWhenAccessManagerRefuses(): void
    {
        $voter = $this->voter(canResult: false);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token(), null, ['EDIT_POST']),
        );
    }

    public function testAbstainsOnRoleAttributes(): void
    {
        // ROLE_EDITOR is a role-type item: the voter does not handle it
        // (avoids recursion on ROLE_*), it lets the other voters decide.
        $voter = $this->voter(canResult: true);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->token(), null, ['ROLE_EDITOR']),
        );
    }

    public function testAbstainsOnUnknownAttributes(): void
    {
        $voter = $this->voter(canResult: true);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->token(), null, ['UNKNOWN']),
        );
    }

    public function testAbstainsWhenDisabled(): void
    {
        $voter = $this->voter(canResult: true, enabled: false);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->token(), null, ['EDIT_POST']),
        );
    }

    private function voter(bool $canResult, bool $enabled = true): DynamicVoter
    {
        $items = (new InMemoryItemStorage())
            ->role('ROLE_EDITOR')
            ->permission('EDIT_POST');

        $accessManager = new class($canResult) implements AccessManagerInterface {
            public function __construct(private readonly bool $result)
            {
            }

            public function can(string $item, mixed $subject = null): bool
            {
                return $this->result;
            }
        };

        return new DynamicVoter(
            $accessManager,
            $items,
            new VoterConfig(enabled: $enabled, superAdminRole: 'ROLE_SUPER_ADMIN'),
        );
    }

    private function token(): TokenInterface
    {
        return new UsernamePasswordToken(new InMemoryUser('u', null), 'main', ['ROLE_USER']);
    }
}
