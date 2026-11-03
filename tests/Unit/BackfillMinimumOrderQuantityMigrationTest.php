<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use LeadingSystems\MerconisBundle\Migration\BackfillMinimumOrderQuantityMigration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BackfillMinimumOrderQuantityMigrationTest extends TestCase
{
    private Connection&MockObject $connection;
    private AbstractSchemaManager&MockObject $schemaManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->schemaManager = $this->createMock(AbstractSchemaManager::class);

        $this->connection
            ->method('createSchemaManager')
            ->willReturn($this->schemaManager);
    }

    public function testShouldRunReturnsTrueWhenProductTableNeedsBackfill(): void
    {
        $this->schemaManager
            ->expects(self::once())
            ->method('tablesExist')
            ->with(['tl_ls_shop_product'])
            ->willReturn(true);

        $this->schemaManager
            ->expects(self::once())
            ->method('listTableColumns')
            ->with('tl_ls_shop_product')
            ->willReturn(['lsshopproductminimumorderquantity' => new \stdClass()]);

        $this->connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(*) FROM `tl_ls_shop_product` WHERE `lsShopProductMinimumOrderQuantity` IS NULL'
            )
            ->willReturn(2);

        $migration = new BackfillMinimumOrderQuantityMigration($this->connection);

        self::assertTrue($migration->shouldRun());
    }

    public function testShouldRunReturnsTrueWhenOnlyVariantTableNeedsBackfill(): void
    {
        $this->schemaManager
            ->expects(self::exactly(2))
            ->method('tablesExist')
            ->willReturnCallback(
                static fn (array $tables): bool => in_array($tables[0], ['tl_ls_shop_product', 'tl_ls_shop_variant'], true)
            );

        $this->schemaManager
            ->expects(self::exactly(2))
            ->method('listTableColumns')
            ->willReturnCallback(
                static function (string $tableName): array {
                    return match ($tableName) {
                        'tl_ls_shop_product' => ['lsshopproductminimumorderquantity' => new \stdClass()],
                        'tl_ls_shop_variant' => ['lsshopvariantminimumorderquantity' => new \stdClass()],
                        default => [],
                    };
                }
            );

        $this->connection
            ->expects(self::exactly(2))
            ->method('fetchOne')
            ->willReturnCallback(
                static function (string $sql): int {
                    return match ($sql) {
                        'SELECT COUNT(*) FROM `tl_ls_shop_product` WHERE `lsShopProductMinimumOrderQuantity` IS NULL' => 0,
                        'SELECT COUNT(*) FROM `tl_ls_shop_variant` WHERE `lsShopVariantMinimumOrderQuantity` IS NULL' => 3,
                        default => 0,
                    };
                }
            );

        $migration = new BackfillMinimumOrderQuantityMigration($this->connection);

        self::assertTrue($migration->shouldRun());
    }

    public function testShouldRunReturnsFalseWhenColumnsAreMissing(): void
    {
        $this->schemaManager
            ->expects(self::exactly(2))
            ->method('tablesExist')
            ->willReturn(true);

        $this->schemaManager
            ->expects(self::exactly(2))
            ->method('listTableColumns')
            ->willReturn([]);

        $this->connection
            ->expects(self::never())
            ->method('fetchOne');

        $migration = new BackfillMinimumOrderQuantityMigration($this->connection);

        self::assertFalse($migration->shouldRun());
    }

    public function testRunBackfillsBothTables(): void
    {
        $executedStatements = [];

        $this->connection
            ->expects(self::exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(
                static function (string $sql) use (&$executedStatements): int {
                    $executedStatements[] = $sql;

                    return 1;
                }
            );

        $migration = new BackfillMinimumOrderQuantityMigration($this->connection);
        $result = $migration->run();

        self::assertTrue($result->isSuccessful());
        self::assertSame(
            'Existing minimum order quantity values were backfilled to 0.0000.',
            $result->getMessage()
        );
        self::assertSame(
            [
                "UPDATE `tl_ls_shop_product`\n                SET `lsShopProductMinimumOrderQuantity` = '0.0000'\n                WHERE `lsShopProductMinimumOrderQuantity` IS NULL",
                "UPDATE `tl_ls_shop_variant`\n                SET `lsShopVariantMinimumOrderQuantity` = '0.0000'\n                WHERE `lsShopVariantMinimumOrderQuantity` IS NULL",
            ],
            $executedStatements
        );
    }
}
