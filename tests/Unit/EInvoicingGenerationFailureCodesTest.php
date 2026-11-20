<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\EInvoicingGenerationException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Model\GeneratedZugferdDocument;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingDocumentWriter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\OrderEInvoicingDocumentGenerator;
use LeadingSystems\MerconisBundle\EInvoicing\Service\SemanticInvoiceBuilder;
use LeadingSystems\MerconisBundle\EInvoicing\Service\VisibleInvoiceHtmlRenderer;
use LeadingSystems\MerconisBundle\EInvoicing\Service\ZugferdDocumentGenerator;
use LeadingSystems\MerconisBundle\EInvoicing\Service\ZugferdHybridPdfGenerator;
use LeadingSystems\MerconisBundle\EInvoicing\Service\ZugferdXmlGenerator;
use PHPUnit\Framework\TestCase;

final class EInvoicingGenerationFailureCodesTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectDir = sys_get_temp_dir() . '/merconis-einvoice-failure-codes-' . bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);

        parent::tearDown();
    }

    public function testEmptyOrderNumberIsReportedWithCode(): void
    {
        $generationException = $this->captureGenerationException(
            fn () => $this->createDocumentGenerator($this->createMock(Connection::class))->generateForOrderNumber('   ')
        );

        $this->assertSingleReason($generationException, EInvoicingErrorCode::ORDER_NUMBER_MISSING, []);
    }

    public function testUnknownOrderNumberIsReportedWithCodeAndOrderNumber(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(false);

        $generationException = $this->captureGenerationException(
            fn () => $this->createDocumentGenerator($connection)->generateForOrderNumber(' 2026-404 ')
        );

        $this->assertSingleReason($generationException, EInvoicingErrorCode::ORDER_NOT_FOUND, ['orderNumber' => '2026-404']);
    }

    public function testEmptyInvoiceNumberIsReportedWithCodeOnBothEntryMethods(): void
    {
        $documentGenerator = $this->createDocumentGenerator($this->createMock(Connection::class));

        $this->assertSingleReason(
            $this->captureGenerationException(fn () => $documentGenerator->generateForOrderId(5, ' ')),
            EInvoicingErrorCode::INVOICE_NUMBER_MISSING,
            []
        );
        $this->assertSingleReason(
            $this->captureGenerationException(fn () => $documentGenerator->buildSemanticInvoiceForOrderId(5, '')),
            EInvoicingErrorCode::INVOICE_NUMBER_MISSING,
            []
        );
    }

    public function testEmptyTargetDirectoryIsReportedWithCode(): void
    {
        $generationException = $this->captureGenerationException(
            fn () => (new EInvoicingDocumentWriter($this->projectDir))->writePdf($this->createDocument(), '  ')
        );

        $this->assertSingleReason($generationException, EInvoicingErrorCode::OUTPUT_DIRECTORY_MISSING, []);
    }

    public function testUncreatableTargetDirectoryIsReportedWithCodeAndDirectory(): void
    {
        file_put_contents($this->projectDir . '/blocking-file', 'not a directory');

        $generationException = $this->captureGenerationException(
            fn () => $this->withSuppressedWarnings(
                fn () => (new EInvoicingDocumentWriter($this->projectDir))->writePdf($this->createDocument(), 'blocking-file/export')
            )
        );

        $this->assertSingleReason(
            $generationException,
            EInvoicingErrorCode::OUTPUT_DIRECTORY_NOT_CREATABLE,
            ['directory' => $this->projectDir . '/blocking-file/export']
        );
    }

    public function testUnwritableFileIsReportedWithCodeAndFile(): void
    {
        mkdir($this->projectDir . '/export/invoice-RE-1-zugferd.pdf', 0775, true);

        $generationException = $this->captureGenerationException(
            fn () => $this->withSuppressedWarnings(
                fn () => (new EInvoicingDocumentWriter($this->projectDir))->writePdf($this->createDocument(), 'export', false)
            )
        );

        $this->assertSingleReason(
            $generationException,
            EInvoicingErrorCode::OUTPUT_FILE_NOT_WRITABLE,
            ['file' => $this->projectDir . '/export/invoice-RE-1-zugferd.pdf']
        );
    }

    /**
     * @param array<string, string> $expectedParameters
     */
    private function assertSingleReason(
        EInvoicingGenerationException $generationException,
        EInvoicingErrorCode $expectedCode,
        array $expectedParameters,
    ): void {
        self::assertCount(1, $generationException->failureReasons);
        self::assertSame($expectedCode, $generationException->failureReasons[0]->code);
        self::assertSame($expectedParameters, $generationException->failureReasons[0]->parameters);
        self::assertSame($expectedCode->value, $generationException->getMessage());
    }

    private function captureGenerationException(callable $operation): EInvoicingGenerationException
    {
        try {
            $operation();
        } catch (EInvoicingGenerationException $generationException) {
            return $generationException;
        }

        self::fail('Expected an EInvoicingGenerationException.');
    }

    private function withSuppressedWarnings(callable $operation): mixed
    {
        set_error_handler(static fn (): bool => true, E_WARNING);

        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }

    private function createDocumentGenerator(Connection $connection): OrderEInvoicingDocumentGenerator
    {
        return new OrderEInvoicingDocumentGenerator(
            $this->createMock(ContaoFramework::class),
            $connection,
            new SemanticInvoiceBuilder(),
            new ZugferdDocumentGenerator(
                new ZugferdXmlGenerator(),
                new VisibleInvoiceHtmlRenderer(),
                new ZugferdHybridPdfGenerator($this->projectDir)
            )
        );
    }

    private function createDocument(): GeneratedZugferdDocument
    {
        return new GeneratedZugferdDocument('invoice-RE-1-zugferd.pdf', '<xml/>', '%PDF-test');
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
