<?php

namespace Fedale\RbacBundle\Dto;

/**
 * Immutable definition of a "Rule" (mirror of Yii2 RBAC's `auth_rule`).
 *
 * Intentional divergence from Yii2 (which serializes a PHP object into the
 * `data` column): here the logic is referenced in two alternative ways, both
 * DB-friendly:
 *   - $serviceId  -> id of a service implementing RuleInterface;
 *   - $expression -> ExpressionLanguage string (evaluated by ExpressionRule).
 *
 * Exactly one of the two is set. $data is kept for any metadata.
 */
final readonly class AuthRule
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $name,
        public ?string $serviceId = null,
        public ?string $expression = null,
        public array $data = [],
    ) {
    }
}
