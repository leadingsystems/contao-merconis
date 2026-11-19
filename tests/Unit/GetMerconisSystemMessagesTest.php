<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../../src/Resources/contao/helpers/ls_shop_generalHelper.php';

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupLgnModeInspector;
use Merconis\Core\ls_shop_generalHelper;
use PHPUnit\Framework\TestCase;

final class GetMerconisSystemMessagesTest extends TestCase
{
    private const LGN_ANNOUNCEMENT = 'Member group announcement';

    /*
     * Zusammengesetzt, damit eine Repository-Suche nach Resten der entfernten LGN-Übernahme
     * leer bleibt.
     */
    private const FORMER_GLL_MIGRATION_FLAG = 'ls_shop_enableGll' . 'MigrationApplied';

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

    public function testShowsLgnAnnouncementWhileOneMemberGroupHasNoLgnMode(): void
    {
        $markup = ls_shop_generalHelper::getMerconisSystemMessages($this->createInspectorStub(1));

        self::assertStringContainsString(self::LGN_ANNOUNCEMENT, $markup);
        self::assertStringContainsString('ls_shop_systemMessage', $markup);
        self::assertStringContainsString('ls_shop_announcement', $markup);
    }

    public function testShowsLgnAnnouncementWhileSeveralMemberGroupsHaveNoLgnMode(): void
    {
        $markup = ls_shop_generalHelper::getMerconisSystemMessages($this->createInspectorStub(3));

        self::assertSame(1, substr_count($markup, self::LGN_ANNOUNCEMENT));
    }

    public function testHidesLgnAnnouncementWhenAllMemberGroupsHaveLgnMode(): void
    {
        $markup = ls_shop_generalHelper::getMerconisSystemMessages($this->createInspectorStub(0));

        self::assertStringNotContainsString(self::LGN_ANNOUNCEMENT, $markup);
        self::assertStringNotContainsString('ls_shop_systemMessage', $markup);
    }

    public function testFormerMigrationFlagDoesNotHideLgnAnnouncement(): void
    {
        $GLOBALS['TL_CONFIG'][self::FORMER_GLL_MIGRATION_FLAG] = '1';

        $markup = ls_shop_generalHelper::getMerconisSystemMessages($this->createInspectorStub(1));

        self::assertStringContainsString(self::LGN_ANNOUNCEMENT, $markup);
    }

    public function testMissingFormerMigrationFlagDoesNotShowLgnAnnouncement(): void
    {
        unset($GLOBALS['TL_CONFIG'][self::FORMER_GLL_MIGRATION_FLAG]);

        $markup = ls_shop_generalHelper::getMerconisSystemMessages($this->createInspectorStub(0));

        self::assertStringNotContainsString(self::LGN_ANNOUNCEMENT, $markup);
    }

    private function createInspectorStub(int $memberGroupsWithoutLgnMode): MemberGroupLgnModeInspector
    {
        $inspectorStub = $this->createStub(MemberGroupLgnModeInspector::class);
        $inspectorStub
            ->method('countMemberGroupsWithoutLgnMode')
            ->willReturn($memberGroupsWithoutLgnMode);

        return $inspectorStub;
    }
}
