<?php

use Fedale\AccessControlVoterBundle\Command\ListPermissionRulesCommand;
use Fedale\AccessControlVoterBundle\Security\DynamicVoter;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {

    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    // Voter nativo (tag security.voter via autoconfigure). Dipende da
    // PermissionRuleProviderInterface (alias cablato in loadExtension),
    // Security e VoterConfig.
    $services->set(DynamicVoter::class);

    // Comando console di sola lettura (tag console.command via autoconfigure).
    $services->set(ListPermissionRulesCommand::class);
};
