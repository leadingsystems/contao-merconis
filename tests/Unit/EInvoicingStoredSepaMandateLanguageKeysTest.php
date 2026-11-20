<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EInvoicingStoredSepaMandateLanguageKeysTest extends TestCase
{
    private const LANGUAGE_KEYS = [
        'einvoicing_sepa_mandate_heading',
        'einvoicing_sepa_mandate_account_holder_label',
        'einvoicing_sepa_mandate_iban_label',
        'einvoicing_sepa_mandate_reference_label',
        'einvoicing_sepa_mandate_change_notice',
        'einvoicing_sepa_mandate_value_unavailable',
    ];

    /**
     * @return array<string, array{string}>
     */
    public static function languageProvider(): array
    {
        return [
            'de' => ['de'],
            'en' => ['en'],
        ];
    }

    #[DataProvider('languageProvider')]
    public function testStoredSepaMandateLanguageKeysExistInLanguageFile(string $language): void
    {
        $languageStrings = $this->loadMerconisLanguageStrings($language);

        foreach (self::LANGUAGE_KEYS as $languageKey) {
            self::assertArrayHasKey($languageKey, $languageStrings, $language . ': ' . $languageKey);
            self::assertIsString($languageStrings[$languageKey], $language . ': ' . $languageKey);
            self::assertNotSame('', trim($languageStrings[$languageKey]), $language . ': ' . $languageKey);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadMerconisLanguageStrings(string $language): array
    {
        $originalLanguageGlobals = $GLOBALS['TL_LANG'] ?? null;
        $GLOBALS['TL_LANG'] = [];

        include dirname(__DIR__, 2) . '/src/Resources/contao/languages/' . $language . '/default.php';

        $languageStrings = $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis'];

        if ($originalLanguageGlobals === null) {
            unset($GLOBALS['TL_LANG']);
        } else {
            $GLOBALS['TL_LANG'] = $originalLanguageGlobals;
        }

        return $languageStrings;
    }
}
