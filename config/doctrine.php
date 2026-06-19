<?php

use Fedale\AccessControlVoterBundle\Bridge\Doctrine\Provider\DoctrinePermissionRuleProvider;
use Fedale\AccessControlVoterBundle\Bridge\Doctrine\Repository\PermissionRuleRepository;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Wiring del provider Doctrine, importato solo quando `provider: doctrine`.
 * Richiede doctrine/orm e doctrine/doctrine-bundle nell'app consumer.
 * L'alias di PermissionRuleProviderInterface (con eventuale wrapper di cache)
 * e' gestito centralmente in FedaleAccessControlVoterBundle::loadExtension().
 */
return static function (ContainerConfigurator $container): void {

    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set(PermissionRuleRepository::class);
    $services->set(DoctrinePermissionRuleProvider::class);
};
