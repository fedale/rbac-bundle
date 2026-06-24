<?php

namespace Fedale\RbacBundle\Tests\Security;

use Fedale\RbacBundle\Config\VoterConfig;
use Fedale\RbacBundle\Contract\RuleResolverInterface;
use Fedale\RbacBundle\Security\AccessManager;
use Fedale\RbacBundle\Tests\Fixtures\CallbackRule;
use Fedale\RbacBundle\Tests\Fixtures\InMemoryAssignmentStorage;
use Fedale\RbacBundle\Tests\Fixtures\InMemoryItemStorage;
use Fedale\RbacBundle\Tests\Fixtures\MapRuleResolver;
use Fedale\RbacBundle\Tests\Fixtures\StubAuthorizationChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

#[CoversClass(AccessManager::class)]
final class AccessManagerTest extends TestCase
{
    public function testDirectPermissionAssignmentGrantsWithoutTheParentRole(): void
    {
        // EDIT_INVOICE is a child of ROLE_MANAGER, but the user has ONLY ROLE_EDITOR
        // and the direct assignment of EDIT_INVOICE (key Yii2 scenario).
        $items = (new InMemoryItemStorage())
            ->role('ROLE_MANAGER')
            ->role('ROLE_EDITOR')
            ->permission('EDIT_INVOICE')
            ->child('ROLE_MANAGER', 'EDIT_INVOICE');

        $manager = $this->manager(
            $items,
            ['u' => ['ROLE_EDITOR', 'EDIT_INVOICE']],
        );

        self::assertTrue($manager->can('EDIT_INVOICE'), 'directly assigned permission');
        self::assertFalse($manager->can('ROLE_MANAGER'), 'the parent role is NOT granted');
    }

    public function testGrantsThroughMultiLevelHierarchy(): void
    {
        $items = (new InMemoryItemStorage())
            ->role('ROLE_ADMIN')
            ->role('ROLE_EDITOR')
            ->permission('EDIT_POST')
            ->child('ROLE_ADMIN', 'ROLE_EDITOR')
            ->child('ROLE_EDITOR', 'EDIT_POST');

        $manager = $this->manager($items, ['u' => ['ROLE_ADMIN']]);

        self::assertTrue($manager->can('EDIT_POST'));
    }

    public function testDeniesWhenNoPathReachesAssignments(): void
    {
        $items = (new InMemoryItemStorage())
            ->role('ROLE_EDITOR')
            ->permission('DELETE_POST');

        $manager = $this->manager($items, ['u' => ['ROLE_EDITOR']]);

        self::assertFalse($manager->can('DELETE_POST'));
    }

    public function testRuleGatesTheNodeUsingSubject(): void
    {
        // EDIT_POST has an "author" rule: it passes only if $subject === 'mine'.
        $items = (new InMemoryItemStorage())
            ->role('ROLE_ADMIN')
            ->permission('EDIT_POST', ruleName: 'author')
            ->child('ROLE_ADMIN', 'EDIT_POST');

        $resolver = new MapRuleResolver([
            'author' => new CallbackRule(static fn ($token, $item, $params): bool => 'mine' === $params),
        ]);

        $manager = $this->manager($items, ['u' => ['ROLE_ADMIN']], $resolver);

        self::assertTrue($manager->can('EDIT_POST', 'mine'));
        self::assertFalse($manager->can('EDIT_POST', 'other'), 'the rule prunes the branch');
    }

    public function testUnresolvableRuleFailsClosed(): void
    {
        $items = (new InMemoryItemStorage())
            ->role('ROLE_ADMIN')
            ->permission('EDIT_POST', ruleName: 'missing')
            ->child('ROLE_ADMIN', 'EDIT_POST');

        $manager = $this->manager($items, ['u' => ['ROLE_ADMIN']], new MapRuleResolver([]));

        self::assertFalse($manager->can('EDIT_POST'));
    }

    public function testSuperAdminShortCircuit(): void
    {
        $items = new InMemoryItemStorage();

        $manager = $this->manager($items, [], null, grantedRoles: ['ROLE_SUPER_ADMIN']);

        self::assertTrue($manager->can('ANYTHING'));
    }

    public function testCycleDoesNotLoopForever(): void
    {
        $items = (new InMemoryItemStorage())
            ->role('A')
            ->role('B')
            ->child('A', 'B')
            ->child('B', 'A');

        $manager = $this->manager($items, ['u' => []]);

        self::assertFalse($manager->can('A'));
    }

    public function testReturnsFalseWhenDisabled(): void
    {
        $manager = $this->manager(new InMemoryItemStorage(), ['u' => ['EDIT_POST']], enabled: false);

        self::assertFalse($manager->can('EDIT_POST'));
    }

    public function testReturnsFalseWithoutToken(): void
    {
        $manager = new AccessManager(
            new InMemoryItemStorage(),
            new InMemoryAssignmentStorage(),
            new MapRuleResolver(),
            new TokenStorage(),
            new StubAuthorizationChecker(),
            new VoterConfig(enabled: true, superAdminRole: 'ROLE_SUPER_ADMIN'),
        );

        self::assertFalse($manager->can('EDIT_POST'));
    }

    /**
     * @param array<string, string[]> $assignments
     * @param string[]                $grantedRoles
     */
    private function manager(
        InMemoryItemStorage $items,
        array $assignments,
        ?RuleResolverInterface $resolver = null,
        bool $enabled = true,
        array $grantedRoles = [],
    ): AccessManager {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken(new InMemoryUser('u', null), 'main'));

        return new AccessManager(
            $items,
            new InMemoryAssignmentStorage($assignments),
            $resolver ?? new MapRuleResolver(),
            $tokenStorage,
            new StubAuthorizationChecker($grantedRoles),
            new VoterConfig(enabled: $enabled, superAdminRole: 'ROLE_SUPER_ADMIN'),
        );
    }
}
