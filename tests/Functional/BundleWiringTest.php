<?php

namespace Fedale\AccessControlVoterBundle\Tests\Functional;

use Fedale\AccessControlVoterBundle\Cache\CachedPermissionRuleProvider;
use Fedale\AccessControlVoterBundle\Config\VoterConfig;
use Fedale\AccessControlVoterBundle\Contract\PermissionRuleProviderInterface;
use Fedale\AccessControlVoterBundle\FedaleAccessControlVoterBundle;
use Fedale\AccessControlVoterBundle\Security\DynamicVoter;
use Fedale\AccessControlVoterBundle\Tests\Fixtures\InMemoryPermissionRuleProvider;
use Fedale\AccessControlVoterBundle\Tests\Fixtures\PermissionRuleFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compila il container reale prodotto dal bundle (loadExtension) e verifica il
 * wiring: provider custom, decorazione cache, voter registrato e VoterConfig.
 */
#[CoversNothing]
final class BundleWiringTest extends TestCase
{
    use PermissionRuleFactory;

    public function testVoterIsRegisteredAndProviderAliasResolvesToCacheDecorator(): void
    {
        $container = $this->compile(cacheEnabled: true);

        self::assertInstanceOf(DynamicVoter::class, $container->get(DynamicVoter::class));
        self::assertInstanceOf(
            CachedPermissionRuleProvider::class,
            $container->get(PermissionRuleProviderInterface::class),
            'con cache attiva il provider deve essere decorato',
        );
    }

    public function testProviderAliasResolvesToInnerWhenCacheDisabled(): void
    {
        $container = $this->compile(cacheEnabled: false);

        self::assertInstanceOf(
            InMemoryPermissionRuleProvider::class,
            $container->get(PermissionRuleProviderInterface::class),
            'senza cache l\'alias punta direttamente al provider configurato',
        );
    }

    public function testVoterConfigCarriesConfiguredSuperAdminRole(): void
    {
        $container = $this->compile(cacheEnabled: false);

        /** @var VoterConfig $config */
        $config = $container->get(VoterConfig::class);

        self::assertTrue($config->enabled);
        self::assertSame('ROLE_SuperAdmin', $config->superAdminRole);
    }

    private function compile(bool $cacheEnabled): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());

        $container->register('app.rules', InMemoryPermissionRuleProvider::class)
            ->setArgument(0, [$this->makeRule(attribute: 'EDIT_INVOICE')]);

        // Security iniettato dopo la compilazione (servizio sintetico).
        $container->register(Security::class)->setSynthetic(true)->setPublic(true);

        $bundleConfig = [
            'provider' => 'app.rules',
            'super_admin_role' => 'ROLE_SuperAdmin',
        ];

        if ($cacheEnabled) {
            $container->register('app.cache_pool')->setSynthetic(true)->setPublic(true);
            $bundleConfig['cache'] = ['enabled' => true, 'pool' => 'app.cache_pool'];
        } else {
            $bundleConfig['cache'] = ['enabled' => false];
        }

        (new FedaleAccessControlVoterBundle())
            ->getContainerExtension()
            ->load([$bundleConfig], $container);

        $container->getDefinition(DynamicVoter::class)->setPublic(true);
        $container->getDefinition(VoterConfig::class)->setPublic(true);
        $container->getAlias(PermissionRuleProviderInterface::class)->setPublic(true);

        $container->compile();

        $container->set(Security::class, $this->makeSecurity());

        if ($cacheEnabled) {
            $container->set('app.cache_pool', new ArrayAdapter());
        }

        return $container;
    }
}
