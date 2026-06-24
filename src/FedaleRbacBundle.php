<?php

namespace Fedale\RbacBundle;

use Fedale\RbacBundle\Bridge\Doctrine\DoctrineRbacManager;
use Fedale\RbacBundle\Bridge\Doctrine\Storage\DoctrineAssignmentStorage;
use Fedale\RbacBundle\Bridge\Doctrine\Storage\DoctrineItemStorage;
use Fedale\RbacBundle\Bridge\Doctrine\Storage\DoctrineRuleStorage;
use Fedale\RbacBundle\Cache\CachedItemStorage;
use Fedale\RbacBundle\Cache\CachedRuleStorage;
use Fedale\RbacBundle\Cache\MemoizedAssignmentStorage;
use Fedale\RbacBundle\Config\VoterConfig;
use Fedale\RbacBundle\Contract\AssignmentStorageInterface;
use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Fedale\RbacBundle\Contract\RbacManagerInterface;
use Fedale\RbacBundle\Contract\RuleInterface;
use Fedale\RbacBundle\Contract\RuleStorageInterface;
use Fedale\RbacBundle\Security\AssignedRolesInjector;
use Fedale\RbacBundle\Security\AssignmentRolesProvider;
use Fedale\RbacBundle\Security\RbacRoleHierarchy;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class FedaleRbacBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                // Role that bypasses any rule. Empty string = disable the
                // super-admin short-circuit.
                ->scalarNode('super_admin_role')
                    ->defaultValue('ROLE_SUPER_ADMIN')
                ->end()
                // Decorates security.role_hierarchy with the role->role hierarchy
                // read from the RBAC graph (auth_item_child) instead of security.yaml.
                ->booleanNode('override_role_hierarchy')->defaultTrue()->end()
                // Fallback: a listener merges the auth_assignment roles into the
                // token at creation time. Default OFF: the primary mechanism is
                // User::getRoles() via AssignedRolesUserProvider (token always fresh).
                ->booleanNode('inject_assigned_roles')->defaultFalse()->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        // id of the PSR-6 pool (default: the application pool)
                        ->scalarNode('pool')->defaultValue('cache.app')->end()
                        // validity in seconds; null = no expiration
                        ->integerNode('ttl')->defaultNull()->end()
                    ->end()
                ->end()
                // 'doctrine' = built-in provider (4 tables). Otherwise the app
                // must register the services for the 3 storage interfaces.
                ->scalarNode('provider')
                    ->defaultValue('doctrine')
                ->end()
            ->end();
    }

    /**
     * Automatically tags the services implementing RuleInterface, so the app's
     * custom "Rules" are reachable from the RuleResolver via the locator.
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(RuleInterface::class)
            ->addTag('fedale_rbac.rule');
    }

    /**
     * Automatically registers the ORM mapping of the bundle's entities when the
     * Doctrine provider is used, so the consumer app does not have to add a
     * doctrine.orm.mappings entry pointing inside vendor/.
     */
    public function prependExtension(
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        if (!$builder->hasExtension('doctrine')) {
            return;
        }

        if (!$this->isDoctrineProvider($builder)) {
            return;
        }

        $container->extension('doctrine', [
            'orm' => [
                'mappings' => [
                    'FedaleRbac' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => dirname(__DIR__) . '/src/Bridge/Doctrine/Entity',
                        'prefix' => 'Fedale\RbacBundle\Bridge\Doctrine\Entity',
                        'alias' => 'FedaleRbac',
                    ],
                ],
            ],
        ]);
    }

    private function isDoctrineProvider(ContainerBuilder $builder): bool
    {
        $provider = 'doctrine';

        foreach ($builder->getExtensionConfig('fedale_rbac') as $config) {
            if (isset($config['provider'])) {
                $provider = $config['provider'];
            }
        }

        return 'doctrine' === $provider;
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {

        $container->import(dirname(__DIR__) . '/config/services.php');

        $services = $container->services();

        $services->set(VoterConfig::class)
            ->args([
                $config['enabled'],
                $config['super_admin_role'],
            ]);

        $cacheEnabled = $config['cache']['enabled'];
        $pool = $config['cache']['pool'];
        $ttl = $config['cache']['ttl'];

        // Built-in Doctrine provider: wires storage + decorators and the aliases
        // for the 3 interfaces. With a custom provider, the app registers the
        // services for ItemStorageInterface / AssignmentStorageInterface / RuleStorageInterface.
        if ('doctrine' === $config['provider']) {
            $container->import(dirname(__DIR__) . '/config/doctrine.php');

            if ($cacheEnabled) {
                $services->set(CachedItemStorage::class)
                    ->args([service(DoctrineItemStorage::class), service($pool), $ttl]);
                $services->alias(ItemStorageInterface::class, CachedItemStorage::class);

                $services->set(CachedRuleStorage::class)
                    ->args([service(DoctrineRuleStorage::class), service($pool), $ttl]);
                $services->alias(RuleStorageInterface::class, CachedRuleStorage::class);
            } else {
                $services->alias(ItemStorageInterface::class, DoctrineItemStorage::class);
                $services->alias(RuleStorageInterface::class, DoctrineRuleStorage::class);
            }

            // Per-request memoization of assignments (always).
            $services->set(MemoizedAssignmentStorage::class)
                ->args([service(DoctrineAssignmentStorage::class)]);
            $services->alias(AssignmentStorageInterface::class, MemoizedAssignmentStorage::class);

            // Write API (management): mutations + cache invalidation.
            $services->set(DoctrineRbacManager::class)
                ->args([
                    service('doctrine.orm.entity_manager'),
                    $cacheEnabled ? service($pool) : null,
                ]);
            $services->alias(RbacManagerInterface::class, DoctrineRbacManager::class);
        }

        // DB-driven role->role hierarchy: decorates security.role_hierarchy.
        if ($config['override_role_hierarchy']) {
            $services->set(RbacRoleHierarchy::class)
                ->decorate('security.role_hierarchy')
                ->args([service(ItemStorageInterface::class)]);
        }

        // Optional fallback to inject roles into the token.
        if ($config['inject_assigned_roles']) {
            $services->set(AssignedRolesInjector::class)
                ->args([service(AssignmentRolesProvider::class)])
                ->tag('kernel.event_listener', [
                    'event' => AuthenticationTokenCreatedEvent::class,
                ]);
        }
    }
}
