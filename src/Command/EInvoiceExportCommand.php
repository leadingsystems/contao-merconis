<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Command;

use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingDocumentWriter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\OrderEInvoicingDocumentGenerator;
use Symfony\Component\Console\Command\Command;
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
                'Invoice number to embed into the document. Defaults to the order number.'
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
            $output->writeln(sprintf(
                '<error>Export failed for order "%s". %s</error>',
                $orderNumber,
                $this->formatFailureReason($throwable)
            ));

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

    private function formatFailureReason(Throwable $throwable): string
    {
        if ($throwable instanceof InvoiceValidationException) {
            return 'The order data is incomplete for EN 16931 export.';
        }

        $message = trim($throwable->getMessage());

        if ($message === '') {
            return 'An unspecified error occurred.';
        }

        return $message;
    }
}
