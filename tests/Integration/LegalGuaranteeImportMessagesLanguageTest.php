<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationApplier;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use Merconis\Core\ls_shop_importController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/Resources/contao/classes/ls_shop_importController.php';

/**
 * Meldungen zu Spec 27, Abschnitt 4, im Import-Log und in der Product Management API: in
 * `de` und `en` vorhanden, ohne die Kürzel "LGN" und "GARAN" und ohne Feldnamen. Die
 * CSV-Werte dürfen genannt werden (Spec 27, Abschnitte 3.1 und 3.3).
 */
final class LegalGuaranteeImportMessagesLanguageTest extends TestCase
{
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';

    /**
     * Code => Anzahl der Platzhalter `%s`.
     */
    private const MESSAGE_PLACEHOLDER_COUNTS = [
        ProductGuaranteeConfigurationValidator::MESSAGE_LGN_INVALID_MODE => 1,
        ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INVALID_MODE => 1,
        ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND => 0,
        ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL => 0,
        ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION => 0,
        ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION => 1,
        ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_REQUIRED => 0,
        ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_REQUIRED_ALWAYS => 0,
        ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_VARIANT_OVERRIDE => 1,
        ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_LGN_REQUIRED => 0,
    ];

    private const FORBIDDEN_FRAGMENTS = [
        'LGN',
        'GARAN',
        'lgnMode',
        'garanMode',
        'enableGaran',
        'enableGll',
        'lsShopLgnMode',
        'ls_shop_standardGroup',
        'guaranteeDurationMonths',
        'guaranteeBrand',
        'guaranteeModelIdentifier',
        '`',
    ];

    private mixed $originalTlLang;
    private mixed $originalSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlLang = $GLOBALS['TL_LANG'] ?? null;
        $this->originalSession = $_SESSION ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalTlLang === null) {
            unset($GLOBALS['TL_LANG']);
        } else {
            $GLOBALS['TL_LANG'] = $this->originalTlLang;
        }

        if ($this->originalSession === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $this->originalSession;
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
    public function testEveryValidatorMessageHasMerchantTextWithoutAbbreviationsAndFieldNames(string $language): void
    {
        $importLanguage = $this->loadDefaultLanguageFile($language);

        self::assertSame(
            array_keys(self::MESSAGE_PLACEHOLDER_COUNTS),
            array_keys($importLanguage['messages'])
        );

        foreach ($importLanguage['messages'] as $messageCode => $messageText) {
            self::assertIsString($messageText, $messageCode);
            self::assertNotSame('', trim($messageText), $messageCode);
            self::assertSame(self::MESSAGE_PLACEHOLDER_COUNTS[$messageCode], substr_count($messageText, '%s'), $messageCode);
            self::assertSame(0, substr_count(str_replace('%s', '', $messageText), '%'), $messageCode);

            foreach (self::FORBIDDEN_FRAGMENTS as $forbiddenFragment) {
                self::assertStringNotContainsString($forbiddenFragment, $messageText, $messageCode);
            }

            foreach (["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{201E}"] as $typographicQuote) {
                self::assertStringNotContainsString($typographicQuote, $messageText, $messageCode);
            }
        }

        self::assertSame(1, substr_count($importLanguage['importLogEntry'], '%d'));
        self::assertSame(2, substr_count($importLanguage['importLogEntry'], '%s'));
        self::assertSame(2, substr_count($importLanguage['apiMessage'], '%s'));
    }

    #[DataProvider('languageProvider')]
    public function testMessagesNameTheWay(string $language): void
    {
        $messages = $this->loadDefaultLanguageFile($language)['messages'];
        $texts = [
            'de' => [
                'lgn_required' => ['"inherit" oder "always" angeben', 'Herstellergarantie ausschalten', 'gesetzliche Gewährleistung'],
                'lgn_required_always' => ['"always" angeben', 'Herstellergarantie ausschalten', 'Standard-Benutzergruppe'],
                'variant_override' => ['"override"', 'erneut übertragen'],
                'override_lgn_required' => ['am Produkt ändern'],
                'lgn_invalid_mode' => ['"inherit", "always" und "never"'],
                'garan_invalid_mode' => ['"inherit", "disabled" und "override"'],
            ],
            'en' => [
                'lgn_required' => ['"inherit" or "always"', 'disable the manufacturer guarantee', 'legal guarantee notice'],
                'lgn_required_always' => ['specify "always"', 'disable the manufacturer guarantee', 'standard user group'],
                'variant_override' => ['"override"', 'transfer the row again'],
                'override_lgn_required' => ['setting of the product first'],
                'lgn_invalid_mode' => ['"inherit", "always" and "never"'],
                'garan_invalid_mode' => ['"inherit", "disabled" and "override"'],
            ],
        ][$language];
        $messageCodes = [
            'lgn_required' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_REQUIRED,
            'lgn_required_always' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_REQUIRED_ALWAYS,
            'variant_override' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_VARIANT_OVERRIDE,
            'override_lgn_required' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_LGN_REQUIRED,
            'lgn_invalid_mode' => ProductGuaranteeConfigurationValidator::MESSAGE_LGN_INVALID_MODE,
            'garan_invalid_mode' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INVALID_MODE,
        ];

        foreach ($texts as $messageLabel => $expectedFragments) {
            foreach ($expectedFragments as $expectedFragment) {
                self::assertStringContainsString($expectedFragment, $messages[$messageCodes[$messageLabel]], $messageLabel);
            }
        }
    }

    public function testImportLogEntryUsesRowFieldAndLanguageText(): void
    {
        $this->loadDefaultLanguageFile('de');
        $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] = 9;
        $reflectionClass = new \ReflectionClass(ls_shop_importController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();
        $reflectionClass->getProperty('bln_importFileHasRowForVersionAndSpecialChars')->setValue($controller, true);

        $logEntry = $reflectionClass->getMethod('formatLegalGuaranteeImportWarning')->invoke($controller, [
            'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_VARIANT_OVERRIDE,
            'field' => 'lgnMode',
            'parameters' => ['VAR-1, VAR-2'],
        ]);

        self::assertSame(
            'Zeile 7, Feld "lgnMode": Die Zeile wurde nicht übernommen, weil die Mitteilung über die gesetzliche Gewährleistung damit in der Standard-Benutzergruppe nicht angezeigt würde und folgende Varianten bei der Herstellergarantie "override" verwenden: VAR-1, VAR-2. Bitte zuerst diese Varianten auf einen anderen Wert als "override" stellen und die Zeile danach erneut übertragen oder für die gesetzliche Gewährleistung einen Wert angeben, mit dem die Mitteilung angezeigt wird.',
            $logEntry
        );
    }

    public function testApiMessageUsesFieldAndLanguageText(): void
    {
        $this->loadDefaultLanguageFile('en');

        self::assertSame(
            'field "lgnMode": The row was not applied because "sometimes" is not a valid value for the legal guarantee notice setting. Allowed values are "inherit", "always" and "never".; field "guaranteeBrand": The row was not applied because the Brand/Trademark for the manufacturer guarantee is missing.',
            (new ProductGuaranteeConfigurationApplier())->formatWriteErrorMessage([
                [
                    'code' => ProductGuaranteeConfigurationValidator::MESSAGE_LGN_INVALID_MODE,
                    'field' => 'lgnMode',
                    'parameters' => ['sometimes'],
                ],
                [
                    'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                    'field' => 'guaranteeBrand',
                    'parameters' => [],
                ],
            ])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDefaultLanguageFile(string $language): array
    {
        $GLOBALS['TL_LANG'] = [];

        require self::LANGUAGE_BASE_PATH . $language . '/default.php';

        return $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuaranteeImport'];
    }
}
