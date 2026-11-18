<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class MemberGroupGllModeLanguageTest extends TestCase
{
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';

    /*
     * Zusammengesetzt, damit eine Repository-Suche nach Resten der entfernten GLL-Übernahme
     * leer bleibt.
     */
    private const FORMER_GLL_MIGRATION_CONTROL_FIELD = 'ls_shop_enableGll' . 'MigrationControl';

    private const GLL_MODE_DESCRIPTION_DE = 'Steuert die Anzeige für Mitglieder dieser Gruppe. Eine Änderung passt keine Produkte an.';
    private const GLL_MODE_DESCRIPTION_EN = 'Controls the display for members of this group. A change does not adjust any products.';

    /*
     * Je Wert Beschriftung und Erläuterung im Hilfe-Assistenten nach Spec 27, Abschnitt 3.5
     * (Implementierungsentscheidung 0027); die leere Auswahl hat keine Erläuterung.
     */
    private const GLL_MODE_OPTIONS_DE = [
        'on' => [
            'Anzeigen',
            "Produkte mit 'Wie Mitgliedergruppe' oder 'Immer anzeigen' zeigen die Mitteilung und, falls eingeschaltet, die Herstellergarantie.",
        ],
        'off' => [
            "Nur Produkte mit 'Immer anzeigen'",
            "Nur Produkte mit 'Immer anzeigen' zeigen Mitteilung und Herstellergarantie. Für Shops mit überwiegend Dienstleistungen oder digitalen Inhalten.",
        ],
        'blocked' => [
            'Nie anzeigen (z. B. Geschäftskunden)',
            "Weder Mitteilung noch Herstellergarantie, auch nicht bei 'Immer anzeigen'. Für Gruppen ohne Verbraucher, zum Beispiel Geschäftskunden.",
        ],
    ];
    private const GLL_MODE_OPTIONS_EN = [
        'on' => [
            'Show',
            "Products set to 'As member group' or 'Always show' show the notice and, if enabled, the manufacturer guarantee.",
        ],
        'off' => [
            "Only products set to 'Always show'",
            "Only products set to 'Always show' show the notice and the manufacturer guarantee. For shops selling mostly services or digital content.",
        ],
        'blocked' => [
            'Never show (e.g. business customers)',
            "Neither notice nor manufacturer guarantee, not even for 'Always show'. For groups without consumers, e.g. business customers.",
        ],
    ];

    private const STANDARD_GROUP_DESCRIPTION_DE = 'Einige Shop-Einstellungen werden für unterschiedliche Kunden- bzw. Mitgliedergruppen separat eingestellt. Definieren Sie hier, welcher dieser Gruppen ein nicht angemeldeter Kunde zugeordnet werden soll. Die Einstellung zur gesetzlichen Gewährleistung dieser Gruppe gilt für alle nicht angemeldeten Besucher und ist der Bezug für die automatische Anpassung am Produkt. Ein Wechsel der Gruppe passt keine Produkte an.';
    private const STANDARD_GROUP_DESCRIPTION_EN = 'Some shop settings are made separately for different user and member groups. Define here to which group a non-registered customer shall be assigned. The legal guarantee setting of this group applies to all visitors who are not logged in and is the reference for the automatic adjustment on the product. Changing the group does not adjust any products.';

    private const GLL_ANNOUNCEMENT_DE = 'Mit Version 5.1.18 wurden die Mitteilung über die gesetzliche Gewährleistung und die Kennzeichnung der Herstellergarantie eingeführt. Legen Sie bei jeder Mitgliedergruppe fest, ob sie angezeigt werden. Bis dahin zeigt der Shop sie nicht an.';
    private const GLL_ANNOUNCEMENT_EN = 'Version 5.1.18 introduced the notice on the legal guarantee and the label of the manufacturer guarantee. Specify for each member group whether they are shown. Until then, the shop does not show them.';

    private mixed $originalTlLang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlLang = $GLOBALS['TL_LANG'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalTlLang === null) {
            unset($GLOBALS['TL_LANG']);
        } else {
            $GLOBALS['TL_LANG'] = $this->originalTlLang;
        }

        parent::tearDown();
    }

    public function testGermanMemberGroupTextsFollowSpecWording(): void
    {
        $memberGroupLanguage = $this->loadLanguageFile('de', 'tl_member_group')['tl_member_group'];

        self::assertSame(['Gesetzliche Gewährleistung', self::GLL_MODE_DESCRIPTION_DE], $memberGroupLanguage['lsShopGllMode']);
        self::assertSame(self::GLL_MODE_OPTIONS_DE, $memberGroupLanguage['lsShopGllModeOptions']);
        self::assertSame('Bitte wählen', $memberGroupLanguage['lsShopGllModeBlankOption']);
    }

    public function testEnglishMemberGroupTextsFollowSpecWording(): void
    {
        $memberGroupLanguage = $this->loadLanguageFile('en', 'tl_member_group')['tl_member_group'];

        self::assertSame(['Legal guarantee notice', self::GLL_MODE_DESCRIPTION_EN], $memberGroupLanguage['lsShopGllMode']);
        self::assertSame(self::GLL_MODE_OPTIONS_EN, $memberGroupLanguage['lsShopGllModeOptions']);
        self::assertSame('Please select', $memberGroupLanguage['lsShopGllModeBlankOption']);
    }

    public function testStandardGroupDescriptionKeepsExistingTextAndAppendsSpecSentence(): void
    {
        $settingsLanguageDe = $this->loadLanguageFile('de', 'tl_lsShopSettings')['tl_lsShopSettings'];
        $settingsLanguageEn = $this->loadLanguageFile('en', 'tl_lsShopSettings')['tl_lsShopSettings'];

        self::assertSame(
            ['Standard-Benutzergruppe', self::STANDARD_GROUP_DESCRIPTION_DE],
            $settingsLanguageDe['ls_shop_standardGroup']
        );
        self::assertSame(
            ['Standard user group', self::STANDARD_GROUP_DESCRIPTION_EN],
            $settingsLanguageEn['ls_shop_standardGroup']
        );
    }

    public function testFormerGllMigrationControlKeysAreRemoved(): void
    {
        $settingsLanguageDe = $this->loadLanguageFile('de', 'tl_lsShopSettings')['tl_lsShopSettings'];
        $settingsLanguageEn = $this->loadLanguageFile('en', 'tl_lsShopSettings')['tl_lsShopSettings'];

        self::assertArrayNotHasKey(self::FORMER_GLL_MIGRATION_CONTROL_FIELD, $settingsLanguageDe);
        self::assertArrayNotHasKey(self::FORMER_GLL_MIGRATION_CONTROL_FIELD, $settingsLanguageEn);
        self::assertArrayHasKey('garanLabelTruncationLength', $settingsLanguageDe);
        self::assertArrayHasKey('garanLabelTruncationLength', $settingsLanguageEn);
    }

    public function testDashboardAnnouncementFollowsSpecWording(): void
    {
        $defaultLanguageDe = $this->loadLanguageFile('de', 'default');
        $defaultLanguageEn = $this->loadLanguageFile('en', 'default');

        self::assertSame(
            self::GLL_ANNOUNCEMENT_DE,
            $defaultLanguageDe['MSC']['ls_shop']['dashboard']['gllAnnouncement']
        );
        self::assertSame(
            self::GLL_ANNOUNCEMENT_EN,
            $defaultLanguageEn['MSC']['ls_shop']['dashboard']['gllAnnouncement']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLanguageFile(string $language, string $languageFileName): array
    {
        $GLOBALS['TL_LANG'] = [];

        require self::LANGUAGE_BASE_PATH . $language . '/' . $languageFileName . '.php';

        return $GLOBALS['TL_LANG'];
    }
}
