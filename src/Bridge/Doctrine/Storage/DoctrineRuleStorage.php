<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Storage;

use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthRuleRepository;
use Fedale\RbacBundle\Contract\RuleStorageInterface;
use Fedale\RbacBundle\Dto\AuthRule;

final class DoctrineRuleStorage implements RuleStorageInterface
{
    public function __construct(
        private readonly AuthRuleRepository $rules,
    ) {
    }

    public function getRule(string $name): ?AuthRule
    {
        return $this->rules->find($name)?->toDto();
    }
}
