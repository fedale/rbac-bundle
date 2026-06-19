<?php

namespace Fedale\AccessControlVoterBundle\Bridge\Doctrine\Provider;

use Fedale\AccessControlVoterBundle\Bridge\Doctrine\Repository\PermissionRuleRepository;
use Fedale\AccessControlVoterBundle\Contract\PermissionRuleProviderInterface;

final class DoctrinePermissionRuleProvider implements PermissionRuleProviderInterface
{
    public function __construct(
        private readonly PermissionRuleRepository $repository
    ) {
    }

    public function findActive(): iterable
    {
        foreach ($this->repository->findActive() as $entity) {
            yield $entity->toDto();
        }
    }

    public function findByAttribute(string $attribute): iterable
    {
        foreach ($this->repository->findActiveByAttribute($attribute) as $entity) {
            yield $entity->toDto();
        }
    }
}
