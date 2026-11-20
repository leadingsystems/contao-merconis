<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../Support/EInvoicingFailureTestSupport.php';

use Contao\CoreBundle\Framework\ContaoFramework;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReason;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReasonFormatter;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingFailureTestSupport;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingLanguageFileLoaderStub;
use PHPUnit\Framework\TestCase;

final class EInvoicingFailureReasonFormatterTest extends TestCase
{
    private bool $languageStringsExisted = false;

    private mixed $originalLanguageStrings = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageStringsExisted = array_key_exists('TL_LANG', $GLOBALS);
        $this->originalLanguageStrings = $GLOBALS['TL_LANG'] ?? null;
        EInvoicingLanguageFileLoaderStub::reset();
    }

    protected function tearDown(): void
    {
        if ($this->languageStringsExisted) {
            $GLOBALS['TL_LANG'] = $this->originalLanguageStrings;
        } else {
            unset($GLOBALS['TL_LANG']);
        }

        EInvoicingLanguageFileLoaderStub::reset();

        parent::tearDown();
    }

    public function testFormatsReasonWithParameterInEnglish(): void
    {
        $formattedFailureReason = $this->createFormatter()->format(
            new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_152, ['lineId' => '3']),
            'en'
        );

        self::assertSame(
            '[EINVOICING_MISSING_BT_152] The VAT rate (BT-152) of line item 3 is missing.',
            $formattedFailureReason
        );
    }

    public function testFormatsReasonWithParameterInGerman(): void
    {
        $formattedFailureReason = $this->createFormatter()->format(
            new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_89, ['paymentMeansCode' => '59']),
            'de'
        );

        self::assertSame(
            '[EINVOICING_MISSING_BT_89] Für den Payment Means Code 59 ist die Mandatsreferenz (BT-89) erforderlich.',
            $formattedFailureReason
        );
    }

    public function testFormatsReasonWithoutParametersInBothLanguages(): void
    {
        $formatter = $this->createFormatter();
        $failureReason = new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_27);

        self::assertSame('[EINVOICING_MISSING_BT_27] The seller name (BT-27) is missing.', $formatter->format($failureReason, 'en'));
        self::assertSame('[EINVOICING_MISSING_BT_27] Der Verkäufername (BT-27) fehlt.', $formatter->format($failureReason, 'de'));
    }

    public function testLoadsTheEInvoicingLanguageFileWithoutCacheForTheRequestedLanguage(): void
    {
        $this->createFormatter()->format(new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_27), 'de');

        self::assertSame([['einvoicing', 'de', true]], EInvoicingLanguageFileLoaderStub::$calls);
    }

    public function testRestoresPreviouslyLoadedLanguageStringsAfterResolution(): void
    {
        $GLOBALS['TL_LANG'] = [
            'MSC' => ['textDirection' => 'previous-direction', 'previousKey' => 'previous value'],
            'lsShopEinvoicing' => ['marker' => 'previously loaded language'],
        ];
        $languageStringsBeforeResolution = $GLOBALS['TL_LANG'];

        $this->createFormatter()->format(new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_27), 'en');

        self::assertSame($languageStringsBeforeResolution, $GLOBALS['TL_LANG']);
    }

    public function testRemovesLanguageStringsAgainWhenNoneWereLoadedBefore(): void
    {
        unset($GLOBALS['TL_LANG']);

        $this->createFormatter()->format(new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_27), 'de');

        self::assertArrayNotHasKey('TL_LANG', $GLOBALS);
    }

    public function testRestoresLanguageStringsWhenLoadingFails(): void
    {
        $GLOBALS['TL_LANG'] = ['MSC' => ['textDirection' => 'previous-direction']];
        $languageStringsBeforeResolution = $GLOBALS['TL_LANG'];
        EInvoicingLanguageFileLoaderStub::$throwOnLoad = true;

        try {
            $this->createFormatter()->format(new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_27), 'de');
            self::fail('Expected the language file loading to fail.');
        } catch (\RuntimeException $runtimeException) {
            self::assertSame('Language file loading failed.', $runtimeException->getMessage());
        }

        self::assertSame($languageStringsBeforeResolution, $GLOBALS['TL_LANG']);
    }

    public function testFormatsSimultaneousReasonsIndividuallyInOrder(): void
    {
        $formattedFailureReasons = $this->createFormatter()->formatThrowable(
            new InvoiceValidationException([
                new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_89, ['paymentMeansCode' => '59']),
                new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_91, ['paymentMeansCode' => '59']),
            ]),
            'en'
        );

        self::assertSame(
            [
                '[EINVOICING_MISSING_BT_89] Payment means code 59 requires the mandate reference (BT-89).',
                '[EINVOICING_MISSING_BT_91] Payment means code 59 requires the buyer IBAN (BT-91).',
            ],
            $formattedFailureReasons
        );
    }

    public function testRuntimeErrorWithoutMessageUsesDescriptionWithoutEmptyParameter(): void
    {
        $formatter = $this->createFormatter();

        self::assertSame(
            ['[EINVOICING_RUNTIME_ERROR] An unexpected error occurred during e-invoice generation.'],
            $formatter->formatThrowable(new \RuntimeException(''), 'en')
        );
        self::assertSame(
            ['[EINVOICING_RUNTIME_ERROR] Bei der Erzeugung der E-Rechnung ist ein unerwarteter Fehler aufgetreten.'],
            $formatter->formatThrowable(new \RuntimeException('   '), 'de')
        );
    }

    public function testRuntimeErrorKeepsTheOriginalMessageUntranslated(): void
    {
        $formatter = $this->createFormatter();
        $foreignException = new \LogicException('Library failure: invalid XML node.');

        self::assertSame(
            ['[EINVOICING_RUNTIME_ERROR] An unexpected error occurred during e-invoice generation: Library failure: invalid XML node.'],
            $formatter->formatThrowable($foreignException, 'en')
        );
        self::assertSame(
            ['[EINVOICING_RUNTIME_ERROR] Bei der Erzeugung der E-Rechnung ist ein unerwarteter Fehler aufgetreten: Library failure: invalid XML node.'],
            $formatter->formatThrowable($foreignException, 'de')
        );
    }

    private function createFormatter(): EInvoicingFailureReasonFormatter
    {
        return new EInvoicingFailureReasonFormatter(
            EInvoicingFailureTestSupport::configureFrameworkAdapters($this->createMock(ContaoFramework::class))
        );
    }
}
