<?php

namespace Fedale\RbacBundle\Dto;

use Fedale\RbacBundle\Enum\AuthItemType;

/**
 * Immutable value object of an auth item (role or permission), source-agnostic
 * (Doctrine, YAML, API, ...). Mirror of Yii2 RBAC's `auth_item` table.
 *
 * Conceptual mapping with Yii2:
 *   - $name        <-> auth_item.name (PK)
 *   - $type        <-> auth_item.type (1=role, 2=permission)
 *   - $description <-> auth_item.description
 *   - $ruleName    <-> auth_item.rule_name (FK -> auth_rule.name)
 *   - $data        <-> auth_item.data
 */
final readonly class AuthItem
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $name,
        public AuthItemType $type,
        public ?string $description = null,
        public ?string $ruleName = null,
        public array $data = [],
    ) {
    }

    public function isRole(): bool
    {
        return AuthItemType::ROLE === $this->type;
    }

    public function isPermission(): bool
    {
        return AuthItemType::PERMISSION === $this->type;
    }
}
