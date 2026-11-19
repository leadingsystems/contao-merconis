<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Titel, Beschreibungen und Legenden der LGN- und GARAN-Felder in den Backend-Masken von
 * Produkt, Variante, Mitgliedergruppe und Grundeinstellungen nach Spec 27, Abschnitte
 * "Terminologie", 3.1 und 3.4, und Spec 02, Abschnitte 1.11 und 1.26.
 */
final class LegalGuaranteeBackendMaskLanguageTest extends TestCase
{
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';

    private const ENABLE_GARAN_DE = [
        'Herstellergarantie anzeigen (GARAN-Label)',
        'Zeigt die Kennzeichnung der Herstellergarantie, wenn Dauer, Marke und Modell ausgefüllt sind und die gesetzliche Gewährleistung angezeigt wird.',
    ];
    private const ENABLE_GARAN_EN = [
        'Show manufacturer guarantee (GARAN label)',
        'Shows the manufacturer guarantee label if duration, brand and model are filled in and the legal guarantee notice is shown.',
    ];

    private const GUARANTEE_LEGEND_DE = 'Gewährleistung und Herstellergarantie (GARAN-Label)';
    private const GUARANTEE_LEGEND_EN = 'Legal guarantee and manufacturer guarantee (GARAN label)';

    private const TRUNCATION_LENGTH_DE = [
        'Kürzung von Marke und Modell (Zeichen)',
        'Längere Angaben zu Marke oder Modell werden in der Kennzeichnung der Herstellergarantie nach dieser Zeichenzahl mit "..." gekürzt. Gespeichert bleibt der vollständige Wert. Standard: 30.',
    ];
    private const TRUNCATION_LENGTH_EN = [
        'Shorten brand and model after (characters)',
        'Longer brand or model values are shortened with "..." after this number of characters in the manufacturer guarantee label. The full value remains stored. Default: 30.',
    ];

    private const ALLOWED_GARAN_SUFFIXES = ['(GARAN-Label)', '(GARAN label)'];

    /*
     * Die vier Schlüssel aus `collectMaskTexts()` für die drei Stellen, an denen Spec 27,
     * Abschnitt "Terminologie", den Klammerzusatz erlaubt: Titel von `enableGaran`,
     * Legende der Palette (je ein Schlüssel an Produkt und Variante) und Legende der
     * Grundeinstellungen.
     */
    private const GARAN_SUFFIX_LOCATIONS = [
        'tl_ls_shop_product.enableGaran.0',
        'tl_ls_shop_product.lsShopGuaranteeLabels_legend',
        'tl_ls_shop_variant.lsShopGuaranteeLabels_legend',
        'tl_lsShopSettings.legalGuarantee_legend',
    ];

    private const FORBIDDEN_FRAGMENTS = ['LGN', 'GARAN', 'Haltbarkeitsgarantie', 'durability'];

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

    public function testGermanMaskTextsFollowSpecWording(): void
    {
        $productLanguage = $this->loadLanguageFile('de', 'tl_ls_shop_product')['tl_ls_shop_product'];
        $variantLanguage = $this->loadLanguageFile('de', 'tl_ls_shop_variant')['tl_ls_shop_variant'];
        $settingsLanguage = $this->loadLanguageFile('de', 'tl_lsShopSettings')['tl_lsShopSettings'];

        self::assertSame(self::ENABLE_GARAN_DE, $productLanguage['enableGaran']);
        self::assertSame(self::GUARANTEE_LEGEND_DE, $productLanguage['lsShopGuaranteeLabels_legend']);
        self::assertSame(self::GUARANTEE_LEGEND_DE, $variantLanguage['lsShopGuaranteeLabels_legend']);
        self::assertSame(self::GUARANTEE_LEGEND_DE, $settingsLanguage['legalGuarantee_legend']);
        self::assertSame(self::TRUNCATION_LENGTH_DE, $settingsLanguage['garanLabelTruncationLength']);
        self::assertSame('Standard-Benutzergruppe', $settingsLanguage['ls_shop_standardGroup'][0]);
        self::assertSame('Marke (Brand/Trademark)', $productLanguage['guaranteeBrand'][0]);
        self::assertSame('Modell (Model identifier)', $productLanguage['guaranteeModelIdentifier'][0]);
        self::assertSame('Marke (Brand/Trademark)', $variantLanguage['guaranteeBrand'][0]);
        self::assertSame('Modell (Model identifier)', $variantLanguage['guaranteeModelIdentifier'][0]);
    }

    public function testEnglishMaskTextsFollowSpecWording(): void
    {
        $productLanguage = $this->loadLanguageFile('en', 'tl_ls_shop_product')['tl_ls_shop_product'];
        $variantLanguage = $this->loadLanguageFile('en', 'tl_ls_shop_variant')['tl_ls_shop_variant'];
        $settingsLanguage = $this->loadLanguageFile('en', 'tl_lsShopSettings')['tl_lsShopSettings'];

        self::assertSame(self::ENABLE_GARAN_EN, $productLanguage['enableGaran']);
        self::assertSame(self::GUARANTEE_LEGEND_EN, $productLanguage['lsShopGuaranteeLabels_legend']);
        self::assertSame(self::GUARANTEE_LEGEND_EN, $variantLanguage['lsShopGuaranteeLabels_legend']);
        self::assertSame(self::GUARANTEE_LEGEND_EN, $settingsLanguage['legalGuarantee_legend']);
        self::assertSame(self::TRUNCATION_LENGTH_EN, $settingsLanguage['garanLabelTruncationLength']);
        self::assertSame('Standard user group', $settingsLanguage['ls_shop_standardGroup'][0]);
        self::assertSame('Brand/Trademark', $productLanguage['guaranteeBrand'][0]);
        self::assertSame('Model identifier', $productLanguage['guaranteeModelIdentifier'][0]);
        self::assertSame('Brand/Trademark', $variantLanguage['guaranteeBrand'][0]);
        self::assertSame('Model identifier', $variantLanguage['guaranteeModelIdentifier'][0]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function languageProvider(): iterable
    {
        yield 'de' => ['de'];
        yield 'en' => ['en'];
    }

    #[DataProvider('languageProvider')]
    public function testMaskTextsContainNoAbbreviationsOrLegalTerm(string $language): void
    {
        foreach ($this->collectMaskTexts($language) as $textLocation => $maskText) {
            $textWithoutAllowedSuffix = str_replace(self::ALLOWED_GARAN_SUFFIXES, '', $maskText);

            foreach (self::FORBIDDEN_FRAGMENTS as $forbiddenFragment) {
                self::assertStringNotContainsString($forbiddenFragment, $textWithoutAllowedSuffix, $textLocation);
            }
        }
    }

    #[DataProvider('languageProvider')]
    public function testGaranSuffixAppearsOnlyAtTheThreeSpecifiedLocations(string $language): void
    {
        $locationsWithSuffix = [];

        foreach ($this->collectMaskTexts($language) as $textLocation => $maskText) {
            foreach (self::ALLOWED_GARAN_SUFFIXES as $allowedSuffix) {
                if (str_contains($maskText, $allowedSuffix)) {
                    self::assertStringEndsWith($allowedSuffix, $maskText, $textLocation);
                    $locationsWithSuffix[] = $textLocation;
                }
            }
        }

        self::assertSame(self::GARAN_SUFFIX_LOCATIONS, $locationsWithSuffix);
    }

    /**
     * Titel, Beschreibungen, Legenden sowie Beschriftungen und Erläuterungen der Werte, je
     * Fundstelle als `<Tabelle>.<Schlüssel>[.<Wert>].<Index>`.
     *
     * @return array<string, string>
     */
    private function collectMaskTexts(string $language): array
    {
        $languageKeysByTable = [
            'tl_ls_shop_product' => [
                'lgnMode',
                'lgnModeOptions',
                'enableGaran',
                'guaranteeDurationMonths',
                'guaranteeBrand',
                'guaranteeModelIdentifier',
                'lsShopGuaranteeLabels_legend',
            ],
            'tl_ls_shop_variant' => [
                'garanMode',
                'garanModeOptions',
                'guaranteeDurationMonths',
                'guaranteeBrand',
                'guaranteeModelIdentifier',
                'lsShopGuaranteeLabels_legend',
            ],
            'tl_member_group' => [
                'lsShopLgnMode',
                'lsShopLgnModeOptions',
                'lsShopLgnModeBlankOption',
            ],
            'tl_lsShopSettings' => [
                'legalGuarantee_legend',
                'garanLabelTruncationLength',
                'ls_shop_standardGroup',
            ],
        ];

        $maskTexts = [];

        foreach ($languageKeysByTable as $tableName => $languageKeys) {
            $tableLanguage = $this->loadLanguageFile($language, $tableName)[$tableName];

            foreach ($languageKeys as $languageKey) {
                self::assertArrayHasKey($languageKey, $tableLanguage, $tableName . '.' . $languageKey);
                $this->flattenMaskTexts($tableName . '.' . $languageKey, $tableLanguage[$languageKey], $maskTexts);
            }
        }

        return $maskTexts;
    }

    /**
     * @param array<string, string> $maskTexts
     */
    private function flattenMaskTexts(string $textLocation, mixed $languageValue, array &$maskTexts): void
    {
        if (is_array($languageValue)) {
            foreach ($languageValue as $languageValueKey => $nestedLanguageValue) {
                $this->flattenMaskTexts($textLocation . '.' . $languageValueKey, $nestedLanguageValue, $maskTexts);
            }

            return;
        }

        self::assertIsString($languageValue, $textLocation);
        $maskTexts[$textLocation] = $languageValue;
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
