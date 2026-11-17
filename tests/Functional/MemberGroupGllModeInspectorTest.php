<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

require_once __DIR__ . '/../../src/Resources/contao/helpers/ls_shop_generalHelper.php';

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupGllModeInspector;
use Merconis\Core\ls_shop_generalHelper;
use PHPUnit\Framework\TestCase;

final class MemberGroupGllModeInspectorTest extends TestCase
{
    private const GLL_ANNOUNCEMENT = 'Member group announcement';

    private mixed $originalTlConfig;
    private mixed $originalTlLang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
        $this->originalTlLang = $GLOBALS['TL_LANG'] ?? null;

        $GLOBALS['TL_CONFIG'] = [];
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['dashboard']['gllAnnouncement'] = self::GLL_ANNOUNCEMENT;
    }

    protected function tearDown(): void
    {
        if ($this->originalTlConfig === null) {
            unset($GLOBALS['TL_CONFIG']);
        } else {
            $GLOBALS['TL_CONFIG'] = $this->originalTlConfig;
        }

        if ($this->originalTlLang === null) {
            unset($GLOBALS['TL_LANG']);
        } else {
            $GLOBALS['TL_LANG'] = $this->originalTlLang;
        }

        parent::tearDown();
    }

    public function testCountsEmptyAndNullGllModeAsNotSet(): void
    {
        $connection = $this->createMemberGroupConnection(["''", 'NULL', "'on'", "'off'", "'blocked'"]);
        $inspector = new MemberGroupGllModeInspector($connection);

        self::assertSame(2, $inspector->countMemberGroupsWithoutGllMode());
    }

    public function testCountsNoMemberGroupWhenAllHaveGllMode(): void
    {
        $connection = $this->createMemberGroupConnection(["'on'", "'off'", "'blocked'"]);
        $inspector = new MemberGroupGllModeInspector($connection);

        self::assertSame(0, $inspector->countMemberGroupsWithoutGllMode());
    }

    public function testCountsNoMemberGroupWithoutMemberGroups(): void
    {
        $connection = $this->createMemberGroupConnection([]);
        $inspector = new MemberGroupGllModeInspector($connection);

        self::assertSame(0, $inspector->countMemberGroupsWithoutGllMode());
    }

    public function testDashboardShowsGllAnnouncementForEmptyMemberGroup(): void
    {
        $connection = $this->createMemberGroupConnection(["'on'", "''"]);

        $markup = ls_shop_generalHelper::getMerconisSystemMessages(new MemberGroupGllModeInspector($connection));

        self::assertStringContainsString(self::GLL_ANNOUNCEMENT, $markup);
    }

    public function testDashboardShowsGllAnnouncementForNullMemberGroup(): void
    {
        $connection = $this->createMemberGroupConnection(["'on'", 'NULL']);

        $markup = ls_shop_generalHelper::getMerconisSystemMessages(new MemberGroupGllModeInspector($connection));

        self::assertStringContainsString(self::GLL_ANNOUNCEMENT, $markup);
    }

    public function testDashboardHidesGllAnnouncementWhenAllMemberGroupsHaveGllMode(): void
    {
        $connection = $this->createMemberGroupConnection(["'on'", "'off'", "'blocked'"]);

        $markup = ls_shop_generalHelper::getMerconisSystemMessages(new MemberGroupGllModeInspector($connection));

        self::assertStringNotContainsString(self::GLL_ANNOUNCEMENT, $markup);
    }

    public function testDashboardHidesGllAnnouncementWithoutMemberGroups(): void
    {
        $connection = $this->createMemberGroupConnection([]);

        $markup = ls_shop_generalHelper::getMerconisSystemMessages(new MemberGroupGllModeInspector($connection));

        self::assertStringNotContainsString(self::GLL_ANNOUNCEMENT, $markup);
    }

    /**
     * @param list<string> $gllModeSqlLiterals
     */
    private function createMemberGroupConnection(array $gllModeSqlLiterals): Connection
    {
        $connection = DriverManager::getConnection([
            'url' => 'sqlite:///:memory:',
        ]);

        $connection->executeStatement("
            CREATE TABLE tl_member_group (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL DEFAULT '',
                lsShopGllMode TEXT NULL
            )
        ");

        foreach ($gllModeSqlLiterals as $memberGroupIndex => $gllModeSqlLiteral) {
            $connection->executeStatement(
                'INSERT INTO tl_member_group (name, lsShopGllMode) VALUES (?, ' . $gllModeSqlLiteral . ')',
                ['Group ' . $memberGroupIndex]
            );
        }

        return $connection;
    }
}
