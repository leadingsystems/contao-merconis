<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class ProductGllModeLanguageTest extends TestCase
{
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';

    private const GLL_MODE_DESCRIPTION_DE = "Die Mitteilung gilt für körperliche Waren und Waren mit digitalen Elementen. Für reine digitale Inhalte, SaaS und Dienstleistungen 'Nie anzeigen' wählen. 'Wie Mitgliedergruppe' übernimmt die Einstellung der Mitgliedergruppe des Kunden.";
    private const GLL_MODE_DESCRIPTION_EN = "The notice applies to physical goods and goods with digital elements. Select 'Never show' for purely digital content, SaaS and services. 'As member group' uses the setting of the customer's member group.";

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
        self::assertSame(
            [
                'inherit' => 'Wie Mitgliedergruppe',
                'always' => 'Immer anzeigen',
                'never' => 'Nie anzeigen',
            ],
            $productLanguage['gllModeOptions']
        );
        self::assertArrayNotHasKey('enableGll', $productLanguage);
    }

    public function testEnglishProductGllModeTextsFollowSpecWording(): void
    {
        $productLanguage = $this->loadLanguageFile('en', 'tl_ls_shop_product')['tl_ls_shop_product'];

        self::assertSame(['Legal guarantee notice', self::GLL_MODE_DESCRIPTION_EN], $productLanguage['gllMode']);
        self::assertSame(
            [
                'inherit' => 'As member group',
                'always' => 'Always show',
                'never' => 'Never show',
            ],
            $productLanguage['gllModeOptions']
        );
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
