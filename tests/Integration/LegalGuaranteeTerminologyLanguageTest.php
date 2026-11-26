<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Alle händlersichtbaren LGN- und GARAN-Texte in `de` und `en` nach Spec 27, Abschnitt
 * "Terminologie": Backend-Masken, Meldungen und Hinweise im Backend, Dashboard-Hinweis
 * sowie Meldungen der Dateivalidierung des Imports und der Vorprüfung der Product
 * Management API. Dazu der Wortlaut der Backend-Meldungen zur Herstellergarantie nach
 * Arbeitsauftrag 0016. Jede Prüfung nach Arbeitsauftrag 0020 hat eine Gegenprobe mit
 * verletzenden Beispieltexten.
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
        ['misc', 'importErrors', 'productValueInvalid_lgnMode'],
        ['misc', 'importErrors', 'variantValueInvalid_garanMode'],
        ['misc', 'importErrors', 'valueInvalid_guaranteeDurationMonths'],
        ['misc', 'importErrors', 'valueInvalid_guaranteeBrand'],
        ['misc', 'importErrors', 'valueInvalid_guaranteeModelIdentifier'],
        ['misc', 'importErrors', 'productGuaranteeDataIncomplete'],
        ['misc', 'importErrors', 'productGuaranteeWithLgnModeNever'],
        ['misc', 'importErrors', 'variantGuaranteeDataIncomplete'],
        ['misc', 'importErrors', 'variantGuaranteeOverrideWithParentLgnModeNever'],
        ['misc', 'productManagementApiErrors'],
    ];

    /*
     * Meldungen des Imports und der Product Management API dürfen Spaltennamen und interne
     * Werte so nennen, wie sie in der Importdatei beziehungsweise im Request stehen (Spec 27,
     * Abschnitt "Terminologie"); dort stehen sie in Anführungszeichen.
     */
    private const IMPORT_AND_API_MESSAGE_LOCATION_PREFIXES = [
        'MSC.ls_shop.misc.importErrors.',
        'MSC.ls_shop.misc.productManagementApiErrors.',
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

    /*
     * Einzige Ausnahme von "unmittelbar nach": Spec 27, Abschnitt 3.1, legt den deutschen
     * Feldtitel von `enableGaran` wörtlich fest; dort gilt genau dieser Wortlaut.
     */
    private const GARAN_SUFFIX_SPEC_WORDING_BY_LANGUAGE = [
        'de' => [
            'tl_ls_shop_product.enableGaran.0' => 'Herstellergarantie anzeigen (GARAN-Label)',
        ],
        'en' => [],
    ];

    private const MANUFACTURER_GUARANTEE_TERM_BY_LANGUAGE = [
        'de' => 'Herstellergarantie',
        'en' => 'manufacturer guarantee',
    ];

    /*
     * Klammerzusätze der veränderbaren Felder des amtlichen Labels und das Wort, auf das sie
     * unmittelbar folgen (Spec 27, Abschnitt "Terminologie").
     */
    private const LABEL_FIELD_SUFFIX_TERMS = [
        '(Brand/Trademark)' => 'Marke',
        '(Model identifier)' => 'Modell',
    ];

    /*
     * Nur ganze Wörter: "garan" in "Garantie" und Spaltennamen wie `lgnMode` oder `garanMode`
     * sind kein Treffer.
     */
    private const ABBREVIATION_PATTERN = '/\b(lgn|garan|gll)\b/iu';

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
        $importAndApiMessageCount = 0;

        foreach ($this->collectMerchantTexts($language) as $textLocation => $merchantText) {
            if ($this->isImportOrApiMessageLocation($textLocation)) {
                ++$importAndApiMessageCount;
                $merchantText = (string) preg_replace('/"(lgnMode|enableGaran|garanMode|guaranteeDurationMonths|guaranteeBrand|guaranteeModelIdentifier)"/', '', $merchantText);
            }

            foreach (self::FIELD_AND_CODE_IDENTIFIERS as $fieldOrCodeIdentifier) {
                self::assertStringNotContainsString($fieldOrCodeIdentifier, $merchantText, $textLocation);
            }
        }

        self::assertSame(22, $importAndApiMessageCount);
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
    public function testGaranSuffixDirectlyFollowsManufacturerGuarantee(string $language): void
    {
        $merchantTexts = $this->collectMerchantTexts($language);

        foreach (array_keys(self::GARAN_SUFFIX_SPEC_WORDING_BY_LANGUAGE[$language]) as $specWordingLocation) {
            self::assertArrayHasKey($specWordingLocation, $merchantTexts);
        }

        foreach ($merchantTexts as $textLocation => $merchantText) {
            $this->assertGaranSuffixFollowsManufacturerGuaranteeOrSpecWording($language, $textLocation, $merchantText);
        }
    }

    #[DataProvider('languageProvider')]
    public function testLabelFieldSuffixesDirectlyFollowBrandAndModel(string $language): void
    {
        foreach ($this->collectMerchantTexts($language) as $textLocation => $merchantText) {
            $this->assertLabelFieldSuffixesDirectlyFollowBrandAndModel($textLocation, $merchantText);
        }
    }

    #[DataProvider('languageProvider')]
    public function testTextsContainNoAbbreviationAsWholeWord(string $language): void
    {
        foreach ($this->collectMerchantTexts($language) as $textLocation => $merchantText) {
            $this->assertTextContainsNoAbbreviationAsWholeWord($language, $textLocation, $merchantText);
        }
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function garanSuffixViolationProvider(): iterable
    {
        yield 'de, Zusatz nach anderem Wort' => ['de', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Gewährleistung und Garantie (GARAN-Label)'];
        yield 'de, Wort zwischen Begriff und Zusatz' => ['de', 'tl_ls_shop_product.enableGaran.0', 'Herstellergarantie anzeigen (GARAN-Label)'];
        yield 'de, ohne Leerzeichen' => ['de', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Gewährleistung und Herstellergarantie(GARAN-Label)'];
        yield 'de, zwei Leerzeichen' => ['de', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Gewährleistung und Herstellergarantie  (GARAN-Label)'];
        yield 'de, Begriff als Wortteil' => ['de', 'tl_ls_shop_variant.lsShopGuaranteeLabels_legend', 'Gewährleistung und Zusatzherstellergarantie (GARAN-Label)'];
        yield 'de, zweiter Zusatz ohne Begriff' => ['de', 'tl_ls_shop_variant.lsShopGuaranteeLabels_legend', 'Herstellergarantie (GARAN-Label) und Gewährleistung (GARAN-Label)'];
        yield 'en, Zusatz nach anderem Wort' => ['en', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Legal guarantee and guarantee label (GARAN label)'];
        yield 'en, Wort zwischen Begriff und Zusatz' => ['en', 'tl_ls_shop_product.enableGaran.0', 'Manufacturer guarantee shown (GARAN label)'];
        yield 'en, ohne Leerzeichen' => ['en', 'tl_ls_shop_product.enableGaran.0', 'Show manufacturer guarantee(GARAN label)'];
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function garanSuffixComplianceProvider(): iterable
    {
        yield 'de, Legende' => ['de', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Gewährleistung und Herstellergarantie (GARAN-Label)'];
        yield 'de, ohne Zusatz' => ['de', 'tl_ls_shop_product.enableGaran.1', 'Zeigt die Kennzeichnung der Herstellergarantie, wenn Dauer, Marke und Modell ausgefüllt sind.'];
        yield 'en, Legende' => ['en', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Legal guarantee and manufacturer guarantee (GARAN label)'];
        yield 'en, Feldtitel' => ['en', 'tl_ls_shop_product.enableGaran.0', 'Show manufacturer guarantee (GARAN label)'];
        yield 'en, Satzanfang' => ['en', 'tl_ls_shop_product.enableGaran.0', 'Manufacturer guarantee (GARAN label)'];
    }

    #[DataProvider('garanSuffixViolationProvider')]
    public function testGaranSuffixCheckFailsForViolatingText(string $language, string $textLocation, string $violatingText): void
    {
        $this->assertCheckFails(
            fn () => $this->assertGaranSuffixDirectlyFollowsManufacturerGuarantee($language, $textLocation, $violatingText),
            $violatingText
        );
    }

    #[DataProvider('garanSuffixComplianceProvider')]
    public function testGaranSuffixCheckAcceptsCompliantText(string $language, string $textLocation, string $compliantText): void
    {
        $this->assertGaranSuffixDirectlyFollowsManufacturerGuarantee($language, $textLocation, $compliantText);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function garanSuffixWithSpecWordingViolationProvider(): iterable
    {
        yield 'de, Feldtitel mit anderem Wortlaut' => ['de', 'tl_ls_shop_product.enableGaran.0', 'Herstellergarantie jetzt anzeigen (GARAN-Label)'];
        yield 'de, Feldtitel mit Zusatz unmittelbar nach Begriff' => ['de', 'tl_ls_shop_product.enableGaran.0', 'Herstellergarantie (GARAN-Label) anzeigen'];
        yield 'de, Wortlaut des Feldtitels an anderer Stelle' => ['de', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Herstellergarantie anzeigen (GARAN-Label)'];
        yield 'en, Feldtitel mit Wort zwischen Begriff und Zusatz' => ['en', 'tl_ls_shop_product.enableGaran.0', 'Manufacturer guarantee shown (GARAN label)'];
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function garanSuffixWithSpecWordingComplianceProvider(): iterable
    {
        yield 'de, Feldtitel nach Spezifikation' => ['de', 'tl_ls_shop_product.enableGaran.0', 'Herstellergarantie anzeigen (GARAN-Label)'];
        yield 'de, Legende' => ['de', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Gewährleistung und Herstellergarantie (GARAN-Label)'];
        yield 'en, Feldtitel nach Spezifikation' => ['en', 'tl_ls_shop_product.enableGaran.0', 'Show manufacturer guarantee (GARAN label)'];
    }

    #[DataProvider('garanSuffixWithSpecWordingViolationProvider')]
    public function testGaranSuffixCheckWithSpecWordingFailsForViolatingText(string $language, string $textLocation, string $violatingText): void
    {
        $this->assertCheckFails(
            fn () => $this->assertGaranSuffixFollowsManufacturerGuaranteeOrSpecWording($language, $textLocation, $violatingText),
            $violatingText
        );
    }

    #[DataProvider('garanSuffixWithSpecWordingComplianceProvider')]
    public function testGaranSuffixCheckWithSpecWordingAcceptsCompliantText(string $language, string $textLocation, string $compliantText): void
    {
        $this->assertGaranSuffixFollowsManufacturerGuaranteeOrSpecWording($language, $textLocation, $compliantText);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function labelFieldSuffixViolationProvider(): iterable
    {
        yield 'de, Zusatz nach anderem Wort' => ['tl_ls_shop_product.guaranteeBrand.0', 'Hersteller (Brand/Trademark)'];
        yield 'de, Wort zwischen Begriff und Zusatz' => ['tl_ls_shop_product.guaranteeBrand.0', 'Marke des Herstellers (Brand/Trademark)'];
        yield 'de, ohne Leerzeichen' => ['tl_ls_shop_variant.guaranteeBrand.0', 'Marke(Brand/Trademark)'];
        yield 'de, vertauschte Zusätze' => ['tl_ls_shop_product.guaranteeModelIdentifier.0', 'Modell (Brand/Trademark)'];
        yield 'de, Modell als Wortteil' => ['tl_ls_shop_variant.guaranteeModelIdentifier.0', 'Gerätemodell (Model identifier)'];
        yield 'de, Kleinschreibung im Zusatz' => ['MSC.ls_shop.misc.importErrors.valueInvalid_guaranteeBrand', 'Die Marke (brand/trademark) ist zu lang: Zeile(n) Nr. %s'];
        yield 'en, Zusatz nach anderem Wort' => ['tl_ls_shop_product.guaranteeBrand.0', 'Brand (Brand/Trademark)'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function labelFieldSuffixComplianceProvider(): iterable
    {
        yield 'de, Feldtitel Marke' => ['tl_ls_shop_product.guaranteeBrand.0', 'Marke (Brand/Trademark)'];
        yield 'de, Feldtitel Modell' => ['tl_ls_shop_product.guaranteeModelIdentifier.0', 'Modell (Model identifier)'];
        yield 'de, Meldung mit beiden Zusätzen' => ['MSC.ls_shop.misc.importErrors.productGuaranteeDataIncomplete', 'Die Marke (Brand/Trademark) oder das Modell (Model identifier) fehlt: Zeile(n) Nr. %s'];
        yield 'de, ohne Zusatz' => ['MSC.ls_shop.misc.productManagementApiErrors.productGuaranteeBrandMissing', 'Die Herstellergarantie ist eingeschaltet ("enableGaran"), aber die Marke ("guaranteeBrand") fehlt.'];
        yield 'en, ohne Zusatz' => ['tl_ls_shop_product.guaranteeBrand.0', 'Brand/Trademark'];
    }

    #[DataProvider('labelFieldSuffixViolationProvider')]
    public function testLabelFieldSuffixCheckFailsForViolatingText(string $textLocation, string $violatingText): void
    {
        $this->assertCheckFails(
            fn () => $this->assertLabelFieldSuffixesDirectlyFollowBrandAndModel($textLocation, $violatingText),
            $violatingText
        );
    }

    #[DataProvider('labelFieldSuffixComplianceProvider')]
    public function testLabelFieldSuffixCheckAcceptsCompliantText(string $textLocation, string $compliantText): void
    {
        $this->assertLabelFieldSuffixesDirectlyFollowBrandAndModel($textLocation, $compliantText);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function abbreviationViolationProvider(): iterable
    {
        yield 'de, LGN' => ['de', 'tl_ls_shop_product.lgnMode.1', 'Steuert die LGN für dieses Produkt.'];
        yield 'de, lgn klein' => ['de', 'tl_ls_shop_product.lgnMode.1', 'Steuert die lgn für dieses Produkt.'];
        yield 'de, LGN mit Bindestrich' => ['de', 'tl_member_group.lsShopLgnMode.1', 'Steuert die LGN-Mitteilung für Mitglieder dieser Gruppe.'];
        yield 'de, Garan gemischt' => ['de', 'MSC.ls_shop.dashboard.lgnAnnouncement', 'Mit Version 5.1.18 wurde Garan eingeführt.'];
        yield 'de, GLL' => ['de', 'MSC.ls_shop.dashboard.lgnAnnouncement', 'Früher hieß die Mitteilung GLL.'];
        yield 'de, Zusatz an nicht zulässiger Stelle' => ['de', 'tl_ls_shop_product.enableGaran.1', 'Zeigt die Kennzeichnung der Herstellergarantie (GARAN-Label).'];
        yield 'de, GARAN-Label ohne Klammern' => ['de', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Gewährleistung und GARAN-Label'];
        yield 'de, Kürzel neben Spaltenname in Importmeldung' => ['de', 'MSC.ls_shop.misc.importErrors.productValueInvalid_lgnMode', 'Feldwert für "lgnMode" (LGN) ist ungültig: Zeile(n) Nr. %s'];
        yield 'en, LGN' => ['en', 'tl_member_group.lsShopLgnMode.0', 'LGN notice'];
        yield 'en, garan neben Feldname in API-Meldung' => ['en', 'MSC.ls_shop.misc.productManagementApiErrors.garanModeInvalid', 'The value for "garanMode" (garan) is invalid.'];
        yield 'en, Kürzel ohne Label an zulässiger Stelle' => ['en', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Legal guarantee and manufacturer guarantee (GARAN)'];
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function abbreviationComplianceProvider(): iterable
    {
        yield 'de, Garantie als Wortteil' => ['de', 'tl_ls_shop_product.guaranteeDurationMonths.1', 'Die Garantiedauer der Herstellergarantie fehlt.'];
        yield 'de, Zusatz an zulässiger Stelle' => ['de', 'tl_ls_shop_product.lsShopGuaranteeLabels_legend', 'Gewährleistung und Herstellergarantie (GARAN-Label)'];
        yield 'de, Spaltennamen und Werte in Importmeldung' => ['de', 'MSC.ls_shop.misc.importErrors.productGuaranteeWithLgnModeNever', 'Die Herstellergarantie ist eingeschaltet ("enableGaran"), aber die gesetzliche Gewährleistung steht auf "never" ("lgnMode"): Zeile(n) Nr. %s'];
        yield 'en, Feldname und Werte in API-Meldung' => ['en', 'MSC.ls_shop.misc.productManagementApiErrors.garanModeInvalid', 'The value for "garanMode" (manufacturer guarantee of the variant) is invalid. Allowed are "inherit", "disabled", "override" or an empty value.'];
        yield 'en, Zusatz an zulässiger Stelle' => ['en', 'tl_ls_shop_product.enableGaran.0', 'Show manufacturer guarantee (GARAN label)'];
    }

    #[DataProvider('abbreviationViolationProvider')]
    public function testAbbreviationCheckFailsForViolatingText(string $language, string $textLocation, string $violatingText): void
    {
        $this->assertCheckFails(
            fn () => $this->assertTextContainsNoAbbreviationAsWholeWord($language, $textLocation, $violatingText),
            $violatingText
        );
    }

    #[DataProvider('abbreviationComplianceProvider')]
    public function testAbbreviationCheckAcceptsCompliantText(string $language, string $textLocation, string $compliantText): void
    {
        $this->assertTextContainsNoAbbreviationAsWholeWord($language, $textLocation, $compliantText);
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

    private function isImportOrApiMessageLocation(string $textLocation): bool
    {
        foreach (self::IMPORT_AND_API_MESSAGE_LOCATION_PREFIXES as $importOrApiMessageLocationPrefix) {
            if (str_starts_with($textLocation, $importOrApiMessageLocationPrefix)) {
                return true;
            }
        }

        return false;
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
     * "(GARAN-Label)" bzw. "(GARAN label)" folgt an jeder Stelle unmittelbar und durch genau ein
     * Leerzeichen getrennt auf das ganze Wort "Herstellergarantie" bzw. "manufacturer guarantee".
     */
    private function assertGaranSuffixDirectlyFollowsManufacturerGuarantee(string $language, string $textLocation, string $merchantText): void
    {
        $garanSuffix = self::GARAN_SUFFIX_BY_LANGUAGE[$language];
        $suffixAfterTermPattern = '/(?<![\p{L}\p{N}])(?i:' . preg_quote(self::MANUFACTURER_GUARANTEE_TERM_BY_LANGUAGE[$language], '/') . ') ' . preg_quote($garanSuffix, '/') . '/u';

        self::assertSame(
            substr_count($merchantText, $garanSuffix),
            preg_match_all($suffixAfterTermPattern, $merchantText),
            $textLocation . ': ' . $merchantText
        );
    }

    /**
     * Prüfung "unmittelbar nach" an jeder Fundstelle; an einer Fundstelle aus
     * `GARAN_SUFFIX_SPEC_WORDING_BY_LANGUAGE` stattdessen genau der dort festgelegte Wortlaut.
     */
    private function assertGaranSuffixFollowsManufacturerGuaranteeOrSpecWording(string $language, string $textLocation, string $merchantText): void
    {
        if (array_key_exists($textLocation, self::GARAN_SUFFIX_SPEC_WORDING_BY_LANGUAGE[$language])) {
            self::assertSame(self::GARAN_SUFFIX_SPEC_WORDING_BY_LANGUAGE[$language][$textLocation], $merchantText, $textLocation);

            return;
        }

        $this->assertGaranSuffixDirectlyFollowsManufacturerGuarantee($language, $textLocation, $merchantText);
    }

    /**
     * "(Brand/Trademark)" folgt an jeder Stelle unmittelbar und durch genau ein Leerzeichen
     * getrennt auf das ganze Wort "Marke", "(Model identifier)" ebenso auf "Modell". Der Zusatz
     * wird ohne Beachtung der Groß- und Kleinschreibung gezählt, muss aber genau so geschrieben
     * sein.
     */
    private function assertLabelFieldSuffixesDirectlyFollowBrandAndModel(string $textLocation, string $merchantText): void
    {
        foreach (self::LABEL_FIELD_SUFFIX_TERMS as $labelFieldSuffix => $germanTerm) {
            $suffixAfterTermPattern = '/(?<![\p{L}\p{N}])' . preg_quote($germanTerm, '/') . ' ' . preg_quote($labelFieldSuffix, '/') . '/u';

            self::assertSame(
                mb_substr_count(mb_strtolower($merchantText), mb_strtolower($labelFieldSuffix)),
                preg_match_all($suffixAfterTermPattern, $merchantText),
                $textLocation . ': ' . $merchantText
            );
        }
    }

    /**
     * Keine Kürzel "LGN", "GARAN" oder "GLL" als ganzes Wort, ohne Beachtung der Groß- und
     * Kleinschreibung; ausgenommen ist nur der Zusatz "(GARAN-Label)" bzw. "(GARAN label)" an
     * den zulässigen Stellen.
     */
    private function assertTextContainsNoAbbreviationAsWholeWord(string $language, string $textLocation, string $merchantText): void
    {
        $textToCheckForAbbreviations = in_array($textLocation, self::GARAN_SUFFIX_LOCATIONS, true)
            ? str_replace(self::GARAN_SUFFIX_BY_LANGUAGE[$language], '', $merchantText)
            : $merchantText;

        self::assertDoesNotMatchRegularExpression(self::ABBREVIATION_PATTERN, $textToCheckForAbbreviations, $textLocation . ': ' . $merchantText);
    }

    /**
     * Gegenprobe: Die Prüfung muss am verletzenden Text fehlschlagen.
     */
    private function assertCheckFails(callable $terminologyCheck, string $violatingText): void
    {
        try {
            $terminologyCheck();
        } catch (AssertionFailedError) {
            return;
        }

        self::fail('Die Prüfung erkennt den verletzenden Text nicht: ' . $violatingText);
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
