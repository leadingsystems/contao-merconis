<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class FilterModeMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('tl_layout');

        return isset($columns['ls_shop_activatefilter']);
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('tl_layout');

        if (!isset($columns['ls_shop_filtermode'])) {
            $this->connection->executeStatement(
                "ALTER TABLE tl_layout ADD ls_shop_filterMode varchar(16) NOT NULL default ''"
            );
        }

        $this->connection->executeStatement(
            "UPDATE tl_layout SET ls_shop_filterMode = 'legacy' WHERE ls_shop_activateFilter = '1' AND ls_shop_filterMode = ''"
        );

        $this->connection->executeStatement(
            "ALTER TABLE tl_layout DROP ls_shop_activateFilter"
        );

        return $this->createResult(true, 'Migrated ls_shop_activateFilter to ls_shop_filterMode and removed old column.');
    }
}
