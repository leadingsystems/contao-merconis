<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterIndexBuilder;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterIndexMaintenance;
use PHPUnit\Framework\TestCase;

final class FastFilterIndexMaintenanceTest extends TestCase
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $previousConfig = null;

    protected function setUp(): void
    {
        $this->previousConfig = $GLOBALS['TL_CONFIG'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->previousConfig === null) {
            unset($GLOBALS['TL_CONFIG']);
            return;
        }

        $GLOBALS['TL_CONFIG'] = $this->previousConfig;
    }

    public function testEnsureFreshIndexRebuildsStaleIndex(): void
    {
        $GLOBALS['TL_CONFIG'] = ['ls_shop_lastBackendDataChange' => 200];
        $databaseConnection = $this->createMock(Connection::class);
        $databaseConnection
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(1, 100);
        $databaseConnection
            ->expects(self::exactly(6))
            ->method('executeStatement')
            ->willReturn(0);

        $indexBuilder = new FastFilterIndexBuilder($databaseConnection);
        $indexMaintenance = new FastFilterIndexMaintenance($databaseConnection, $indexBuilder);

        self::assertTrue($indexMaintenance->ensureFreshIndex());
    }

    public function testEnsureFreshIndexSkipsCurrentIndex(): void
    {
        $GLOBALS['TL_CONFIG'] = ['ls_shop_lastBackendDataChange' => 100];
        $databaseConnection = $this->createMock(Connection::class);
        $databaseConnection
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(1, 200);
        $databaseConnection
            ->expects(self::never())
            ->method('executeStatement');

        $indexBuilder = new FastFilterIndexBuilder($databaseConnection);
        $indexMaintenance = new FastFilterIndexMaintenance($databaseConnection, $indexBuilder);

        self::assertFalse($indexMaintenance->ensureFreshIndex());
    }
}
