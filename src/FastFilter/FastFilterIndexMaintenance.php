<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

use Doctrine\DBAL\Connection;

final class FastFilterIndexMaintenance
{
    public function __construct(
        private readonly Connection $databaseConnection,
        private readonly FastFilterIndexBuilder $indexBuilder,
    ) {
    }

    public function ensureFreshIndex(): bool
    {
        if (!$this->needsRebuild()) {
            return false;
        }

        $this->indexBuilder->rebuild();

        return true;
    }

    public function needsRebuild(): bool
    {
        return $this->isIndexEmpty() || $this->isIndexStale();
    }

    private function isIndexEmpty(): bool
    {
        $indexRowCount = $this->databaseConnection->fetchOne(
            'SELECT COUNT(*) FROM `' . FastFilterIndexBuilder::TABLE_NAME . '`'
        );

        return (int) $indexRowCount === 0;
    }

    private function isIndexStale(): bool
    {
        $lastBackendDataChange = (int) ($GLOBALS['TL_CONFIG']['ls_shop_lastBackendDataChange'] ?? 0);

        if ($lastBackendDataChange <= 0) {
            return false;
        }

        $indexTimestamp = $this->databaseConnection->fetchOne(
            'SELECT COALESCE(MAX(`tstamp`), 0) FROM `' . FastFilterIndexBuilder::TABLE_NAME . '`'
        );

        return (int) $indexTimestamp < $lastBackendDataChange;
    }
}
