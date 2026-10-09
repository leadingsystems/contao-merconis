<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use DOMDocument;
use DOMElement;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;

final class MpdfGaranPdfAttachmentWriter implements GaranPdfAttachmentWriterInterface
{
    private const RELATIVE_OUTPUT_DIRECTORY = 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels';
    private const RELATIVE_MPDF_TEMP_DIRECTORY = 'var/tmp/merconis/mpdf';
    private const PDF_PAGE_MARGIN_MM = 15;
    private const PDF_LABEL_WIDTH_MM = 180;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function write(string $svgMarkup, string $preferredBaseName): array
    {
        $outputDirectory = $this->projectDir . '/' . self::RELATIVE_OUTPUT_DIRECTORY;
        $mpdfTempDirectory = $this->projectDir . '/' . self::RELATIVE_MPDF_TEMP_DIRECTORY;

        if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
            throw new RuntimeException(sprintf('Could not create GARAN output directory: %s', $outputDirectory));
        }

        if (!is_dir($mpdfTempDirectory) && !mkdir($mpdfTempDirectory, 0775, true) && !is_dir($mpdfTempDirectory)) {
            throw new RuntimeException(sprintf('Could not create mPDF temp directory: %s', $mpdfTempDirectory));
        }

        [$preparedSvgMarkup, $labelHeightMm] = $this->prepareSvgForPdf($svgMarkup);
        $relativeFilePath = self::RELATIVE_OUTPUT_DIRECTORY . '/' . $this->createFileName($preferredBaseName, $svgMarkup);
        $absoluteFilePath = $this->projectDir . '/' . $relativeFilePath;

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'tempDir' => $mpdfTempDirectory,
            'default_font' => 'dejavusans',
            'format' => 'A4',
        ]);
        $mpdf->SetMargins(self::PDF_PAGE_MARGIN_MM, self::PDF_PAGE_MARGIN_MM, self::PDF_PAGE_MARGIN_MM);
        $mpdf->SetAutoPageBreak(false, self::PDF_PAGE_MARGIN_MM);
        $mpdf->WriteFixedPosHTML(
            $this->buildHtml($preparedSvgMarkup, $labelHeightMm),
            self::PDF_PAGE_MARGIN_MM,
            self::PDF_PAGE_MARGIN_MM,
            self::PDF_LABEL_WIDTH_MM,
            $labelHeightMm,
            'visible'
        );
        $mpdf->Output($absoluteFilePath, Destination::FILE);

        return [
            'relativePath' => $relativeFilePath,
            'cleanupAfterSend' => false,
        ];
    }

    private function createFileName(string $preferredBaseName, string $svgMarkup): string
    {
        $baseName = preg_replace('/\.pdf$/i', '', $preferredBaseName) ?? $preferredBaseName;
        $suffix = substr(sha1($svgMarkup), 0, 12);

        return sprintf('%s-%s.pdf', $baseName, $suffix);
    }

    private function buildHtml(string $svgMarkup, float $labelHeightMm): string
    {
        $svgDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgMarkup);
        $labelHeight = $this->formatMillimetres($labelHeightMm);

        return sprintf(
            '<img src="%s" style="display:block;width:%s;height:%s;" alt="" />',
            $svgDataUri,
            $this->formatMillimetres(self::PDF_LABEL_WIDTH_MM),
            $labelHeight
        );
    }

    /**
     * @return array{0: string, 1: float}
     */
    private function prepareSvgForPdf(string $svgMarkup): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($svgMarkup);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$loaded) {
            throw new RuntimeException('GARAN PDF SVG markup is not valid XML.');
        }

        $svgElement = $document->documentElement;

        if (!$svgElement instanceof DOMElement || strtolower($svgElement->localName) !== 'svg') {
            throw new RuntimeException('GARAN PDF SVG markup does not contain an `svg` root element.');
        }

        [$viewBoxWidth, $viewBoxHeight] = $this->extractViewBoxDimensions($svgElement);
        $labelHeightMm = self::PDF_LABEL_WIDTH_MM * ($viewBoxHeight / $viewBoxWidth);

        $svgElement->setAttribute('width', $this->formatMillimetres(self::PDF_LABEL_WIDTH_MM));
        $svgElement->setAttribute('height', $this->formatMillimetres($labelHeightMm));
        $svgElement->setAttribute('preserveAspectRatio', 'xMinYMin meet');

        $renderedSvg = $document->saveXML($svgElement);

        if (false === $renderedSvg) {
            throw new RuntimeException('GARAN PDF SVG markup could not be serialised.');
        }

        return [$renderedSvg, $labelHeightMm];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function extractViewBoxDimensions(DOMElement $svgElement): array
    {
        $viewBox = trim($svgElement->getAttribute('viewBox'));

        if ($viewBox === '') {
            throw new RuntimeException('GARAN PDF SVG root element does not define a `viewBox`.');
        }

        $parts = preg_split('/[\s,]+/', $viewBox);

        if (!is_array($parts) || count($parts) !== 4) {
            throw new RuntimeException(sprintf('GARAN PDF SVG `viewBox` is invalid: %s', $viewBox));
        }

        $width = (float) $parts[2];
        $height = (float) $parts[3];

        if ($width <= 0.0 || $height <= 0.0) {
            throw new RuntimeException(sprintf('GARAN PDF SVG `viewBox` must define positive dimensions: %s', $viewBox));
        }

        return [$width, $height];
    }

    private function formatMillimetres(float $value): string
    {
        return rtrim(rtrim(sprintf('%.3F', $value), '0'), '.') . 'mm';
    }
}
