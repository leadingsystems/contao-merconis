<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

use LeadingSystems\MerconisBundle\EInvoicing\Model\GeneratedZugferdDocument;
use LeadingSystems\MerconisBundle\EInvoicing\Model\SemanticInvoice;

final class ZugferdDocumentGenerator
{
    public function __construct(
        private readonly ZugferdXmlGenerator $xmlGenerator,
        private readonly VisibleInvoiceHtmlRenderer $htmlRenderer,
        private readonly ZugferdHybridPdfGenerator $hybridPdfGenerator,
    ) {
    }

    public function generate(SemanticInvoice $invoice, ?string $visibleHtml = null): GeneratedZugferdDocument
    {
        $visibleHtml ??= $this->htmlRenderer->render($invoice);
        $xmlContent = $this->xmlGenerator->generate($invoice);
        $pdfContent = $this->hybridPdfGenerator->generate($invoice, $visibleHtml, $xmlContent);

        return new GeneratedZugferdDocument(
            $this->buildFileName($invoice->invoiceNumber),
            $xmlContent,
            $pdfContent,
        );
    }

    private function buildFileName(string $invoiceNumber): string
    {
        $sanitizedInvoiceNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoiceNumber) ?? $invoiceNumber;
        $sanitizedInvoiceNumber = trim($sanitizedInvoiceNumber, '-');

        return 'invoice-' . $sanitizedInvoiceNumber . '-zugferd.pdf';
    }
}
