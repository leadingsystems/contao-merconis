<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Meldungen zu R1 bis R4 und Hinweise beim Öffnen nach Spec 27, Abschnitt 4: vorhanden in
 * `de` und `en`, ohne die Kürzel "LGN" und "GARAN" und ohne Feldnamen.
 */
final class LegalGuaranteeRuleMessagesLanguageTest extends TestCase
{
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';

    private const PRODUCT_MESSAGE_KEYS = [
        'lgnModeChangedForManufacturerGuarantee',
        'manufacturerGuaranteeDisabledByLgnMode',
        'lgnModeRejectedByOwnVariantDetails',
        'openHintProductNeverShown',
        'openHintStandardGroupOnlyAlwaysShow',
        'openHintVariantsWithOwnDetails',
    ];

    private const VARIANT_MESSAGE_KEYS = [
        'ownDetailsRejectedWithoutLegalGuarantee',
        'openHintParentProductWithoutLegalGuarantee',
    ];

    private const FORBIDDEN_FRAGMENTS = [
        'LGN',
        'GARAN',
        'lgnMode',
        'garanMode',
        'enableGaran',
        'lsShopLgnMode',
        'ls_shop_standardGroup',
        'guaranteeDurationMonths',
        'guaranteeBrand',
        'guaranteeModelIdentifier',
        '`',
    ];

    /*
     * Technische Werte statt ihrer Beschriftung; "disabled" fehlt, weil es im Englischen
     * auch ein gewöhnliches Wort ist.
     */
    private const FORBIDDEN_VALUE_NAME_PATTERN = '/\b(inherit|always|never|override|blocked|on|off)\b/';

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
    public function testProductMessagesExistWithoutAbbreviationsAndFieldNames(string $language): void
    {
        $productLanguage = $this->loadLanguageFile($language, 'tl_ls_shop_product')['tl_ls_shop_product'];

        self::assertSame(self::PRODUCT_MESSAGE_KEYS, array_keys($productLanguage['legalGuaranteeMessages']));

        foreach ($productLanguage['legalGuaranteeMessages'] as $messageKey => $messageText) {
            $this->assertMerchantText($messageKey, $messageText);
        }

        foreach (['lgnModeChangedForManufacturerGuarantee', 'lgnModeRejectedByOwnVariantDetails', 'openHintVariantsWithOwnDetails'] as $messageKey) {
            self::assertSame(1, substr_count($productLanguage['legalGuaranteeMessages'][$messageKey], '%s'), $messageKey);
        }
    }

    #[DataProvider('languageProvider')]
    public function testVariantMessagesExistWithoutAbbreviationsAndFieldNames(string $language): void
    {
        $variantLanguage = $this->loadLanguageFile($language, 'tl_ls_shop_variant')['tl_ls_shop_variant'];

        self::assertSame(self::VARIANT_MESSAGE_KEYS, array_keys($variantLanguage['legalGuaranteeMessages']));

        foreach ($variantLanguage['legalGuaranteeMessages'] as $messageKey => $messageText) {
            $this->assertMerchantText($messageKey, $messageText);
            self::assertStringNotContainsString('%s', $messageText, $messageKey);
        }
    }

    public function testMessagesUseValueLabelsFromSpec(): void
    {
        $productLanguageDe = $this->loadLanguageFile('de', 'tl_ls_shop_product')['tl_ls_shop_product']['legalGuaranteeMessages'];
        $productLanguageEn = $this->loadLanguageFile('en', 'tl_ls_shop_product')['tl_ls_shop_product']['legalGuaranteeMessages'];
        $variantLanguageDe = $this->loadLanguageFile('de', 'tl_ls_shop_variant')['tl_ls_shop_variant']['legalGuaranteeMessages'];
        $variantLanguageEn = $this->loadLanguageFile('en', 'tl_ls_shop_variant')['tl_ls_shop_variant']['legalGuaranteeMessages'];

        self::assertStringContainsString('"Nie anzeigen"', $productLanguageDe['openHintProductNeverShown']);
        self::assertStringContainsString('"Never show"', $productLanguageEn['openHintProductNeverShown']);
        self::assertStringContainsString('"Nur Produkte mit \'Immer anzeigen\'"', $productLanguageDe['openHintStandardGroupOnlyAlwaysShow']);
        self::assertStringContainsString('"Wie Mitgliedergruppe"', $productLanguageDe['openHintStandardGroupOnlyAlwaysShow']);
        self::assertStringContainsString('"Immer anzeigen"', $productLanguageDe['openHintStandardGroupOnlyAlwaysShow']);
        self::assertStringContainsString('"Only products set to \'Always show\'"', $productLanguageEn['openHintStandardGroupOnlyAlwaysShow']);
        self::assertStringContainsString('"As member group"', $productLanguageEn['openHintStandardGroupOnlyAlwaysShow']);
        self::assertStringContainsString('"Always show"', $productLanguageEn['openHintStandardGroupOnlyAlwaysShow']);
        self::assertStringContainsString('"Eigene Angaben"', $productLanguageDe['lgnModeRejectedByOwnVariantDetails']);
        self::assertStringContainsString('"Own details"', $productLanguageEn['lgnModeRejectedByOwnVariantDetails']);
        self::assertStringContainsString('zuerst die Einstellung zur gesetzlichen Gewährleistung am Produkt ändern und speichern', $variantLanguageDe['ownDetailsRejectedWithoutLegalGuarantee']);
        self::assertStringContainsString('change and save the legal guarantee notice setting of the product first', $variantLanguageEn['ownDetailsRejectedWithoutLegalGuarantee']);
        self::assertStringContainsString('Dauer, Marke und Modell bleiben gespeichert', $productLanguageDe['manufacturerGuaranteeDisabledByLgnMode']);
        self::assertStringContainsString('Duration, brand and model remain saved', $productLanguageEn['manufacturerGuaranteeDisabledByLgnMode']);
    }

    private function assertMerchantText(string $messageKey, mixed $messageText): void
    {
        self::assertIsString($messageText, $messageKey);
        self::assertNotSame('', trim($messageText), $messageKey);

        foreach (self::FORBIDDEN_FRAGMENTS as $forbiddenFragment) {
            self::assertStringNotContainsString($forbiddenFragment, $messageText, $messageKey);
        }

        self::assertDoesNotMatchRegularExpression(self::FORBIDDEN_VALUE_NAME_PATTERN, $messageText, $messageKey);

        foreach (["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{201E}"] as $typographicQuote) {
            self::assertStringNotContainsString($typographicQuote, $messageText, $messageKey);
        }
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
