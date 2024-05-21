<?php

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class WrapperMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return "Wrapper Migration";
    }

    public function shouldRun(): bool
    {

        $stmt = $this->connection->prepare("
            SELECT *
            FROM `tl_content` 
            WHERE type='htmlWrapperStart' OR type='htmlWrapperStop'
        ");
        $result = $stmt->execute();

        return ((bool)$result->fetchNumeric());
    }

    public function run(): MigrationResult
    {

        $stmtStart = $this->connection->prepare("
                UPDATE
                    tl_content
                SET
                    type = 'rsce_htmlwrapper-start'
                WHERE 
                    type = 'htmlWrapperStart'
        ");
        $resultStart = $stmtStart->execute();

        $stmtStop = $this->connection->prepare("
                        UPDATE
                            tl_content
                        SET
                            type = 'rsce_htmlwrapper-stop'
                        WHERE
                            type = 'htmlWrapperStop'
                ");
        $resultStop = $stmtStop->execute();

        return $this->createResult(
            true,
            $resultStart->rowCount()+$resultStop->rowCount().' Wrapper wurden geupdated'
        );
    }
}