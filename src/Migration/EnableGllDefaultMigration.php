<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

final class EnableGllDefaultMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function shouldRun(): bool
    {
        return false;
    }

    public function run(): MigrationResult
    {
        return $this->createResult(
            true,
            'No automatic `enableGll` backfill is executed.'
        );
    }
}
