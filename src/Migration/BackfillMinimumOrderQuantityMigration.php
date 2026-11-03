<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Exception;

class BackfillMinimumOrderQuantityMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['tl_ls_shop_product']) && $this->tableNeedsBackfill(
            $schemaManager->listTableColumns('tl_ls_shop_product'),
            'tl_ls_shop_product',
            'lsShopProductMinimumOrderQuantity'
        )) {
            return true;
        }

        return $schemaManager->tablesExist(['tl_ls_shop_variant']) && $this->tableNeedsBackfill(
            $schemaManager->listTableColumns('tl_ls_shop_variant'),
            'tl_ls_shop_variant',
            'lsShopVariantMinimumOrderQuantity'
        );
    }

    public function run(): MigrationResult
    {
        try {
            $this->connection->executeStatement(
                "UPDATE `tl_ls_shop_product`
                SET `lsShopProductMinimumOrderQuantity` = '0.0000'
                WHERE `lsShopProductMinimumOrderQuantity` IS NULL"
            );

            $this->connection->executeStatement(
                "UPDATE `tl_ls_shop_variant`
                SET `lsShopVariantMinimumOrderQuantity` = '0.0000'
                WHERE `lsShopVariantMinimumOrderQuantity` IS NULL"
            );
        } catch (Exception $exception) {
            return $this->createResult(false, $exception);
        }

        return $this->createResult(
            true,
            'Existing minimum order quantity values were backfilled to 0.0000.'
        );
    }

    /**
     * @param array<string, mixed> $columns
     */
    private function tableNeedsBackfill(
        array $columns,
        string $tableName,
        string $columnName
    ): bool {
        if (!isset($columns[strtolower($columnName)])) {
            return false;
        }

        return (int) $this->connection->fetchOne(
            sprintf(
                'SELECT COUNT(*) FROM `%s` WHERE `%s` IS NULL',
                $tableName,
                $columnName
            )
        ) > 0;
    }
}
