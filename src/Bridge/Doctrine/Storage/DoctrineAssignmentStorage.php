<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Storage;

use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthAssignmentRepository;
use Fedale\RbacBundle\Contract\AssignmentStorageInterface;

final class DoctrineAssignmentStorage implements AssignmentStorageInterface
{
    public function __construct(
        private readonly AuthAssignmentRepository $assignments,
    ) {
    }

    public function getAssignments(string $userIdentifier): array
    {
        return $this->assignments->findItemNamesByUser($userIdentifier);
    }
}
