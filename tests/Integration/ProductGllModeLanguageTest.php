<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class ProductGllModeLanguageTest extends TestCase
{
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';

    private const GLL_MODE_DESCRIPTION_DE = 'Steuert die Mitteilung über die gesetzliche Gewährleistung für dieses Produkt.';
    private const GLL_MODE_DESCRIPTION_EN = 'Controls the legal guarantee notice for this product.';

    /*
     * Je Wert Beschriftung und Erläuterung im Hilfe-Assistenten nach Spec 27, Abschnitt 3.1
     * (Implementierungsentscheidung 0027).
     */
    private const GLL_MODE_OPTIONS_DE = [
        'inherit' => [
            'Wie Mitgliedergruppe',
            'Übernimmt die Einstellung der Mitgliedergruppe des Kunden. Richtig für körperliche Waren und Waren mit digitalen Elementen.',
        ],
        'always' => [
            'Immer anzeigen',
            "Zeigt die Mitteilung auch in Gruppen, die nur Produkte mit 'Immer anzeigen' berücksichtigen, nicht in Gruppen ohne Verbraucher. Für Waren in Shops mit überwiegend Dienstleistungen oder digitalen Inhalten.",
        ],
        'never' => [
            'Nie anzeigen',
            'Für reine digitale Inhalte, SaaS und Dienstleistungen. Ohne die Mitteilung wird auch keine Herstellergarantie angezeigt.',
        ],
    ];
    private const GLL_MODE_OPTIONS_EN = [
        'inherit' => [
            'As member group',
            "Uses the setting of the customer's member group. Right for physical goods and goods with digital elements.",
        ],
        'always' => [
            'Always show',
            "Also shows the notice in groups that only include products set to 'Always show', not in groups without consumers. For goods in shops selling mostly services or digital content.",
        ],
        'never' => [
            'Never show',
            'For purely digital content, SaaS and services. Without the notice, no manufacturer guarantee is shown either.',
        ],
    ];

    private mixed $originalTlLang;
    private mixed $originalTlConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlLang = $GLOBALS['TL_LANG'] ?? null;
        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
        $GLOBALS['TL_CONFIG'] = [
            'ls_shop_currency' => 'EUR',
            'ls_shop_weightUnit' => 'kg',
        ];
    }

    protected function tearDown(): void
    {
        if ($this->originalTlLang === null) {
            unset($GLOBALS['TL_LANG']);
        } else {
            $GLOBALS['TL_LANG'] = $this->originalTlLang;
        }

        if ($this->originalTlConfig === null) {
            unset($GLOBALS['TL_CONFIG']);
        } else {
            $GLOBALS['TL_CONFIG'] = $this->originalTlConfig;
        }

        parent::tearDown();
    }

    public function testGermanProductGllModeTextsFollowSpecWording(): void
    {
        $productLanguage = $this->loadLanguageFile('de', 'tl_ls_shop_product')['tl_ls_shop_product'];

        self::assertSame(['Gesetzliche Gewährleistung', self::GLL_MODE_DESCRIPTION_DE], $productLanguage['gllMode']);
        self::assertSame(self::GLL_MODE_OPTIONS_DE, $productLanguage['gllModeOptions']);
        self::assertArrayNotHasKey('enableGll', $productLanguage);
    }

    public function testEnglishProductGllModeTextsFollowSpecWording(): void
    {
        $productLanguage = $this->loadLanguageFile('en', 'tl_ls_shop_product')['tl_ls_shop_product'];

        self::assertSame(['Legal guarantee notice', self::GLL_MODE_DESCRIPTION_EN], $productLanguage['gllMode']);
        self::assertSame(self::GLL_MODE_OPTIONS_EN, $productLanguage['gllModeOptions']);
        self::assertArrayNotHasKey('enableGll', $productLanguage);
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
