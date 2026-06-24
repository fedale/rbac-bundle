<?php

namespace Fedale\RbacBundle\Security;

use Fedale\RbacBundle\Contract\RuleInterface;
use Fedale\RbacBundle\Dto\AuthItem;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * RuleInterface driven by an ExpressionLanguage string stored in
 * auth_rule.expression. Built on the fly by RuleResolver (not a service).
 *
 * Variables exposed to the expression:
 *   - user         -> the token's user (or null)
 *   - token        -> the current TokenInterface
 *   - subject      -> can()'s $params (scalar, object or map)
 *   - item         -> the AuthItem being evaluated
 *   - roles        -> the token's role names
 *   - auth_checker -> to use is_granted(...) inside the expression
 *
 * Examples: `subject.getAuthor() == user`,
 *           `user === subject["author"] and subject["post"].isPublished()`.
 */
final class ExpressionRule implements RuleInterface
{
    public function __construct(
        private readonly ExpressionLanguage $expressionLanguage,
        private readonly string $expression,
        private readonly AuthorizationCheckerInterface $authChecker,
    ) {
    }

    public function execute(TokenInterface $token, AuthItem $item, mixed $params = null): bool
    {
        return (bool) $this->expressionLanguage->evaluate($this->expression, [
            'user' => $token->getUser(),
            'token' => $token,
            'subject' => $params,
            'item' => $item,
            'roles' => $token->getRoleNames(),
            'auth_checker' => $this->authChecker,
        ]);
    }
}
