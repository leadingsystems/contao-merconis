<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Doctrine\DBAL\DriverManager;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\EnableGllSettingsMigrationManager;
use PHPUnit\Framework\TestCase;

final class EnableGllSettingsMigrationManagerTest extends TestCase
{
    public function testApplyUpdatesExistingProductsAndPersistsFlag(): void
    {
        $connection = DriverManager::getConnection([
            'url' => 'sqlite:///:memory:',
        ]);

        $connection->executeStatement("
            CREATE TABLE tl_ls_shop_product (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                enableGll TEXT DEFAULT ''
            )
        ");
        $connection->executeStatement("INSERT INTO tl_ls_shop_product (enableGll) VALUES (''), ('1'), (NULL)");

        $config = [];
        $manager = new EnableGllSettingsMigrationManager(
            $connection,
            static function (string $configKey) use (&$config): mixed {
                return $config[$configKey] ?? null;
            },
            static function (string $configKey, string $configValue) use (&$config): void {
                $config[$configKey] = $configValue;
            }
        );

        self::assertTrue($manager->shouldApplyForSubmission('1'));

        $affectedRows = $manager->apply();

        self::assertSame(2, $affectedRows);
        self::assertSame('1', $config[EnableGllSettingsMigrationManager::CONFIG_FLAG]);
        self::assertTrue($manager->isApplied());
        self::assertFalse($manager->shouldApplyForSubmission('1'));
        self::assertSame(
            3,
            (int) $connection->fetchOne("SELECT COUNT(*) FROM tl_ls_shop_product WHERE enableGll = '1'")
        );
    }

    public function testBuildControlMarkupReflectsCurrentState(): void
    {
        $connection = DriverManager::getConnection([
            'url' => 'sqlite:///:memory:',
        ]);

        $config = [];
        $manager = new EnableGllSettingsMigrationManager(
            $connection,
            static function (string $configKey) use (&$config): mixed {
                return $config[$configKey] ?? null;
            },
            static function (string $configKey, string $configValue) use (&$config): void {
                $config[$configKey] = $configValue;
            }
        );

        $pendingMarkup = $manager->buildControlMarkup(
            'Start migration',
            'Pending state',
            'Applied state'
        );

        self::assertStringContainsString('Start migration', $pendingMarkup);
        self::assertStringContainsString('Pending state', $pendingMarkup);
        self::assertStringNotContainsString('disabled="disabled"', $pendingMarkup);

        $config[EnableGllSettingsMigrationManager::CONFIG_FLAG] = '1';

        $appliedMarkup = $manager->buildControlMarkup(
            'Start migration',
            'Pending state',
            'Applied state'
        );

        self::assertStringContainsString('Applied state', $appliedMarkup);
        self::assertStringContainsString('disabled="disabled"', $appliedMarkup);
    }
}
