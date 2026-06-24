<?php

use Fedale\RbacBundle\Command\CheckAccessCommand;
use Fedale\RbacBundle\Command\ListAssignmentsCommand;
use Fedale\RbacBundle\Command\ListItemsCommand;
use Fedale\RbacBundle\Contract\AccessManagerInterface;
use Fedale\RbacBundle\Contract\RuleResolverInterface;
use Fedale\RbacBundle\Contract\RuleStorageInterface;
use Fedale\RbacBundle\Security\AccessManager;
use Fedale\RbacBundle\Security\AssignmentRolesProvider;
use Fedale\RbacBundle\Security\DynamicVoter;
use Fedale\RbacBundle\Security\RuleResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage as SecurityExpressionLanguage;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

/**
 * Source-agnostic services. The storage interface aliases
 * (ItemStorageInterface, AssignmentStorageInterface, RuleStorageInterface) and
 * the optional services (RbacRoleHierarchy, AssignedRolesInjector) are wired in
 * FedaleRbacBundle::loadExtension().
 */
return static function (ContainerConfigurator $container): void {

    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    // Security ExpressionLanguage: registers is_granted(), is_authenticated()
    // etc. usable inside expression rules. No cache injected.
    $services->set('fedale_rbac.expression_language', SecurityExpressionLanguage::class)
        ->args([]);

    // Resolves auth_rule -> RuleInterface (tagged service or ExpressionRule).
    $services->set(RuleResolver::class)
        ->args([
            service(RuleStorageInterface::class),
            tagged_locator('fedale_rbac.rule'),
            service('fedale_rbac.expression_language'),
            service(AuthorizationCheckerInterface::class),
        ]);
    $services->alias(RuleResolverInterface::class, RuleResolver::class);

    // Auth manager: can($item, $subject). Autowires the storage contracts +
    // token storage + authorization checker + VoterConfig.
    $services->set(AccessManager::class);
    $services->alias(AccessManagerInterface::class, AccessManager::class);

    // Bridge to populate the token with RBAC roles.
    $services->set(AssignmentRolesProvider::class);

    // Native voter (security.voter tag via autoconfigure).
    $services->set(DynamicVoter::class);

    // Read-only console commands (console.command tag via autoconfigure).
    $services->set(ListItemsCommand::class);
    $services->set(ListAssignmentsCommand::class);
    $services->set(CheckAccessCommand::class);
};
