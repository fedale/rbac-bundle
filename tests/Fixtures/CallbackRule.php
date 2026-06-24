<?php

namespace Fedale\RbacBundle\Tests\Fixtures;

use Fedale\RbacBundle\Contract\RuleInterface;
use Fedale\RbacBundle\Dto\AuthItem;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Closure-based RuleInterface, for tests.
 *
 * @internal
 */
final class CallbackRule implements RuleInterface
{
    /** @var callable(TokenInterface, AuthItem, mixed): bool */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function execute(TokenInterface $token, AuthItem $item, mixed $params = null): bool
    {
        return ($this->callback)($token, $item, $params);
    }
}
