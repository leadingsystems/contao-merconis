<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../../src/Resources/contao/helpers/ls_shop_generalHelper.php';

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\EnableGllSettingsMigrationManager;
use Merconis\Core\ls_shop_generalHelper;
use PHPUnit\Framework\TestCase;

final class GetMerconisSystemMessagesTest extends TestCase
{
    private mixed $originalTlConfig;
    private mixed $originalTlLang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
        $this->originalTlLang = $GLOBALS['TL_LANG'] ?? null;
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

    public function testShowsGllAnnouncementWhenMigrationIsPending(): void
    {
        $GLOBALS['TL_CONFIG'] = [];
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['dashboard']['gllAnnouncement'] = 'Pending announcement';

        $markup = ls_shop_generalHelper::getMerconisSystemMessages();

        self::assertStringContainsString('Pending announcement', $markup);
        self::assertStringContainsString('ls_shop_systemMessage', $markup);
    }

    public function testHidesGllAnnouncementWhenMigrationIsApplied(): void
    {
        $GLOBALS['TL_CONFIG'] = [
            EnableGllSettingsMigrationManager::CONFIG_FLAG => '1',
        ];
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['dashboard']['gllAnnouncement'] = 'Applied announcement';

        $markup = ls_shop_generalHelper::getMerconisSystemMessages();

        self::assertStringNotContainsString('Applied announcement', $markup);
        self::assertStringNotContainsString('ls_shop_systemMessage', $markup);
    }
}
