<?php

namespace Fedale\RbacBundle\Tests\Security;

use Fedale\RbacBundle\Security\RbacRoleHierarchy;
use Fedale\RbacBundle\Tests\Fixtures\InMemoryItemStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RbacRoleHierarchy::class)]
final class RbacRoleHierarchyTest extends TestCase
{
    public function testExpandsRoleToRoleTransitively(): void
    {
        $items = (new InMemoryItemStorage())
            ->role('ROLE_ADMIN')
            ->role('ROLE_EDITOR')
            ->role('ROLE_USER')
            ->child('ROLE_ADMIN', 'ROLE_EDITOR')
            ->child('ROLE_EDITOR', 'ROLE_USER');

        $reachable = (new RbacRoleHierarchy($items))->getReachableRoleNames(['ROLE_ADMIN']);

        sort($reachable);
        self::assertSame(['ROLE_ADMIN', 'ROLE_EDITOR', 'ROLE_USER'], $reachable);
    }

    public function testExcludesPermissionChildren(): void
    {
        $items = (new InMemoryItemStorage())
            ->role('ROLE_EDITOR')
            ->permission('EDIT_POST')
            ->child('ROLE_EDITOR', 'EDIT_POST');

        $reachable = (new RbacRoleHierarchy($items))->getReachableRoleNames(['ROLE_EDITOR']);

        self::assertSame(['ROLE_EDITOR'], $reachable, 'permissions do not enter the role hierarchy');
    }

    public function testKeepsUnknownRolesAsLeaves(): void
    {
        $reachable = (new RbacRoleHierarchy(new InMemoryItemStorage()))
            ->getReachableRoleNames(['ROLE_X', 'ROLE_Y']);

        sort($reachable);
        self::assertSame(['ROLE_X', 'ROLE_Y'], $reachable);
    }
}
