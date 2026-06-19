<?php

namespace Fedale\AccessControlVoterBundle\Tests\Dto;

use Fedale\AccessControlVoterBundle\Dto\PermissionRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PermissionRule::class)]
final class PermissionRuleTest extends TestCase
{
    public function testRejectsEmptyAttribute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty attribute');

        new PermissionRule(
            id: 1,
            name: 'broken',
            reason: null,
            attribute: '   ',
            subjectType: null,
            condition: null,
            roles: [],
            allow: true,
            sort: 0,
            active: true,
        );
    }

    public function testKeepsProvidedValues(): void
    {
        $rule = new PermissionRule(
            id: 5,
            name: 'edit',
            reason: 'editors only',
            attribute: 'EDIT_INVOICE',
            subjectType: \stdClass::class,
            condition: 'app.condition.owns_invoice',
            roles: ['ROLE_EDITOR'],
            allow: false,
            sort: 3,
            active: true,
        );

        self::assertSame('EDIT_INVOICE', $rule->attribute);
        self::assertSame(\stdClass::class, $rule->subjectType);
        self::assertSame('app.condition.owns_invoice', $rule->condition);
        self::assertSame(['ROLE_EDITOR'], $rule->roles);
        self::assertFalse($rule->allow);
    }
}
