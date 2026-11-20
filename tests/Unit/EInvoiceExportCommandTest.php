<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../Support/EInvoicingFailureTestSupport.php';

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\Command\EInvoiceExportCommand;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\EInvoicingGenerationException;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReason;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReasonFormatter;
use LeadingSystems\MerconisBundle\EInvoicing\Model\GeneratedZugferdDocument;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingDocumentWriter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingFailureReporter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingMessageTypeContext;
use LeadingSystems\MerconisBundle\EInvoicing\Service\OrderEInvoicingDocumentGenerator;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingConfigStub;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingFailureTestSupport;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingLanguageFileLoaderStub;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingRecordingFailureReporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

final class EInvoiceExportCommandTest extends TestCase
{
    private string $projectDir;

    private bool $languageStringsExisted = false;

    private mixed $originalLanguageStrings = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectDir = sys_get_temp_dir() . '/merconis-einvoice-command-' . bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0775, true);

        $this->languageStringsExisted = array_key_exists('TL_LANG', $GLOBALS);
        $this->originalLanguageStrings = $GLOBALS['TL_LANG'] ?? null;
        EInvoicingLanguageFileLoaderStub::reset();
        EInvoicingConfigStub::$values = ['ls_shop_ownEmailAddress' => 'shop@example.org'];
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);

        if ($this->languageStringsExisted) {
            $GLOBALS['TL_LANG'] = $this->originalLanguageStrings;
        } else {
            unset($GLOBALS['TL_LANG']);
        }

        EInvoicingLanguageFileLoaderStub::reset();
        EInvoicingConfigStub::$values = [];

        parent::tearDown();
    }

    public function testExportsXmlAndPdfUsingOrderNumberFallback(): void
    {
        $documentGenerator = $this->createMock(OrderEInvoicingDocumentGenerator::class);
        $documentGenerator
            ->expects(self::once())
            ->method('generateForOrderNumber')
            ->with('ORDER-1001', null)
            ->willReturn(new GeneratedZugferdDocument(
                'invoice-ORDER-1001-zugferd.pdf',
                '<xml>payload</xml>',
                '%PDF-test'
            ));

        $failureReporter = $this->createMock(EInvoicingFailureReporter::class);
        $failureReporter->expects(self::never())->method('formatFailureReasons');
        $failureReporter->expects(self::never())->method('reportManualGenerationFailure');
        $failureReporter->expects(self::never())->method('reportMessageDispatchFailure');

        $command = new EInvoiceExportCommand(
            $documentGenerator,
            new EInvoicingDocumentWriter($this->projectDir),
            $failureReporter
        );

        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            'order-number' => 'ORDER-1001',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertFileExists($this->projectDir . '/files/einvoice-export/invoice-ORDER-1001-zugferd.xml');
        self::assertFileExists($this->projectDir . '/files/einvoice-export/invoice-ORDER-1001-zugferd.pdf');
        self::assertSame(
            '<xml>payload</xml>',
            (string) file_get_contents($this->projectDir . '/files/einvoice-export/invoice-ORDER-1001-zugferd.xml')
        );
        self::assertSame(
            '%PDF-test',
            (string) file_get_contents($this->projectDir . '/files/einvoice-export/invoice-ORDER-1001-zugferd.pdf')
        );
        self::assertStringContainsString('Invoice number: ORDER-1001', $commandTester->getDisplay());
    }

    public function testPrintsEverySimultaneousValidationReasonOnItsOwnLineWithCode(): void
    {
        [$exitCode, $displayLines, $failureReporter, $loggedErrors] = $this->executeWithGenerationFailure(
            new InvoiceValidationException([
                new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_89, ['paymentMeansCode' => '59']),
                new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_91, ['paymentMeansCode' => '59']),
            ])
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame(
            [
                'Export failed for order "ORDER-404".',
                '[EINVOICING_MISSING_BT_89] Payment means code 59 requires the mandate reference (BT-89).',
                '[EINVOICING_MISSING_BT_91] Payment means code 59 requires the buyer IBAN (BT-91).',
            ],
            $displayLines
        );
        self::assertSame([], $failureReporter->sentEmails);
        self::assertSame(
            [
                'MERCONIS E-INVOICING: Failed to generate the e-invoice for order "ORDER-404". Entry point: manual generation. Failure reasons: '
                . '[EINVOICING_MISSING_BT_89] Payment means code 59 requires the mandate reference (BT-89).'
                . ' | [EINVOICING_MISSING_BT_91] Payment means code 59 requires the buyer IBAN (BT-91).',
            ],
            $loggedErrors
        );
    }

    public function testFailureNeverNotifiesTheMerchantEvenIfAMessageTypeContextExists(): void
    {
        [$exitCode, , $failureReporter, $loggedErrors] = $this->executeWithGenerationFailure(
            EInvoicingGenerationException::forReason(EInvoicingErrorCode::ORDER_NOT_FOUND, ['orderNumber' => 'ORDER-404']),
            404
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame([], $failureReporter->sentEmails);
        self::assertCount(1, $loggedErrors);
        self::assertStringNotContainsString('Message type', $loggedErrors[0]);
    }

    public function testInvoiceNumberOptionDescribesRegenerationAndTheDevelopmentDefault(): void
    {
        $command = new EInvoiceExportCommand(
            $this->createMock(OrderEInvoicingDocumentGenerator::class),
            new EInvoicingDocumentWriter($this->projectDir),
            $this->createMock(EInvoicingFailureReporter::class)
        );

        self::assertSame(
            'Invoice number to embed into the document. To regenerate an e-invoice that failed during message dispatch, '
            . 'pass the invoice number assigned there. Defaults to the order number, which is intended for development and evaluation only.',
            $command->getDefinition()->getOption('invoice-number')->getDescription()
        );
    }

    public function testPrintsOwnGenerationErrorWithCodeAndParameter(): void
    {
        [$exitCode, $displayLines] = $this->executeWithGenerationFailure(
            EInvoicingGenerationException::forReason(EInvoicingErrorCode::ORDER_NOT_FOUND, ['orderNumber' => 'ORDER-404'])
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame(
            [
                'Export failed for order "ORDER-404".',
                '[EINVOICING_ORDER_NOT_FOUND] No order with the order number "ORDER-404" was found.',
            ],
            $displayLines
        );
    }

    public function testPrintsForeignErrorAsRuntimeErrorWithLiteralOriginalMessage(): void
    {
        [$exitCode, $displayLines] = $this->executeWithGenerationFailure(
            new \RuntimeException('Library failure in <ram:ID>')
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame(
            [
                'Export failed for order "ORDER-404".',
                '[EINVOICING_RUNTIME_ERROR] An unexpected error occurred during e-invoice generation: Library failure in <ram:ID>',
            ],
            $displayLines
        );
    }

    public function testPrintsForeignErrorWithoutMessageWithoutEmptyParameter(): void
    {
        [$exitCode, $displayLines] = $this->executeWithGenerationFailure(new \RuntimeException(''));

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame(
            [
                'Export failed for order "ORDER-404".',
                '[EINVOICING_RUNTIME_ERROR] An unexpected error occurred during e-invoice generation.',
            ],
            $displayLines
        );
    }

    /**
     * @return array{0: int, 1: list<string>, 2: EInvoicingRecordingFailureReporter, 3: list<string>}
     */
    private function executeWithGenerationFailure(Throwable $generationFailure, ?int $contextOrderId = null): array
    {
        $documentGenerator = $this->createMock(OrderEInvoicingDocumentGenerator::class);
        $documentGenerator
            ->expects(self::once())
            ->method('generateForOrderNumber')
            ->with('ORDER-404', 'INV-404')
            ->willThrowException($generationFailure);

        $framework = EInvoicingFailureTestSupport::configureFrameworkAdapters($this->createMock(ContaoFramework::class));
        $loggedErrors = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->method('error')
            ->willReturnCallback(static function (string $message) use (&$loggedErrors): void {
                $loggedErrors[] = $message;
            });

        $messageTypeContext = new EInvoicingMessageTypeContext();

        if ($contextOrderId !== null) {
            $messageTypeContext->remember($contextOrderId, 4);
        }

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('fetchAssociative');

        $failureReporter = new EInvoicingRecordingFailureReporter(
            $framework,
            $logger,
            new EInvoicingFailureReasonFormatter($framework),
            $messageTypeContext,
            $connection
        );

        $command = new EInvoiceExportCommand(
            $documentGenerator,
            new EInvoicingDocumentWriter($this->projectDir),
            $failureReporter
        );

        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            'order-number' => 'ORDER-404',
            '--invoice-number' => 'INV-404',
        ]);

        $displayLines = array_values(array_filter(
            array_map('rtrim', explode("\n", $commandTester->getDisplay())),
            static fn (string $displayLine): bool => $displayLine !== ''
        ));

        return [$exitCode, $displayLines, $failureReporter, $loggedErrors];
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($path);
    }
}
