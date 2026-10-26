<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Command\EInvoiceExportCommand;
use LeadingSystems\MerconisBundle\EInvoicing\Model\GeneratedZugferdDocument;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingDocumentWriter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingFailureReporter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\OrderEInvoicingDocumentGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class EInvoiceExportCommandTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectDir = sys_get_temp_dir() . '/merconis-einvoice-command-' . bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);

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
        $failureReporter->expects(self::never())->method('formatFailureReason');
        $failureReporter->expects(self::never())->method('reportFailure');

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

    public function testReturnsFailureWhenGenerationThrows(): void
    {
        $documentGenerator = $this->createMock(OrderEInvoicingDocumentGenerator::class);
        $exception = new \RuntimeException('Order "ORDER-404" was not found.');
        $documentGenerator
            ->expects(self::once())
            ->method('generateForOrderNumber')
            ->with('ORDER-404', 'INV-404')
            ->willThrowException($exception);

        $failureReporter = $this->createMock(EInvoicingFailureReporter::class);
        $failureReporter
            ->expects(self::once())
            ->method('formatFailureReason')
            ->with($exception)
            ->willReturn('Order "ORDER-404" was not found.');
        $failureReporter
            ->expects(self::once())
            ->method('reportFailure')
            ->with('ORDER-404', $exception, 'console command');

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

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Export failed for order "ORDER-404".', $commandTester->getDisplay());
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
