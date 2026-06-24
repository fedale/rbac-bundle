<?php

namespace Fedale\RbacBundle\Cache;

use Fedale\RbacBundle\Contract\AssignmentStorageInterface;

/**
 * PER-REQUEST memoization of assignments (in memory, no PSR-6): can() may be
 * called multiple times for the same user within the same request. No
 * persistent cache because assignments change from the outside and must stay
 * fresh from one request to the next.
 */
final class MemoizedAssignmentStorage implements AssignmentStorageInterface
{
    /** @var array<string, string[]> */
    private array $memo = [];

    public function __construct(
        private readonly AssignmentStorageInterface $inner,
    ) {
    }

    public function getAssignments(string $userIdentifier): array
    {
        return $this->memo[$userIdentifier] ??= $this->inner->getAssignments($userIdentifier);
    }
}
