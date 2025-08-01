<?php

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;


class DynamicAttachmentPathsMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_ls_shop_messages_sent'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_ls_shop_messages_sent');

        return isset($columns['dynamicpdfattachmentpaths']) && !isset($columns['dynamicattachmentpaths']);
    }

    public function run(): MigrationResult
    {

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_ls_shop_messages_sent'])) {
            return $this->createResult(
                false,
                "tl_ls_shop_messages_sent dont exist"
            );
        }
        $columns = $schemaManager->listTableColumns('tl_ls_shop_messages_sent');

        //if dynamicattachmentpaths dont exist add it and get all data from dynamicpdfattachmentpaths
        if ( isset($columns['dynamicpdfattachmentpaths']) || !isset($columns['dynamicattachmentpaths']) ) {
            $this->connection->executeStatement(
                'ALTER TABLE tl_ls_shop_messages_sent ADD dynamicAttachmentPaths BLOB DEFAULT NULL'
            );

            $this->connection->executeStatement(
                "UPDATE tl_ls_shop_messages_sent 
                 SET dynamicAttachmentPaths = dynamicPdfAttachmentPaths 
                 WHERE dynamicPdfAttachmentPaths IS NOT NULL"
            );
        }

        return $this->createResult(
            true
        );

    }
}