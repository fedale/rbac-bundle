<?php

namespace Fedale\RbacBundle\Security;

use Fedale\RbacBundle\Contract\RuleInterface;
use Fedale\RbacBundle\Contract\RuleResolverInterface;
use Fedale\RbacBundle\Contract\RuleStorageInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Turns an auth_rule definition (serviceId | expression) into the executable
 * RuleInterface:
 *   - serviceId  -> service resolved from the ServiceLocator (tag fedale_rbac.rule);
 *   - expression -> an ExpressionRule built on the string.
 *
 * Source-agnostic: depends on RuleStorageInterface, not on Doctrine.
 */
final class RuleResolver implements RuleResolverInterface
{
    public function __construct(
        private readonly RuleStorageInterface $rules,
        private readonly ContainerInterface $ruleLocator,
        private readonly ExpressionLanguage $expressionLanguage,
        private readonly AuthorizationCheckerInterface $authChecker,
    ) {
    }

    public function resolve(string $ruleName): ?RuleInterface
    {
        $def = $this->rules->getRule($ruleName);

        if (null === $def) {
            return null;
        }

        if (null !== $def->serviceId) {
            if (!$this->ruleLocator->has($def->serviceId)) {
                throw new \RuntimeException(sprintf(
                    'auth_rule "%s" references service "%s" which is not registered. '
                    . 'Implement RuleInterface and tag it "fedale_rbac.rule".',
                    $ruleName,
                    $def->serviceId,
                ));
            }

            $service = $this->ruleLocator->get($def->serviceId);

            if (!$service instanceof RuleInterface) {
                throw new \RuntimeException(sprintf(
                    'Service "%s" referenced by auth_rule "%s" does not implement RuleInterface.',
                    $def->serviceId,
                    $ruleName,
                ));
            }

            return $service;
        }

        if (null !== $def->expression) {
            return new ExpressionRule($this->expressionLanguage, $def->expression, $this->authChecker);
        }

        return null;
    }
}
