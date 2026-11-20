<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EInvoicingErrorCodeCatalogTest extends TestCase
{
    private const LANGUAGES = ['de', 'en'];

    /**
     * Texte der Händlerbenachrichtigung und die erwarteten Platzhalter
     * (alphabetisch sortiert).
     */
    private const MERCHANT_NOTIFICATION_PLACEHOLDERS = [
        'subject' => ['orderNumber'],
        'introduction' => [],
        'orderNumber' => ['orderNumber'],
        'messageType' => ['messageTypeId', 'messageTypeTitle'],
        'messageTypeUnknown' => [],
        'invoiceNumber' => ['invoiceNumber'],
        'failureReasons' => [],
        'regenerationWithInvoiceNumber' => ['invoiceNumber', 'orderNumber'],
        'regenerationWithoutInvoiceNumber' => ['orderNumber'],
    ];

    public function testEveryCodeFollowsTheCodeSchema(): void
    {
        foreach (EInvoicingErrorCode::cases() as $errorCode) {
            self::assertMatchesRegularExpression('/^EINVOICING_[A-Z0-9_]+$/', $errorCode->value);
        }
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function languageProvider(): iterable
    {
        foreach (self::LANGUAGES as $language) {
            yield $language => [$language];
        }
    }

    #[DataProvider('languageProvider')]
    public function testEveryCodeHasADescriptionWithMatchingPlaceholders(string $language): void
    {
        $descriptionsByCode = $this->loadDescriptionsByCode($language);

        foreach (EInvoicingErrorCode::cases() as $errorCode) {
            self::assertArrayHasKey($errorCode->value, $descriptionsByCode, $language . ': ' . $errorCode->value);

            $expectedPlaceholders = $errorCode->parameterNames();
            sort($expectedPlaceholders);
            $descriptionEntry = $descriptionsByCode[$errorCode->value];

            if ($errorCode->hasOptionalParameters()) {
                self::assertIsArray($descriptionEntry, $language . ': ' . $errorCode->value);
                self::assertSame([], $this->extractPlaceholders($descriptionEntry['withoutParameters']));
                self::assertSame($expectedPlaceholders, $this->extractPlaceholders($descriptionEntry['withParameters']));
                self::assertNotSame('', trim($descriptionEntry['withoutParameters']));

                continue;
            }

            self::assertIsString($descriptionEntry, $language . ': ' . $errorCode->value);
            self::assertNotSame('', trim($descriptionEntry), $language . ': ' . $errorCode->value);
            self::assertSame(
                $expectedPlaceholders,
                $this->extractPlaceholders($descriptionEntry),
                $language . ': ' . $errorCode->value
            );
        }
    }

    #[DataProvider('languageProvider')]
    public function testMerchantNotificationTextsExistWithTheExpectedPlaceholders(string $language): void
    {
        $notificationTexts = $this->loadLanguageStrings($language)['einvoicingMerchantNotification'];

        self::assertSame(
            array_keys(self::MERCHANT_NOTIFICATION_PLACEHOLDERS),
            array_keys($notificationTexts),
            $language
        );

        foreach (self::MERCHANT_NOTIFICATION_PLACEHOLDERS as $textKey => $expectedPlaceholders) {
            self::assertIsString($notificationTexts[$textKey], $language . ': ' . $textKey);
            self::assertNotSame('', trim($notificationTexts[$textKey]), $language . ': ' . $textKey);
            self::assertSame($expectedPlaceholders, $this->extractPlaceholders($notificationTexts[$textKey]), $language . ': ' . $textKey);
        }
    }

    #[DataProvider('languageProvider')]
    public function testLanguageFileContainsNoCodesOutsideTheCatalog(string $language): void
    {
        $catalogCodes = array_map(
            static fn (EInvoicingErrorCode $errorCode): string => $errorCode->value,
            EInvoicingErrorCode::cases()
        );
        $languageCodes = array_keys($this->loadDescriptionsByCode($language));

        sort($catalogCodes);
        sort($languageCodes);

        self::assertSame($catalogCodes, $languageCodes);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDescriptionsByCode(string $language): array
    {
        return $this->loadLanguageStrings($language)['einvoicingFailureReasons'];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLanguageStrings(string $language): array
    {
        $languageStringsExisted = array_key_exists('TL_LANG', $GLOBALS);
        $previousLanguageStrings = $GLOBALS['TL_LANG'] ?? null;

        try {
            require __DIR__ . '/../../src/Resources/contao/languages/' . $language . '/einvoicing.php';

            return $GLOBALS['TL_LANG']['lsShopEinvoicing']['MSC']['ls_contao-merconis'];
        } finally {
            if ($languageStringsExisted) {
                $GLOBALS['TL_LANG'] = $previousLanguageStrings;
            } else {
                unset($GLOBALS['TL_LANG']);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function extractPlaceholders(string $description): array
    {
        preg_match_all('/\{([A-Za-z]+)\}/', $description, $matches);
        $placeholders = array_values(array_unique($matches[1]));
        sort($placeholders);

        return $placeholders;
    }
}
