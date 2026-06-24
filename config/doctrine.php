<?php

use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthAssignmentRepository;
use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthItemChildRepository;
use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthItemRepository;
use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthRuleRepository;
use Fedale\RbacBundle\Bridge\Doctrine\Storage\DoctrineAssignmentStorage;
use Fedale\RbacBundle\Bridge\Doctrine\Storage\DoctrineItemStorage;
use Fedale\RbacBundle\Bridge\Doctrine\Storage\DoctrineRuleStorage;
use Fedale\RbacBundle\Command\AddChildCommand;
use Fedale\RbacBundle\Command\AddItemCommand;
use Fedale\RbacBundle\Command\AssignCommand;
use Fedale\RbacBundle\Command\RemoveChildCommand;
use Fedale\RbacBundle\Command\RemoveItemCommand;
use Fedale\RbacBundle\Command\RevokeCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Doctrine provider wiring, imported only when `provider: doctrine`. Requires
 * doctrine/orm and doctrine/doctrine-bundle in the consumer app. The storage
 * interface aliases (with optional cache decorators) and the RbacManager are
 * wired in FedaleRbacBundle::loadExtension().
 */
return static function (ContainerConfigurator $container): void {

    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set(AuthItemRepository::class);
    $services->set(AuthItemChildRepository::class);
    $services->set(AuthAssignmentRepository::class);
    $services->set(AuthRuleRepository::class);

    $services->set(DoctrineItemStorage::class);
    $services->set(DoctrineAssignmentStorage::class);
    $services->set(DoctrineRuleStorage::class);

    // Management commands (write API, Doctrine-only): tagged console.command via
    // autoconfigure; they autowire RbacManagerInterface (aliased in loadExtension).
    $services->set(AddItemCommand::class);
    $services->set(RemoveItemCommand::class);
    $services->set(AddChildCommand::class);
    $services->set(RemoveChildCommand::class);
    $services->set(AssignCommand::class);
    $services->set(RevokeCommand::class);
};
