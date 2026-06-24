<?php

namespace Fedale\RbacBundle\Contract;

/**
 * Yii2-style auth manager: the equivalent of Yii::$app->user->can($item, $params).
 *
 * $item is an item name — role *or* permission (in Yii2 can() applies to both).
 * Recommended convention: isGranted(ROLE) for a plain role check; can(item,
 * $subject) when you need a rule's contextual gating (even on a role), which
 * isGranted() cannot do.
 */
interface AccessManagerInterface
{
    public function can(string $item, mixed $subject = null): bool;
}
