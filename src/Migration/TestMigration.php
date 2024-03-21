<?php

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class TestMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $stmt = $this->connection->prepare("
            SELECT * 
            FROM `tl_content` 
            WHERE type='htmlWrapperStart' OR type='htmlWrapperStop'
        ");

        $value = $stmt->execute();


        return true;
    }

    public function run(): MigrationResult
    {

        /*
        $stmt = $this->connection->prepare("
            UPDATE
                tl_log
            SET
                text = 'testausgabe'
        ");

        $stmt->execute();*/

        return $this->createResult(
            true,
            'Ausgeführt'
        );
    }
}