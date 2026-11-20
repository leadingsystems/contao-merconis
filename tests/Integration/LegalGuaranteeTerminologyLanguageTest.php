<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Alle händlersichtbaren LGN- und GARAN-Texte in `de` und `en` nach Spec 27, Abschnitt
 * "Terminologie": Backend-Masken, Meldungen und Hinweise im Backend, Dashboard-Hinweis
 * sowie Meldungen im Import-Log und in der Product Management API. Dazu der Wortlaut der
 * Backend-Meldungen zur Herstellergarantie nach Arbeitsauftrag 0016.
 */
final class LegalGuaranteeTerminologyLanguageTest extends TestCase
{
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';

    /**
     * Geprüfte Sprachschlüssel je Sprachdatei; bei Arrays zählen alle enthaltenen Texte.
     */
    private const LANGUAGE_KEYS_BY_TABLE = [
        'tl_ls_shop_product' => [
            'lgnMode',
            'lgnModeOptions',
            'enableGaran',
            'guaranteeDurationMonths',
            'guaranteeBrand',
            'guaranteeModelIdentifier',
            'legalGuaranteeMessages',
            'guaranteeValidationMessages',
            'lsShopGuaranteeLabels_legend',
        ],
        'tl_ls_shop_variant' => [
            'garanMode',
            'garanModeOptions',
            'guaranteeDurationMonths',
            'guaranteeBrand',
            'guaranteeModelIdentifier',
            'legalGuaranteeMessages',
            'guaranteeValidationMessages',
            'lsShopGuaranteeLabels_legend',
        ],
        'tl_member_group' => [
            'lsShopLgnMode',
            'lsShopLgnModeOptions',
            'lsShopLgnModeBlankOption',
        ],
        'tl_lsShopSettings' => [
            'ls_shop_standardGroup',
        ],
    ];

    /**
     * Geprüfte Pfade unter `MSC.ls_shop` in `default.php`.
     */
    private const DEFAULT_LANGUAGE_PATHS = [
        ['dashboard', 'lgnAnnouncement'],
        ['legalGuaranteeImport'],
    ];

    private const GARAN_SUFFIX_BY_LANGUAGE = [
        'de' => '(GARAN-Label)',
        'en' => '(GARAN label)',
    ];

    /*
     * Die Stellen, an denen Spec 27, Abschnitt "Terminologie", den Klammerzusatz erlaubt:
     * Titel von `enableGaran` und Legende der Palette an Produkt und an Variante.
     */
    private const GARAN_SUFFIX_LOCATIONS = [
        'tl_ls_shop_product.enableGaran.0',
        'tl_ls_shop_product.lsShopGuaranteeLabels_legend',
        'tl_ls_shop_variant.lsShopGuaranteeLabels_legend',
    ];

    private const FORBIDDEN_TERMS = [
        'Haltbarkeitsgarantie',
        'durability guarantee',
        'Standard-Mitgliedergruppe',
        'standard member group',
    ];

    private const FIELD_AND_CODE_IDENTIFIERS = [
        'lgnMode',
        'garanMode',
        'enableGaran',
        'enableGll',
        'lsShopLgnMode',
        'guaranteeDurationMonths',
        'guaranteeBrand',
        'guaranteeModelIdentifier',
        'ls_shop_standardGroup',
    ];

    private const LABEL_FIELD_DESIGNATIONS = ['Brand/Trademark', 'Model identifier'];

    private const BACKEND_MESSAGE_LOCATION_PREFIXES = [
        'tl_ls_shop_product.legalGuaranteeMessages.',
        'tl_ls_shop_product.guaranteeValidationMessages.',
        'tl_ls_shop_variant.legalGuaranteeMessages.',
        'tl_ls_shop_variant.guaranteeValidationMessages.',
    ];

    private const QUOTED_INTERNAL_VALUE_PATTERN = '/["\'](inherit|always|never|disabled|override|on|off|blocked)["\']/';

    private const GARAN_INVALID_MODE_LOCATION = 'tl_ls_shop_variant.guaranteeValidationMessages.garan_invalid_mode';

    /**
     * Wortlaut nach Arbeitsauftrag 0016, Abschnitt "Anweisung", Punkte 1 und 2.
     */
    private const DIRECTIVE_MESSAGES = [
        'de' => [
            self::GARAN_INVALID_MODE_LOCATION => 'Die Herstellergarantie wurde nicht gespeichert, weil "%s" keine zulässige Auswahl ist. Zulässig sind "Vom Produkt übernehmen", "Keine Ausgabe" und "Eigene Angaben".',
            'tl_ls_shop_variant.guaranteeValidationMessages.garan_disabled_invalid_duration' => 'Die Herstellergarantie wurde nicht gespeichert, weil "%s" keine zulässige Garantiedauer ist. Zulässig sind ganze Monate von 30 bis 360 in Schritten von 6.',
            'tl_ls_shop_product.guaranteeValidationMessages.garan_disabled_invalid_duration' => 'Die Herstellergarantie wurde nicht gespeichert, weil "%s" keine zulässige Garantiedauer ist. Zulässig sind ganze Monate von 30 bis 360 in Schritten von 6.',
        ],
        'en' => [
            self::GARAN_INVALID_MODE_LOCATION => 'The manufacturer guarantee was not saved because "%s" is not a valid selection. Allowed are "Inherit from product", "No output" and "Own details".',
            'tl_ls_shop_variant.guaranteeValidationMessages.garan_disabled_invalid_duration' => 'The manufacturer guarantee was not saved because "%s" is not an allowed guarantee duration. Use whole months from 30 to 360 in steps of 6.',
            'tl_ls_shop_product.guaranteeValidationMessages.garan_disabled_invalid_duration' => 'The manufacturer guarantee was not saved because "%s" is not an allowed guarantee duration. Use whole months from 30 to 360 in steps of 6.',
        ],
    ];

    /**
     * Die gleichnamigen Import-Meldungen bleiben nach Arbeitsauftrag 0016 unverändert.
     */
    private const UNCHANGED_IMPORT_MESSAGES = [
        'de' => [
            'MSC.ls_shop.legalGuaranteeImport.messages.garan_invalid_mode' => 'Die Zeile wurde nicht übernommen, weil "%s" kein zulässiger Wert für die Herstellergarantie der Variante ist. Zulässig sind "inherit", "disabled" und "override".',
            'MSC.ls_shop.legalGuaranteeImport.messages.garan_disabled_invalid_duration' => 'Die Zeile wurde nicht übernommen, weil "%s" keine zulässige Garantiedauer ist. Zulässig sind ganze Monate von 30 bis 360 in Schritten von 6.',
        ],
        'en' => [
            'MSC.ls_shop.legalGuaranteeImport.messages.garan_invalid_mode' => 'The row was not applied because "%s" is not a valid value for the manufacturer guarantee of the variant. Allowed values are "inherit", "disabled" and "override".',
            'MSC.ls_shop.legalGuaranteeImport.messages.garan_disabled_invalid_duration' => 'The row was not applied because "%s" is not an allowed guarantee duration. Use whole months from 30 to 360 in steps of 6.',
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function languageProvider(): iterable
    {
        yield 'de' => ['de'];
        yield 'en' => ['en'];
    }

    #[DataProvider('languageProvider')]
    public function testTextsContainNoLgnAndGaranOnlyAsSpecifiedSuffix(string $language): void
    {
        foreach ($this->collectMerchantTexts($language) as $textLocation => $merchantText) {
            self::assertStringNotContainsString('LGN', $merchantText, $textLocation);

            $textToCheckForGaran = in_array($textLocation, self::GARAN_SUFFIX_LOCATIONS, true)
                ? str_replace(self::GARAN_SUFFIX_BY_LANGUAGE[$language], '', $merchantText)
                : $merchantText;

            self::assertStringNotContainsString('GARAN', $textToCheckForGaran, $textLocation);
        }
    }

    #[DataProvider('languageProvider')]
    public function testTextsUseNeitherLegalTermNorFormerGroupTerm(string $language): void
    {
        foreach ($this->collectMerchantTexts($language) as $textLocation => $merchantText) {
            foreach (self::FORBIDDEN_TERMS as $forbiddenTerm) {
                self::assertStringNotContainsStringIgnoringCase($forbiddenTerm, $merchantText, $textLocation);
            }
        }
    }

    #[DataProvider('languageProvider')]
    public function testTextsContainNoFieldOrCodeIdentifiers(string $language): void
    {
        foreach ($this->collectMerchantTexts($language) as $textLocation => $merchantText) {
            foreach (self::FIELD_AND_CODE_IDENTIFIERS as $fieldOrCodeIdentifier) {
                self::assertStringNotContainsString($fieldOrCodeIdentifier, $merchantText, $textLocation);
            }
        }
    }

    #[DataProvider('languageProvider')]
    public function testLabelFieldDesignationsAreSuffixOnlyInGerman(string $language): void
    {
        foreach ($this->collectMerchantTexts($language) as $textLocation => $merchantText) {
            $lowercaseText = mb_strtolower($merchantText);

            foreach (self::LABEL_FIELD_DESIGNATIONS as $labelFieldDesignation) {
                $lowercaseDesignation = mb_strtolower($labelFieldDesignation);
                $suffixCount = substr_count($lowercaseText, '(' . $lowercaseDesignation . ')');

                if ($language === 'de') {
                    self::assertSame(substr_count($lowercaseText, $lowercaseDesignation), $suffixCount, $textLocation);
                } else {
                    self::assertSame(0, $suffixCount, $textLocation);
                }
            }
        }
    }

    #[DataProvider('languageProvider')]
    public function testBackendMessagesContainNoQuotedInternalValues(string $language): void
    {
        $backendMessageCount = 0;

        foreach ($this->collectMerchantTexts($language) as $textLocation => $merchantText) {
            if (!$this->isBackendMessageLocation($textLocation)) {
                continue;
            }

            ++$backendMessageCount;
            self::assertDoesNotMatchRegularExpression(self::QUOTED_INTERNAL_VALUE_PATTERN, $merchantText, $textLocation);
        }

        self::assertSame(11, $backendMessageCount);
    }

    #[DataProvider('languageProvider')]
    public function testManufacturerGuaranteeValidationMessagesFollowDirective(string $language): void
    {
        $merchantTexts = $this->collectMerchantTexts($language);

        foreach (self::DIRECTIVE_MESSAGES[$language] as $textLocation => $expectedMessage) {
            self::assertArrayHasKey($textLocation, $merchantTexts);
            self::assertSame($expectedMessage, $merchantTexts[$textLocation], $textLocation);
            self::assertSame(1, substr_count($merchantTexts[$textLocation], '%s'), $textLocation);
            self::assertSame(0, substr_count(str_replace('%s', '', $merchantTexts[$textLocation]), '%'), $textLocation);
        }

        $garanModeOptions = $this->loadLanguageFile($language, 'tl_ls_shop_variant')['tl_ls_shop_variant']['garanModeOptions'];
        self::assertSame(['inherit', 'disabled', 'override'], array_keys($garanModeOptions));

        foreach ($garanModeOptions as $garanModeValue => $garanModeOption) {
            $garanModeLabel = is_array($garanModeOption) ? $garanModeOption[0] : $garanModeOption;

            self::assertStringContainsString(
                '"' . $garanModeLabel . '"',
                $merchantTexts[self::GARAN_INVALID_MODE_LOCATION],
                $garanModeValue
            );
        }
    }

    #[DataProvider('languageProvider')]
    public function testImportMessagesWithSameCodesAreUnchanged(string $language): void
    {
        $merchantTexts = $this->collectMerchantTexts($language);

        foreach (self::UNCHANGED_IMPORT_MESSAGES[$language] as $textLocation => $expectedMessage) {
            self::assertArrayHasKey($textLocation, $merchantTexts);
            self::assertSame($expectedMessage, $merchantTexts[$textLocation], $textLocation);
        }
    }

    /**
     * Alle geprüften Texte, je Fundstelle als `<Tabelle>.<Schlüssel>[.<Teilschlüssel>...]`
     * bzw. `MSC.ls_shop.<Pfad>`.
     *
     * @return array<string, string>
     */
    private function collectMerchantTexts(string $language): array
    {
        $merchantTexts = [];

        foreach (self::LANGUAGE_KEYS_BY_TABLE as $tableName => $languageKeys) {
            $tableLanguage = $this->loadLanguageFile($language, $tableName)[$tableName];

            foreach ($languageKeys as $languageKey) {
                self::assertArrayHasKey($languageKey, $tableLanguage, $tableName . '.' . $languageKey);
                $this->flattenMerchantTexts($tableName . '.' . $languageKey, $tableLanguage[$languageKey], $merchantTexts);
            }
        }

        $shopLanguage = $this->loadLanguageFile($language, 'default')['MSC']['ls_shop'];

        foreach (self::DEFAULT_LANGUAGE_PATHS as $languagePath) {
            $languageValue = $shopLanguage;

            foreach ($languagePath as $languagePathSegment) {
                self::assertArrayHasKey($languagePathSegment, $languageValue, implode('.', $languagePath));
                $languageValue = $languageValue[$languagePathSegment];
            }

            $this->flattenMerchantTexts('MSC.ls_shop.' . implode('.', $languagePath), $languageValue, $merchantTexts);
        }

        return $merchantTexts;
    }

    /**
     * @param array<string, string> $merchantTexts
     */
    private function flattenMerchantTexts(string $textLocation, mixed $languageValue, array &$merchantTexts): void
    {
        if (is_array($languageValue)) {
            foreach ($languageValue as $languageValueKey => $nestedLanguageValue) {
                $this->flattenMerchantTexts($textLocation . '.' . $languageValueKey, $nestedLanguageValue, $merchantTexts);
            }

            return;
        }

        self::assertIsString($languageValue, $textLocation);
        $merchantTexts[$textLocation] = $languageValue;
    }

    private function isBackendMessageLocation(string $textLocation): bool
    {
        foreach (self::BACKEND_MESSAGE_LOCATION_PREFIXES as $backendMessageLocationPrefix) {
            if (str_starts_with($textLocation, $backendMessageLocationPrefix)) {
                return true;
            }
        }

        return false;
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
