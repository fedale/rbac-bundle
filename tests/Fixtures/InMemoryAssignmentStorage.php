<?php

namespace Fedale\RbacBundle\Tests\Fixtures;

use Fedale\RbacBundle\Contract\AssignmentStorageInterface;

/**
 * @internal
 */
final class InMemoryAssignmentStorage implements AssignmentStorageInterface
{
    /**
     * @param array<string, string[]> $byUser userIdentifier => item names
     */
    public function __construct(
        private readonly array $byUser = [],
    ) {
    }

    public function getAssignments(string $userIdentifier): array
    {
        return $this->byUser[$userIdentifier] ?? [];
    }
}
