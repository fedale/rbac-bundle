<?php

namespace Fedale\RbacBundle\Contract;

use Fedale\RbacBundle\Enum\AuthItemType;

/**
 * Write API for the RBAC graph. Optional and provider-specific: the bundle
 * ships a Doctrine implementation (DoctrineRbacManager), available when
 * provider: doctrine. Every mutation invalidates the affected cache.
 */
interface RbacManagerInterface
{
    public function addItem(string $name, AuthItemType $type, ?string $description = null, ?string $ruleName = null): void;

    public function removeItem(string $name): void;

    public function addChild(string $parent, string $child): void;

    public function removeChild(string $parent, string $child): void;

    public function assign(string $userId, string $item): void;

    public function revoke(string $userId, string $item): void;

    public function addRule(string $name, ?string $serviceId = null, ?string $expression = null): void;

    public function removeRule(string $name): void;
}
