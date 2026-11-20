<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Command;

use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingDocumentWriter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingFailureReporter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\OrderEInvoicingDocumentGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class EInvoiceExportCommand extends Command
{
    private const DEFAULT_OUTPUT_DIRECTORY = 'files/einvoice-export';

    protected static $defaultName = 'merconis:einvoice:export';

    public function __construct(
        private readonly OrderEInvoicingDocumentGenerator $documentGenerator,
        private readonly EInvoicingDocumentWriter $documentWriter,
        private readonly EInvoicingFailureReporter $failureReporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Exports a ZUGFeRD e-invoice for a Merconis order.')
            ->addArgument(
                'order-number',
                InputArgument::REQUIRED,
                'Backend-visible order number.'
            )
            ->addOption(
                'invoice-number',
                null,
                InputOption::VALUE_REQUIRED,
                'Invoice number to embed into the document. To regenerate an e-invoice that failed during message dispatch, pass the invoice number assigned there. Defaults to the order number, which is intended for development and evaluation only.'
            )
            ->addOption(
                'output-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Target directory for the generated XML and PDF files.',
                self::DEFAULT_OUTPUT_DIRECTORY
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orderNumber = trim((string) $input->getArgument('order-number'));
        $invoiceNumber = $input->getOption('invoice-number');
        $outputDirectory = trim((string) $input->getOption('output-dir'));

        try {
            $generatedDocument = $this->documentGenerator->generateForOrderNumber(
                $orderNumber,
                is_string($invoiceNumber) && trim($invoiceNumber) !== '' ? trim($invoiceNumber) : null
            );

            $xmlPath = $this->documentWriter->writeXml($generatedDocument, $outputDirectory);
            $pdfPath = $this->documentWriter->writePdf($generatedDocument, $outputDirectory);
        } catch (Throwable $throwable) {
            $this->failureReporter->reportManualGenerationFailure($orderNumber, $throwable);

            $output->writeln(sprintf(
                '<error>Export failed for order "%s".</error>',
                OutputFormatter::escape($orderNumber)
            ));

            foreach ($this->failureReporter->formatFailureReasons($throwable) as $formattedFailureReason) {
                $output->writeln('<error>' . OutputFormatter::escape($formattedFailureReason) . '</error>');
            }

            return Command::FAILURE;
        }

        $effectiveInvoiceNumber = is_string($invoiceNumber) && trim($invoiceNumber) !== ''
            ? trim($invoiceNumber)
            : $orderNumber;

        $output->writeln('Export completed successfully.');
        $output->writeln('Order number: ' . $orderNumber);
        $output->writeln('Invoice number: ' . $effectiveInvoiceNumber);
        $output->writeln('XML: ' . $xmlPath);
        $output->writeln('PDF: ' . $pdfPath);

        return Command::SUCCESS;
    }
}
