<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Meldungen der Dateivalidierung des Imports unter `importErrors` und Meldungen der
 * Vorprüfung der Product Management API unter `productManagementApiErrors` (Spec 27,
 * Abschnitte "Terminologie" und 10.5; Implementierungsentscheidung 0034): in `de` und `en`
 * vorhanden, ohne die Kürzel "LGN", "GARAN" und "GLL". Die früheren Schlüssel unter
 * `legalGuaranteeImport` gibt es nicht mehr.
 */
final class LegalGuaranteeImportMessagesLanguageTest extends TestCase
{
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';

    /**
     * Fehlerarten der Dateivalidierung zu Spec 27, Abschnitte 10.2 bis 10.4.
     */
    private const IMPORT_ERROR_KEYS = [
        'productValueInvalid_lgnMode',
        'variantValueInvalid_garanMode',
        'valueInvalid_guaranteeDurationMonths',
        'valueInvalid_guaranteeBrand',
        'valueInvalid_guaranteeModelIdentifier',
        'productGuaranteeDataIncomplete',
        'productGuaranteeWithLgnModeNever',
        'variantGuaranteeDataIncomplete',
        'variantGuaranteeOverrideWithParentLgnModeNever',
    ];

    /**
     * Meldungen der Vorprüfung zu Spec 27, Abschnitte 10.2 bis 10.5, je Feld.
     */
    private const API_ERROR_KEYS = [
        'lgnModeInvalid',
        'garanModeInvalid',
        'guaranteeDurationMonthsInvalid',
        'guaranteeBrandInvalid',
        'guaranteeModelIdentifierInvalid',
        'productGuaranteeDurationMonthsMissing',
        'productGuaranteeBrandMissing',
        'productGuaranteeModelIdentifierMissing',
        'variantGuaranteeDurationMonthsMissing',
        'variantGuaranteeBrandMissing',
        'variantGuaranteeModelIdentifierMissing',
        'productGuaranteeWithLgnModeNever',
        'variantGuaranteeOverrideWithParentLgnModeNever',
    ];

    /**
     * Spaltennamen und interne Werte, so wie sie in der Importdatei beziehungsweise im Request
     * stehen; nur sie dürfen in den Meldungen in Anführungszeichen stehen (Spec 27, Abschnitt
     * "Terminologie").
     */
    private const IMPORT_FILE_AND_REQUEST_TOKENS = [
        'lgnMode',
        'enableGaran',
        'garanMode',
        'guaranteeDurationMonths',
        'guaranteeBrand',
        'guaranteeModelIdentifier',
        'inherit',
        'always',
        'never',
        'disabled',
        'override',
    ];

    /**
     * Meldungen zu einem unzulässigen Wert nennen die zulässigen Werte (Spec 27, Abschnitt 10.5).
     */
    private const IMPORT_ERROR_ALLOWED_VALUES = [
        'de' => [
            'productValueInvalid_lgnMode' => ['"inherit", "always", "never" oder ein leeres Feld'],
            'variantValueInvalid_garanMode' => ['"inherit", "disabled", "override" oder ein leeres Feld'],
            'valueInvalid_guaranteeDurationMonths' => ['ganze Monate von 30 bis 360 in Schritten von 6', 'ohne führende Null', 'leeres Feld'],
            'valueInvalid_guaranteeBrand' => ['höchstens 255 Zeichen'],
            'valueInvalid_guaranteeModelIdentifier' => ['höchstens 255 Zeichen'],
        ],
        'en' => [
            'productValueInvalid_lgnMode' => ['"inherit", "always", "never" or an empty field'],
            'variantValueInvalid_garanMode' => ['"inherit", "disabled", "override" or an empty field'],
            'valueInvalid_guaranteeDurationMonths' => ['whole months from 30 to 360 in steps of 6', 'without leading zero', 'empty field'],
            'valueInvalid_guaranteeBrand' => ['not be longer than 255 characters'],
            'valueInvalid_guaranteeModelIdentifier' => ['not be longer than 255 characters'],
        ],
    ];

    /**
     * Wie beim Import, zusätzlich die im Request zulässigen Typen (Abschnitt 10.5, "Werte ohne
     * Zeichenkette").
     */
    private const API_ERROR_ALLOWED_VALUES = [
        'de' => [
            'lgnModeInvalid' => ['"inherit", "always", "never" oder ein leerer Wert'],
            'garanModeInvalid' => ['"inherit", "disabled", "override" oder ein leerer Wert'],
            'guaranteeDurationMonthsInvalid' => ['ganze Monate von 30 bis 360 in Schritten von 6', 'als Ganzzahl oder als Zeichenkette', 'ohne führende Null', 'leerer Wert'],
            'guaranteeBrandInvalid' => ['Zeichenkette oder Ganzzahl', 'höchstens 255 Zeichen', 'leerer Wert'],
            'guaranteeModelIdentifierInvalid' => ['Zeichenkette oder Ganzzahl', 'höchstens 255 Zeichen', 'leerer Wert'],
        ],
        'en' => [
            'lgnModeInvalid' => ['"inherit", "always", "never" or an empty value'],
            'garanModeInvalid' => ['"inherit", "disabled", "override" or an empty value'],
            'guaranteeDurationMonthsInvalid' => ['whole months from 30 to 360 in steps of 6', 'as an integer or as a string', 'without leading zero', 'empty value'],
            'guaranteeBrandInvalid' => ['string or integer', 'up to 255 characters', 'empty value'],
            'guaranteeModelIdentifierInvalid' => ['string or integer', 'up to 255 characters', 'empty value'],
        ],
    ];

    private const IMPORT_ERROR_ROW_NUMBER_SUFFIX = [
        'de' => ': Zeile(n) Nr. %s',
        'en' => ': Line(s) no. %s',
    ];

    private const FORBIDDEN_TERMS = [
        'LGN',
        'GARAN',
        'GLL',
        'Haltbarkeitsgarantie',
        'durability guarantee',
        'Standard-Mitgliedergruppe',
        'standard member group',
        'enableGll',
        '`',
    ];

    private const MERCHANT_TERMS = [
        'de' => ['legalGuarantee' => 'gesetzliche Gewährleistung', 'manufacturerGuarantee' => 'Herstellergarantie'],
        'en' => ['legalGuarantee' => 'legal guarantee notice', 'manufacturerGuarantee' => 'manufacturer guarantee'],
    ];

    /**
     * Meldungen nur zu `lgnMode`, ohne Bezug auf die Herstellergarantie.
     */
    private const LEGAL_GUARANTEE_ONLY_KEYS = ['productValueInvalid_lgnMode', 'lgnModeInvalid'];

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

    /**
     * @return iterable<string, array{string}>
     */
    public static function languageProvider(): iterable
    {
        yield 'de' => ['de'];
        yield 'en' => ['en'];
    }

    /**
     * Je Fehlerart ein Schlüssel mit genau einem Platzhalter für die Zeilennummern am Ende, wie
     * die bestehenden Meldungen der Dateivalidierung (Implementierungsentscheidung 0034).
     */
    #[DataProvider('languageProvider')]
    public function testEveryImportErrorHasTextWithRowNumberPlaceholder(string $language): void
    {
        $importErrors = $this->loadShopMiscLanguage($language)['importErrors'];

        foreach (self::IMPORT_ERROR_KEYS as $errorKey) {
            self::assertArrayHasKey($errorKey, $importErrors);
            self::assertIsString($importErrors[$errorKey], $errorKey);
            self::assertSame(1, substr_count($importErrors[$errorKey], '%s'), $errorKey);
            self::assertSame(0, substr_count(str_replace('%s', '', $importErrors[$errorKey]), '%'), $errorKey);
            self::assertStringEndsWith(self::IMPORT_ERROR_ROW_NUMBER_SUFFIX[$language], $importErrors[$errorKey], $errorKey);
        }
    }

    /**
     * Die Vorprüfung ordnet jede Meldung einem Datensatz und einem Feld zu; der Text selbst hat
     * keinen Platzhalter.
     */
    #[DataProvider('languageProvider')]
    public function testEveryApiErrorHasTextWithoutPlaceholder(string $language): void
    {
        $apiErrors = $this->loadShopMiscLanguage($language)['productManagementApiErrors'];

        self::assertSame(self::API_ERROR_KEYS, array_keys($apiErrors));

        foreach ($apiErrors as $errorKey => $messageText) {
            self::assertIsString($messageText, $errorKey);
            self::assertNotSame('', trim($messageText), $errorKey);
            self::assertStringNotContainsString('%', $messageText, $errorKey);
        }
    }

    #[DataProvider('languageProvider')]
    public function testImportErrorsFollowTerminology(string $language): void
    {
        $importErrors = $this->loadShopMiscLanguage($language)['importErrors'];

        foreach (self::IMPORT_ERROR_KEYS as $errorKey) {
            $this->assertMessageFollowsTerminology($language, $errorKey, $importErrors[$errorKey]);
        }
    }

    #[DataProvider('languageProvider')]
    public function testApiErrorsFollowTerminology(string $language): void
    {
        $apiErrors = $this->loadShopMiscLanguage($language)['productManagementApiErrors'];

        foreach (self::API_ERROR_KEYS as $errorKey) {
            $this->assertMessageFollowsTerminology($language, $errorKey, $apiErrors[$errorKey]);
        }
    }

    #[DataProvider('languageProvider')]
    public function testImportErrorsForInvalidValuesNameAllowedValues(string $language): void
    {
        $importErrors = $this->loadShopMiscLanguage($language)['importErrors'];

        foreach (self::IMPORT_ERROR_ALLOWED_VALUES[$language] as $errorKey => $expectedFragments) {
            foreach ($expectedFragments as $expectedFragment) {
                self::assertStringContainsString($expectedFragment, $importErrors[$errorKey], $errorKey);
            }
        }
    }

    #[DataProvider('languageProvider')]
    public function testApiErrorsForInvalidValuesNameAllowedValues(string $language): void
    {
        $apiErrors = $this->loadShopMiscLanguage($language)['productManagementApiErrors'];

        foreach (self::API_ERROR_ALLOWED_VALUES[$language] as $errorKey => $expectedFragments) {
            foreach ($expectedFragments as $expectedFragment) {
                self::assertStringContainsString($expectedFragment, $apiErrors[$errorKey], $errorKey);
            }
        }
    }

    #[DataProvider('languageProvider')]
    public function testFormerLegalGuaranteeImportKeysAreRemoved(string $language): void
    {
        $this->loadShopMiscLanguage($language);

        self::assertArrayNotHasKey('legalGuaranteeImport', $GLOBALS['TL_LANG']['MSC']['ls_shop']);
        self::assertStringNotContainsString(
            'legalGuaranteeImport',
            (string) file_get_contents(self::LANGUAGE_BASE_PATH . $language . '/default.php')
        );
    }

    /**
     * Spec 27, Abschnitt "Terminologie": keine Kürzel, keine Rechts- oder früheren Begriffe;
     * Spaltennamen und interne Werte nur so, wie sie in der Importdatei beziehungsweise im
     * Request stehen; die Bezeichnungen des amtlichen Labels im Deutschen nur als Klammerzusatz.
     */
    private function assertMessageFollowsTerminology(string $language, string $errorKey, string $messageText): void
    {
        foreach (self::FORBIDDEN_TERMS as $forbiddenTerm) {
            self::assertStringNotContainsString($forbiddenTerm, $messageText, $errorKey);
        }

        foreach (["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{201E}"] as $typographicQuote) {
            self::assertStringNotContainsString($typographicQuote, $messageText, $errorKey);
        }

        preg_match_all('/"([^"]*)"/', $messageText, $quotedTokens);

        foreach ($quotedTokens[1] as $quotedToken) {
            self::assertContains($quotedToken, self::IMPORT_FILE_AND_REQUEST_TOKENS, $errorKey);
        }

        if (!in_array($errorKey, self::LEGAL_GUARANTEE_ONLY_KEYS, true)) {
            self::assertStringContainsString(self::MERCHANT_TERMS[$language]['manufacturerGuarantee'], $messageText, $errorKey);
        }

        if (str_contains($messageText, '"lgnMode"')) {
            self::assertStringContainsString(self::MERCHANT_TERMS[$language]['legalGuarantee'], $messageText, $errorKey);
        }

        foreach (['Brand/Trademark', 'Model identifier'] as $labelFieldDesignation) {
            $suffixCount = substr_count($messageText, '(' . $labelFieldDesignation . ')');

            if ($language === 'de') {
                self::assertSame(substr_count($messageText, $labelFieldDesignation), $suffixCount, $errorKey);
            } else {
                self::assertSame(0, $suffixCount, $errorKey);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadShopMiscLanguage(string $language): array
    {
        $GLOBALS['TL_LANG'] = [];

        require self::LANGUAGE_BASE_PATH . $language . '/default.php';

        return $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc'];
    }
}
