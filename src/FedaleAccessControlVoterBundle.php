<?php

namespace Fedale\AccessControlVoterBundle;

use Fedale\AccessControlVoterBundle\Bridge\Doctrine\Provider\DoctrinePermissionRuleProvider;
use Fedale\AccessControlVoterBundle\Cache\CachedPermissionRuleProvider;
use Fedale\AccessControlVoterBundle\Config\VoterConfig;
use Fedale\AccessControlVoterBundle\Contract\PermissionRuleProviderInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class FedaleAccessControlVoterBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                // Ruolo che bypassa qualunque regola. Stringa vuota = disabilita
                // lo short-circuit super-admin.
                ->scalarNode('super_admin_role')
                    ->defaultValue('ROLE_SUPER_ADMIN')
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        // id del pool PSR-6 (default: il pool applicativo)
                        ->scalarNode('pool')->defaultValue('cache.app')->end()
                        // secondi di validita; null = nessuna scadenza
                        ->integerNode('ttl')->defaultNull()->end()
                    ->end()
                ->end()
                // 'doctrine' = provider built-in, oppure l'id di un servizio
                // custom che implementa PermissionRuleProviderInterface.
                ->scalarNode('provider')
                    ->defaultValue('doctrine')
                ->end()
            ->end();
    }

    /**
     * Registra automaticamente il mapping ORM dell'entita del bundle quando si
     * usa il provider Doctrine, cosi' l'app consumer non deve aggiungere una
     * voce doctrine.orm.mappings che punta dentro vendor/. Viene saltato se
     * DoctrineBundle non e' presente o se e' configurato un provider custom.
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
                    'FedaleAccessControlVoter' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => dirname(__DIR__) . '/src/Bridge/Doctrine/Entity',
                        'prefix' => 'Fedale\AccessControlVoterBundle\Bridge\Doctrine\Entity',
                        'alias' => 'FedaleAccessControlVoter',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Il provider e' Doctrine se non configurato (default) o se impostato
     * esplicitamente a 'doctrine'. Legge la config grezza perche'
     * prependExtension gira prima della risoluzione dell'estensione.
     */
    private function isDoctrineProvider(ContainerBuilder $builder): bool
    {
        $provider = 'doctrine';

        foreach ($builder->getExtensionConfig('fedale_access_control_voter') as $config) {
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

        $container->services()
            ->set(VoterConfig::class)
            ->args([
                $config['enabled'],
                $config['super_admin_role'],
            ]);

        // Provider concreto (sorgente delle regole).
        if ('doctrine' === $config['provider']) {
            $container->import(dirname(__DIR__) . '/config/doctrine.php');
            $innerId = DoctrinePermissionRuleProvider::class;
        } else {
            $innerId = $config['provider'];
        }

        $providerId = $innerId;

        // Cache opzionale: decoro il provider con un layer PSR-6.
        if ($config['cache']['enabled']) {
            $container->services()
                ->set(CachedPermissionRuleProvider::class)
                ->args([
                    service($innerId),
                    service($config['cache']['pool']),
                    $config['cache']['ttl'],
                ]);

            $providerId = CachedPermissionRuleProvider::class;
        }

        $container->services()
            ->alias(PermissionRuleProviderInterface::class, $providerId);
    }
}
