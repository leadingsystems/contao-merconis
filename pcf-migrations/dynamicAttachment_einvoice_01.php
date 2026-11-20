<?php
declare(strict_types=1);

namespace Merconis\Core;

use Contao\Controller;
use Contao\System;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\EInvoicingGenerationException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReason;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingDocumentWriter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingFailureReporter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\OrderEInvoicingDocumentGenerator;

class dynamicAttachment_einvoice_01 extends Controller
{
    private const OUTPUT_DIRECTORY = 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles';

    /**
     * @param array<string, mixed> $arrOrder
     * @param array<int|string, mixed> $flex_parameters
     */
    public function __construct(
        private array $arrOrder = [],
        private mixed $counterNr = null,
        private array $flex_parameters = [],
    ) {
        parent::__construct();
    }

    public function parse(): ?string
    {
        $orderId = (int) ($this->arrOrder['id'] ?? 0);
        $invoiceNumber = is_scalar($this->counterNr) ? trim((string) $this->counterNr) : '';
        $assignedInvoiceNumber = $invoiceNumber !== '' ? $invoiceNumber : null;
        $missingInputReasons = [];

        if ($orderId <= 0) {
            $missingInputReasons[] = new EInvoicingFailureReason(EInvoicingErrorCode::ORDER_ID_MISSING);
        }

        if ($assignedInvoiceNumber === null) {
            $missingInputReasons[] = new EInvoicingFailureReason(EInvoicingErrorCode::INVOICE_NUMBER_MISSING);
        }

        if ($missingInputReasons !== []) {
            $this->reportFailure(new EInvoicingGenerationException($missingInputReasons), $assignedInvoiceNumber);

            return null;
        }

        try {
            $container = System::getContainer();
            /** @var OrderEInvoicingDocumentGenerator $documentGenerator */
            $documentGenerator = $container->get(OrderEInvoicingDocumentGenerator::class);
            /** @var EInvoicingDocumentWriter $documentWriter */
            $documentWriter = $container->get(EInvoicingDocumentWriter::class);

            $generatedDocument = $documentGenerator->generateForOrderId($orderId, $invoiceNumber);

            return $documentWriter->writePdf($generatedDocument, self::OUTPUT_DIRECTORY, true);
        } catch (\Throwable $throwable) {
            $this->reportFailure($throwable, $assignedInvoiceNumber);

            return null;
        }
    }

    private function reportFailure(\Throwable $throwable, ?string $assignedInvoiceNumber): void
    {
        /** @var EInvoicingFailureReporter $failureReporter */
        $failureReporter = System::getContainer()->get(EInvoicingFailureReporter::class);
        $failureReporter->reportMessageDispatchFailure($this->arrOrder, $throwable, $assignedInvoiceNumber);
    }
}
