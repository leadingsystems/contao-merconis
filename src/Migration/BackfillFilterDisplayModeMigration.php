<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Exception;

class BackfillFilterDisplayModeMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_ls_shop_filter_fields'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_ls_shop_filter_fields');
        if (!isset($columns['filterdisplaymode'])) {
            return false;
        }

        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM `tl_ls_shop_filter_fields` WHERE `filterDisplayMode` = '' OR `filterDisplayMode` IS NULL"
        ) > 0;
    }

    public function run(): MigrationResult
    {
        try {
            $this->connection->executeStatement(
                "UPDATE `tl_ls_shop_filter_fields`
                SET `filterDisplayMode` = 'showMoreLess'
                WHERE `filterDisplayMode` = '' OR `filterDisplayMode` IS NULL"
            );
        } catch (Exception $exception) {
            return $this->createResult(false, $exception);
        }

        return $this->createResult(
            true,
            'Existing filter fields were backfilled to the showMoreLess display mode.'
        );
    }
}
