<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../Support/EInvoicingFailureTestSupport.php';

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingOrderMessageHook;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\EInvoicingGenerationException;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReason;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReasonFormatter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingMessageTypeContext;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingConfigStub;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingFailureTestSupport;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingFallbackLanguageStub;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingLanguageFileLoaderStub;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingRecordingFailureReporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

final class EInvoicingFailureReporterTest extends TestCase
{
    private const ORDER_ID = 84;

    private const ORDER_NUMBER = '2026112084';

    private const INVOICE_NUMBER = '2026112019';

    private const INVOICE_MESSAGE_TYPE_ID = 4;

    private const RUNTIME_ERROR_WITHOUT_MESSAGE = '[EINVOICING_RUNTIME_ERROR] An unexpected error occurred during e-invoice generation.';

    private bool $languageStringsExisted = false;

    private mixed $originalLanguageStrings = null;

    private mixed $originalLanguage = null;

    /**
     * @var list<string>
     */
    private array $loggedErrors = [];

    private EInvoicingMessageTypeContext $messageTypeContext;

    private MockObject&Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageStringsExisted = array_key_exists('TL_LANG', $GLOBALS);
        $this->originalLanguageStrings = $GLOBALS['TL_LANG'] ?? null;
        $this->originalLanguage = $GLOBALS['TL_LANGUAGE'] ?? null;
        $GLOBALS['TL_LANGUAGE'] = 'en';
        EInvoicingLanguageFileLoaderStub::reset();
        EInvoicingFallbackLanguageStub::reset();
        EInvoicingConfigStub::$values = ['ls_shop_ownEmailAddress' => 'shop@example.org'];
        $this->messageTypeContext = new EInvoicingMessageTypeContext();
        $this->connection = $this->createMock(Connection::class);
        $this->connection
            ->method('fetchAssociative')
            ->willReturnCallback(static fn (string $query, array $parameters): array|false => $parameters === [self::INVOICE_MESSAGE_TYPE_ID]
                ? ['id' => self::INVOICE_MESSAGE_TYPE_ID, 'title' => 'Rechnung', 'title_de' => 'Rechnung', 'title_en' => 'Invoice']
                : false);
    }

    protected function tearDown(): void
    {
        if ($this->languageStringsExisted) {
            $GLOBALS['TL_LANG'] = $this->originalLanguageStrings;
        } else {
            unset($GLOBALS['TL_LANG']);
        }

        if ($this->originalLanguage === null) {
            unset($GLOBALS['TL_LANGUAGE']);
        } else {
            $GLOBALS['TL_LANGUAGE'] = $this->originalLanguage;
        }

        EInvoicingLanguageFileLoaderStub::reset();
        EInvoicingFallbackLanguageStub::reset();
        EInvoicingConfigStub::$values = [];

        parent::tearDown();
    }

    public function testMerchantNotificationIsWrittenInTheFallbackLanguageWithAllDetails(): void
    {
        $this->messageTypeContext->remember(self::ORDER_ID, self::INVOICE_MESSAGE_TYPE_ID);
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertCount(1, $reporter->sentEmails);
        self::assertSame('shop@example.org', $reporter->sentEmails[0]['recipient']);
        self::assertSame('E-Rechnung für Bestellung 2026112084 konnte nicht erzeugt werden', $reporter->sentEmails[0]['subject']);
        self::assertSame(
            implode("\n", [
                'Die E-Rechnung konnte beim Nachrichtenversand nicht erzeugt werden. Die Nachricht wurde ohne Rechnungsanhang versendet.',
                '',
                'Bestellnummer: 2026112084',
                'Nachrichtentyp: Rechnung (4)',
                'Rechnungsnummer: 2026112019',
                '',
                'Fehlergründe:',
                '[EINVOICING_MISSING_BT_89] Für den Payment Means Code 59 ist die Mandatsreferenz (BT-89) erforderlich.',
                '[EINVOICING_MISSING_BT_91] Für den Payment Means Code 59 ist die IBAN des Käufers (BT-91) erforderlich.',
                '',
                'Bitte korrigieren Sie die Bestell- oder Konfigurationsdaten und erzeugen Sie die E-Rechnung anschließend manuell mit dieser Rechnungsnummer neu: '
                . 'vendor/bin/contao-console merconis:einvoice:export "2026112084" --invoice-number="2026112019"',
            ]),
            $reporter->sentEmails[0]['text']
        );
        self::assertContains(['einvoicing', 'de', true], EInvoicingLanguageFileLoaderStub::$calls);
    }

    public function testMerchantNotificationFollowsAnEnglishFallbackLanguage(): void
    {
        EInvoicingFallbackLanguageStub::$fallbackLanguage = 'en';
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $this->messageTypeContext->remember(self::ORDER_ID, self::INVOICE_MESSAGE_TYPE_ID);
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertSame('E-invoice generation failed for order 2026112084', $reporter->sentEmails[0]['subject']);
        self::assertStringContainsString("\nMessage type: Invoice (4)\n", $reporter->sentEmails[0]['text']);
        self::assertStringContainsString("\nInvoice number: 2026112019\n", $reporter->sentEmails[0]['text']);
        self::assertStringContainsString(
            "\n[EINVOICING_MISSING_BT_89] Payment means code 59 requires the mandate reference (BT-89).\n",
            $reporter->sentEmails[0]['text']
        );
        self::assertNotContains(['einvoicing', 'de', true], EInvoicingLanguageFileLoaderStub::$calls);
    }

    public function testMerchantNotificationWithoutInvoiceNumberAsksForAnInvoiceNumberOnRegeneration(): void
    {
        $this->messageTypeContext->remember(self::ORDER_ID, self::INVOICE_MESSAGE_TYPE_ID);
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure(
            $this->createOrderData(),
            EInvoicingGenerationException::forReason(EInvoicingErrorCode::INVOICE_NUMBER_MISSING),
            null
        );

        self::assertSame(
            implode("\n", [
                'Die E-Rechnung konnte beim Nachrichtenversand nicht erzeugt werden. Die Nachricht wurde ohne Rechnungsanhang versendet.',
                '',
                'Bestellnummer: 2026112084',
                'Nachrichtentyp: Rechnung (4)',
                '',
                'Fehlergründe:',
                '[EINVOICING_INVOICE_NUMBER_MISSING] Es wurde keine Rechnungsnummer übergeben.',
                '',
                'Beim Nachrichtenversand wurde keine Rechnungsnummer vergeben (z. B. weil der Zähler des Nachrichtentyps deaktiviert ist). '
                . 'Bitte korrigieren Sie die Bestell- oder Konfigurationsdaten und erzeugen Sie die E-Rechnung anschließend manuell neu. '
                . 'Dabei ist eine Rechnungsnummer anzugeben: vendor/bin/contao-console merconis:einvoice:export "2026112084" --invoice-number="<Rechnungsnummer>"',
            ]),
            $reporter->sentEmails[0]['text']
        );
    }

    public function testBlankInvoiceNumberIsTreatedAsMissing(): void
    {
        $this->messageTypeContext->remember(self::ORDER_ID, self::INVOICE_MESSAGE_TYPE_ID);
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), '  ');

        self::assertStringNotContainsString('Rechnungsnummer: ', $reporter->sentEmails[0]['text']);
        self::assertStringContainsString('--invoice-number="<Rechnungsnummer>"', $reporter->sentEmails[0]['text']);
    }

    public function testMerchantNotificationContainsAHintWhenNoMessageTypeContextExists(): void
    {
        $this->connection->expects(self::never())->method('fetchAssociative');
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertStringContainsString("\nNachrichtentyp: konnte nicht ermittelt werden\n", $reporter->sentEmails[0]['text']);
        self::assertStringNotContainsString('Rechnung (4)', $reporter->sentEmails[0]['text']);
        self::assertStringContainsString('Message type: could not be determined.', $this->loggedErrors[0]);
    }

    public function testContextOfAnEarlierOrderDoesNotReachTheNotification(): void
    {
        $this->messageTypeContext->remember(83, self::INVOICE_MESSAGE_TYPE_ID);
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertStringContainsString("\nNachrichtentyp: konnte nicht ermittelt werden\n", $reporter->sentEmails[0]['text']);
        self::assertStringContainsString('Message type: could not be determined.', $this->loggedErrors[0]);
    }

    public function testUnknownMessageTypeRecordIsReportedAsNotDeterminable(): void
    {
        $this->messageTypeContext->remember(self::ORDER_ID, 999);
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertStringContainsString("\nNachrichtentyp: konnte nicht ermittelt werden\n", $reporter->sentEmails[0]['text']);
    }

    public function testMessageTypeContextStoredByTheHookArrivesAtTheReporter(): void
    {
        $hook = new EInvoicingOrderMessageHook($this->messageTypeContext);
        $hook->beforeSendingOrderMessage(
            ['pid' => (string) self::INVOICE_MESSAGE_TYPE_ID, 'messageLanguage' => 'de'],
            ['id' => (string) self::ORDER_ID, 'paymentMethod' => [], 'memberGroup' => []]
        );
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertStringContainsString("\nNachrichtentyp: Rechnung (4)\n", $reporter->sentEmails[0]['text']);
        self::assertStringContainsString('Message type: Rechnung (4).', $this->loggedErrors[0]);
    }

    public function testGlobalLanguageIsUnchangedAfterTheNotification(): void
    {
        $GLOBALS['TL_LANG'] = ['MSC' => ['textDirection' => 'ltr', 'marker' => 'before']];
        $languageStringsBefore = $GLOBALS['TL_LANG'];
        $this->messageTypeContext->remember(self::ORDER_ID, self::INVOICE_MESSAGE_TYPE_ID);
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertCount(1, $reporter->sentEmails);
        self::assertSame($languageStringsBefore, $GLOBALS['TL_LANG']);
        self::assertSame('en', $GLOBALS['TL_LANGUAGE']);
    }

    public function testMessageDispatchLogEntryUsesEnglishTextsAndTheMessageTypeInTheFallbackLanguage(): void
    {
        $this->messageTypeContext->remember(self::ORDER_ID, self::INVOICE_MESSAGE_TYPE_ID);
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertSame(
            [
                'MERCONIS E-INVOICING: Failed to generate the e-invoice for order "2026112084". Entry point: message dispatch. '
                . 'Message type: Rechnung (4). Failure reasons: '
                . '[EINVOICING_MISSING_BT_89] Payment means code 59 requires the mandate reference (BT-89).'
                . ' | [EINVOICING_MISSING_BT_91] Payment means code 59 requires the buyer IBAN (BT-91).',
            ],
            $this->loggedErrors
        );
    }

    public function testManualGenerationOnlyWritesTheLogEntryWithoutMessageType(): void
    {
        $this->messageTypeContext->remember(self::ORDER_ID, self::INVOICE_MESSAGE_TYPE_ID);
        $this->connection->expects(self::never())->method('fetchAssociative');
        $reporter = $this->createReporter();

        $reporter->reportManualGenerationFailure(self::ORDER_NUMBER, $this->createSepaValidationException());

        self::assertSame([], $reporter->sentEmails);
        self::assertSame(
            [
                'MERCONIS E-INVOICING: Failed to generate the e-invoice for order "2026112084". Entry point: manual generation. Failure reasons: '
                . '[EINVOICING_MISSING_BT_89] Payment means code 59 requires the mandate reference (BT-89).'
                . ' | [EINVOICING_MISSING_BT_91] Payment means code 59 requires the buyer IBAN (BT-91).',
            ],
            $this->loggedErrors
        );
        self::assertSame([['einvoicing', 'en', true]], EInvoicingLanguageFileLoaderStub::$calls);
    }

    public function testManualGenerationWithBlankOrderNumberIsLoggedAsNotAvailable(): void
    {
        $reporter = $this->createReporter();

        $reporter->reportManualGenerationFailure(' ', EInvoicingGenerationException::forReason(EInvoicingErrorCode::ORDER_NUMBER_MISSING));

        self::assertStringStartsWith('MERCONIS E-INVOICING: Failed to generate the e-invoice for order "n/a". Entry point: manual generation.', $this->loggedErrors[0]);
    }

    public function testOrderIdIsUsedAsOrderNumberWhenTheOrderNumberIsMissing(): void
    {
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure(['id' => '84'], $this->createSepaValidationException(), null);

        self::assertStringContainsString('for order "84".', $this->loggedErrors[0]);
        self::assertStringContainsString("\nBestellnummer: 84\n", $reporter->sentEmails[0]['text']);
    }

    public function testThrowableWithoutMessageIsReportedAsRuntimeErrorWithoutEmptyParameter(): void
    {
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure(['orderNr' => '2026102690'], new \RuntimeException(''), null);

        self::assertStringEndsWith('Failure reasons: ' . self::RUNTIME_ERROR_WITHOUT_MESSAGE, $this->loggedErrors[0]);
        self::assertStringContainsString(
            "\n[EINVOICING_RUNTIME_ERROR] Bei der Erzeugung der E-Rechnung ist ein unerwarteter Fehler aufgetreten.\n",
            $reporter->sentEmails[0]['text']
        );
        self::assertStringNotContainsString('{message}', $reporter->sentEmails[0]['text']);
        self::assertStringNotContainsString('aufgetreten: ', $reporter->sentEmails[0]['text']);
        self::assertStringNotContainsString('generation: ', $this->loggedErrors[0]);
    }

    public function testForeignThrowableIsReportedAsRuntimeErrorWithOriginalMessage(): void
    {
        $reporter = $this->createReporter();

        self::assertSame(
            ['[EINVOICING_RUNTIME_ERROR] An unexpected error occurred during e-invoice generation: Library failure'],
            $reporter->formatFailureReasons(new \RuntimeException('Library failure'))
        );
    }

    public function testFormatsFailureReasonsInEnglishIndependentOfTheCurrentLanguage(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $reporter = $this->createReporter();

        $formattedFailureReasons = $reporter->formatFailureReasons($this->createSepaValidationException());

        self::assertSame('de', $GLOBALS['TL_LANGUAGE']);
        self::assertSame([['einvoicing', 'en', true]], EInvoicingLanguageFileLoaderStub::$calls);
        self::assertSame(
            '[EINVOICING_MISSING_BT_89] Payment means code 59 requires the mandate reference (BT-89).',
            $formattedFailureReasons[0]
        );
    }

    public function testMissingShopEmailAddressSkipsTheNotificationAndLogsIt(): void
    {
        EInvoicingConfigStub::$values = ['ls_shop_ownEmailAddress' => ''];
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertSame([], $reporter->sentEmails);
        self::assertCount(2, $this->loggedErrors);
        self::assertSame(
            'MERCONIS E-INVOICING: Merchant notification could not be sent because no valid shop email address is configured.',
            $this->loggedErrors[1]
        );
    }

    public function testInvalidShopEmailAddressSkipsTheNotificationAndLogsIt(): void
    {
        EInvoicingConfigStub::$values = ['ls_shop_ownEmailAddress' => 'no-email-address'];
        $reporter = $this->createReporter();

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertSame([], $reporter->sentEmails);
        self::assertCount(2, $this->loggedErrors);
    }

    public function testFailedMerchantNotificationIsLoggedWithFormattedReason(): void
    {
        $reporter = $this->createReporter();
        $reporter->emailSendingFailure = new \RuntimeException('Mail transport unavailable');

        $reporter->reportMessageDispatchFailure($this->createOrderData(), $this->createSepaValidationException(), self::INVOICE_NUMBER);

        self::assertSame([], $reporter->sentEmails);
        self::assertCount(2, $this->loggedErrors);
        self::assertSame(
            'MERCONIS E-INVOICING: Merchant notification for order "2026112084" could not be sent. '
            . '[EINVOICING_RUNTIME_ERROR] An unexpected error occurred during e-invoice generation: Mail transport unavailable',
            $this->loggedErrors[1]
        );
    }

    /**
     * @return array<string, string>
     */
    private function createOrderData(): array
    {
        return ['id' => (string) self::ORDER_ID, 'orderNr' => self::ORDER_NUMBER];
    }

    private function createSepaValidationException(): InvoiceValidationException
    {
        return new InvoiceValidationException([
            new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_89, ['paymentMeansCode' => '59']),
            new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_91, ['paymentMeansCode' => '59']),
        ]);
    }

    private function createReporter(): EInvoicingRecordingFailureReporter
    {
        $framework = EInvoicingFailureTestSupport::configureFrameworkAdapters($this->createMock(ContaoFramework::class));
        $loggedErrors = &$this->loggedErrors;

        $logger = new class($loggedErrors) extends AbstractLogger {
            /**
             * @param list<string> $loggedErrors
             */
            public function __construct(private array &$loggedErrors)
            {
            }

            public function log($level, $message, array $context = []): void
            {
                $this->loggedErrors[] = (string) $message;
            }
        };

        return new EInvoicingRecordingFailureReporter(
            $framework,
            $logger,
            new EInvoicingFailureReasonFormatter($framework),
            $this->messageTypeContext,
            $this->connection
        );
    }
}
