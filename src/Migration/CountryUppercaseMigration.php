<?php


namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class CountryUppercaseMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_member'])) {
            return false;
        }

        if (!isset($schemaManager->listTableColumns('tl_member')['country_alternative'])) {
            return false;
        }

        $test = $this->connection->fetchOne('SELECT TRUE FROM tl_member WHERE BINARY country_alternative!=BINARY UPPER(country_alternative) LIMIT 1');

        return false !== $test;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('UPDATE tl_member SET country_alternative=UPPER(country_alternative) WHERE BINARY country_alternative!=BINARY UPPER(country_alternative)');

        return $this->createResult(true);
    }
}
