<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

use LeadingSystems\MerconisBundle\EInvoicing\Model\SemanticInvoice;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

final class ZugferdHybridPdfGenerator
{
    private const ATTACHMENT_FILE_NAME = 'factur-x.xml';
    private const ATTACHMENT_MIME_TYPE = 'text/xml';
    private const ATTACHMENT_DESCRIPTION = 'Structured invoice data';
    private const ATTACHMENT_RELATIONSHIP = 'Alternative';
    private const FACTUR_X_NAMESPACE = 'urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#';
    private const FACTUR_X_VERSION = '1.0';
    private const FACTUR_X_CONFORMANCE_LEVEL = 'EN 16931';

    public function generate(SemanticInvoice $invoice, string $visibleHtml, string $xmlContent): string
    {
        $mpdf = new Mpdf([
            'tempDir' => $this->resolveTempDir(),
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->PDFA = true;
        $mpdf->PDFAauto = true;
        $mpdf->PDFAversion = '3-B';

        $mpdf->SetTitle('Invoice ' . $invoice->invoiceNumber);
        $mpdf->SetAuthor($invoice->seller['name']);
        $mpdf->SetSubject('ZUGFeRD invoice ' . $invoice->invoiceNumber);
        $mpdf->SetCreator('Merconis E-Invoicing');
        $mpdf->SetAssociatedFiles([
            [
                'name' => self::ATTACHMENT_FILE_NAME,
                'mime' => self::ATTACHMENT_MIME_TYPE,
                'description' => self::ATTACHMENT_DESCRIPTION,
                'AFRelationship' => self::ATTACHMENT_RELATIONSHIP,
                'content' => $xmlContent,
            ],
        ]);
        $mpdf->SetAdditionalXmpRdf($this->buildAdditionalXmpRdf());
        $mpdf->WriteHTML($visibleHtml, HTMLParserMode::DEFAULT_MODE);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function resolveTempDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/merconis-einvoicing-mpdf';

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        return $tempDir;
    }

    private function buildAdditionalXmpRdf(): string
    {
        return '<rdf:Description xmlns:pdfaExtension="http://www.aiim.org/pdfa/ns/extension/" '
            . 'xmlns:pdfaSchema="http://www.aiim.org/pdfa/ns/schema#" '
            . 'xmlns:pdfaProperty="http://www.aiim.org/pdfa/ns/property#" rdf:about="">'
            . '<pdfaExtension:schemas>'
            . '<rdf:Bag>'
            . '<rdf:li rdf:parseType="Resource">'
            . '<pdfaSchema:schema>Factur-X PDFA Extension Schema</pdfaSchema:schema>'
            . '<pdfaSchema:namespaceURI>' . self::FACTUR_X_NAMESPACE . '</pdfaSchema:namespaceURI>'
            . '<pdfaSchema:prefix>fx</pdfaSchema:prefix>'
            . '<pdfaSchema:property>'
            . '<rdf:Seq>'
            . '<rdf:li rdf:parseType="Resource">'
            . '<pdfaProperty:name>DocumentFileName</pdfaProperty:name>'
            . '<pdfaProperty:valueType>Text</pdfaProperty:valueType>'
            . '<pdfaProperty:category>external</pdfaProperty:category>'
            . '<pdfaProperty:description>The name of the embedded XML document</pdfaProperty:description>'
            . '</rdf:li>'
            . '<rdf:li rdf:parseType="Resource">'
            . '<pdfaProperty:name>DocumentType</pdfaProperty:name>'
            . '<pdfaProperty:valueType>Text</pdfaProperty:valueType>'
            . '<pdfaProperty:category>external</pdfaProperty:category>'
            . '<pdfaProperty:description>The type of the hybrid document in capital letters, e.g. INVOICE or ORDER</pdfaProperty:description>'
            . '</rdf:li>'
            . '<rdf:li rdf:parseType="Resource">'
            . '<pdfaProperty:name>Version</pdfaProperty:name>'
            . '<pdfaProperty:valueType>Text</pdfaProperty:valueType>'
            . '<pdfaProperty:category>external</pdfaProperty:category>'
            . '<pdfaProperty:description>The actual version of the standard applying to the embedded XML document</pdfaProperty:description>'
            . '</rdf:li>'
            . '<rdf:li rdf:parseType="Resource">'
            . '<pdfaProperty:name>ConformanceLevel</pdfaProperty:name>'
            . '<pdfaProperty:valueType>Text</pdfaProperty:valueType>'
            . '<pdfaProperty:category>external</pdfaProperty:category>'
            . '<pdfaProperty:description>The conformance level of the embedded XML document</pdfaProperty:description>'
            . '</rdf:li>'
            . '</rdf:Seq>'
            . '</pdfaSchema:property>'
            . '</rdf:li>'
            . '</rdf:Bag>'
            . '</pdfaExtension:schemas>'
            . '</rdf:Description>'
            . '<rdf:Description xmlns:fx="' . self::FACTUR_X_NAMESPACE . '" rdf:about="">'
            . '<fx:DocumentType>INVOICE</fx:DocumentType>'
            . '<fx:DocumentFileName>' . self::ATTACHMENT_FILE_NAME . '</fx:DocumentFileName>'
            . '<fx:Version>' . self::FACTUR_X_VERSION . '</fx:Version>'
            . '<fx:ConformanceLevel>' . self::FACTUR_X_CONFORMANCE_LEVEL . '</fx:ConformanceLevel>'
            . '</rdf:Description>';
    }
}
