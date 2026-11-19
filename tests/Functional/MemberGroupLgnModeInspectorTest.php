<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

require_once __DIR__ . '/../../src/Resources/contao/helpers/ls_shop_generalHelper.php';

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupLgnModeInspector;
use Merconis\Core\ls_shop_generalHelper;
use PHPUnit\Framework\TestCase;

final class MemberGroupLgnModeInspectorTest extends TestCase
{
    private const LGN_ANNOUNCEMENT = 'Member group announcement';

    private mixed $originalTlConfig;
    private mixed $originalTlLang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
        $this->originalTlLang = $GLOBALS['TL_LANG'] ?? null;

        $GLOBALS['TL_CONFIG'] = [];
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['dashboard']['lgnAnnouncement'] = self::LGN_ANNOUNCEMENT;
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

    public function testCountsEmptyAndNullLgnModeAsNotSet(): void
    {
        $connection = $this->createMemberGroupConnection(["''", 'NULL', "'on'", "'off'", "'blocked'"]);
        $inspector = new MemberGroupLgnModeInspector($connection);

        self::assertSame(2, $inspector->countMemberGroupsWithoutLgnMode());
    }

    public function testCountsNoMemberGroupWhenAllHaveLgnMode(): void
    {
        $connection = $this->createMemberGroupConnection(["'on'", "'off'", "'blocked'"]);
        $inspector = new MemberGroupLgnModeInspector($connection);

        self::assertSame(0, $inspector->countMemberGroupsWithoutLgnMode());
    }

    public function testCountsNoMemberGroupWithoutMemberGroups(): void
    {
        $connection = $this->createMemberGroupConnection([]);
        $inspector = new MemberGroupLgnModeInspector($connection);

        self::assertSame(0, $inspector->countMemberGroupsWithoutLgnMode());
    }

    public function testDashboardShowsLgnAnnouncementForEmptyMemberGroup(): void
    {
        $connection = $this->createMemberGroupConnection(["'on'", "''"]);

        $markup = ls_shop_generalHelper::getMerconisSystemMessages(new MemberGroupLgnModeInspector($connection));

        self::assertStringContainsString(self::LGN_ANNOUNCEMENT, $markup);
    }

    public function testDashboardShowsLgnAnnouncementForNullMemberGroup(): void
    {
        $connection = $this->createMemberGroupConnection(["'on'", 'NULL']);

        $markup = ls_shop_generalHelper::getMerconisSystemMessages(new MemberGroupLgnModeInspector($connection));

        self::assertStringContainsString(self::LGN_ANNOUNCEMENT, $markup);
    }

    public function testDashboardHidesLgnAnnouncementWhenAllMemberGroupsHaveLgnMode(): void
    {
        $connection = $this->createMemberGroupConnection(["'on'", "'off'", "'blocked'"]);

        $markup = ls_shop_generalHelper::getMerconisSystemMessages(new MemberGroupLgnModeInspector($connection));

        self::assertStringNotContainsString(self::LGN_ANNOUNCEMENT, $markup);
    }

    public function testDashboardHidesLgnAnnouncementWithoutMemberGroups(): void
    {
        $connection = $this->createMemberGroupConnection([]);

        $markup = ls_shop_generalHelper::getMerconisSystemMessages(new MemberGroupLgnModeInspector($connection));

        self::assertStringNotContainsString(self::LGN_ANNOUNCEMENT, $markup);
    }

    /**
     * @param list<string> $lgnModeSqlLiterals
     */
    private function createMemberGroupConnection(array $lgnModeSqlLiterals): Connection
    {
        $connection = DriverManager::getConnection([
            'url' => 'sqlite:///:memory:',
        ]);

        $connection->executeStatement("
            CREATE TABLE tl_member_group (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL DEFAULT '',
                lsShopLgnMode TEXT NULL
            )
        ");

        foreach ($lgnModeSqlLiterals as $memberGroupIndex => $lgnModeSqlLiteral) {
            $connection->executeStatement(
                'INSERT INTO tl_member_group (name, lsShopLgnMode) VALUES (?, ' . $lgnModeSqlLiteral . ')',
                ['Group ' . $memberGroupIndex]
            );
        }

        return $connection;
    }
}
