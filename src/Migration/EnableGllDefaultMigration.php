<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\Config;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Exception;

final class EnableGllDefaultMigration extends AbstractMigration
{
    private const CONFIG_FLAG = 'ls_shop_enableGllMigrationApplied';
    private static bool $hasRun = false;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function shouldRun(): bool
    {
        if (self::$hasRun) {
            return false;
        }

        if (Config::get(self::CONFIG_FLAG)) {
            return false;
        }

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_ls_shop_product'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_ls_shop_product');

        return isset($columns['enablegll']) || isset($columns['enableGll']);
    }

    public function run(): MigrationResult
    {
        try {
            $this->connection->executeStatement("
                UPDATE `tl_ls_shop_product`
                SET `enableGll` = '1'
            ");

            Config::persist(self::CONFIG_FLAG, '1');
            Config::set(self::CONFIG_FLAG, '1');
            self::$hasRun = true;
        } catch (Exception $exception) {
            return $this->createResult(false, $exception);
        }

        return $this->createResult(
            true,
            'GLL was enabled for all existing products. Review digital products and services manually and disable GLL where required.'
        );
    }
}
