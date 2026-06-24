<?php

namespace Fedale\RbacBundle\Tests\Twig;

use Fedale\RbacBundle\Contract\AccessManagerInterface;
use Fedale\RbacBundle\Twig\RbacExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RbacExtension::class)]
final class RbacExtensionTest extends TestCase
{
    public function testRegistersTheCanFunction(): void
    {
        $extension = new RbacExtension($this->manager());

        $names = array_map(static fn ($f) => $f->getName(), $extension->getFunctions());

        self::assertContains('can', $names);
    }

    public function testCanDelegatesToTheManager(): void
    {
        self::assertTrue((new RbacExtension($this->manager(true)))->can('EDIT_INVOICE'));
        self::assertFalse((new RbacExtension($this->manager(false)))->can('EDIT_INVOICE'));
    }

    public function testCanForwardsItemAndSubject(): void
    {
        $manager = $this->manager();
        $subject = new \stdClass();

        (new RbacExtension($manager))->can('EDIT_INVOICE', $subject);

        self::assertSame([['EDIT_INVOICE', $subject]], $manager->calls);
    }

    private function manager(bool $result = true): AccessManagerInterface
    {
        return new class($result) implements AccessManagerInterface {
            /** @var list<array{0: string, 1: mixed}> */
            public array $calls = [];

            public function __construct(private readonly bool $result)
            {
            }

            public function can(string $item, mixed $subject = null): bool
            {
                $this->calls[] = [$item, $subject];

                return $this->result;
            }
        };
    }
}
