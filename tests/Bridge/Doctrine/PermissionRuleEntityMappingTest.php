<?php

namespace Fedale\AccessControlVoterBundle\Tests\Bridge\Doctrine;

use Fedale\AccessControlVoterBundle\Bridge\Doctrine\Entity\PermissionRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;

/**
 * Valida la mappatura ORM dell'entita usando un EntityManager reale, senza
 * connettersi ad alcun database (serverVersion impostata): la lettura dei
 * metadata e la generazione del DDL non richiedono un server.
 */
#[CoversClass(PermissionRule::class)]
final class PermissionRuleEntityMappingTest extends TestCase
{
    protected function setUp(): void
    {
        // ORM 3 + var-exporter 8 usa i lazy object nativi di PHP 8.4; su PHP 8.3
        // enableNativeLazyObjects() lancia. La validazione del mapping resta
        // coperta quando la suite gira su 8.4+.
        if (\PHP_VERSION_ID < 80400) {
            self::markTestSkipped('La validazione del mapping ORM richiede PHP 8.4+ (lazy object nativi).');
        }
    }

    public function testOrmMappingIsValid(): void
    {
        $errors = (new SchemaValidator($this->entityManager()))->validateMapping();

        $entityErrors = $errors[PermissionRule::class] ?? [];

        self::assertSame([], $entityErrors, implode("\n", $entityErrors));
    }

    public function testGeneratedSchemaHasTableAndAttributeColumn(): void
    {
        $em = $this->entityManager();
        $metadata = $em->getClassMetadata(PermissionRule::class);

        $ddl = implode("\n", (new SchemaTool($em))->getCreateSchemaSql([$metadata]));

        self::assertStringContainsString('permission_rule', $ddl);
        self::assertStringContainsString('attribute', $ddl);
    }

    private function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [dirname(__DIR__, 3) . '/src/Bridge/Doctrine/Entity'],
            isDevMode: true,
        );

        // ORM 3 + symfony/var-exporter 8: usa i lazy object nativi di PHP 8.4
        // (il LazyGhostTrait non esiste piu).
        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection(
            [
                'driver' => 'pdo_mysql',
                'serverVersion' => '8.0',
                'host' => '127.0.0.1',
                'dbname' => 'test',
                'user' => 'root',
                'password' => '',
            ],
            $config,
        );

        return new EntityManager($connection, $config);
    }
}
