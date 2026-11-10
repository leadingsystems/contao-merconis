<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use DOMDocument;
use DOMElement;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Output\Destination;
use RuntimeException;

final class MpdfGaranPdfAttachmentWriter implements GaranPdfAttachmentWriterInterface
{
    private const RELATIVE_OUTPUT_DIRECTORY = 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels';
    private const RELATIVE_MPDF_TEMP_DIRECTORY = 'var/tmp/merconis/mpdf';
    private const PDF_PAGE_MARGIN_MM = 15;
    private const PDF_LABEL_WIDTH_MM = 118;
    private const PRODUCT_NAME_MARGIN_BOTTOM_MM = 6;
    private const SVG_MARGIN_BOTTOM_MM = 6;
    private const MACHINE_READABLE_TEXT_COLOR = '#ffffff';

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function write(array $pages, string $preferredBaseName): array
    {
        $outputDirectory = $this->projectDir . '/' . self::RELATIVE_OUTPUT_DIRECTORY;
        $mpdfTempDirectory = $this->projectDir . '/' . self::RELATIVE_MPDF_TEMP_DIRECTORY;
        $fontDirectory = $this->projectDir . '/vendor/leadingsystems/contao-merconis/src/Resources/public/legal-guarantee/fonts/inter';

        if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
            throw new RuntimeException(sprintf('Could not create GARAN output directory: %s', $outputDirectory));
        }

        if (!is_dir($mpdfTempDirectory) && !mkdir($mpdfTempDirectory, 0775, true) && !is_dir($mpdfTempDirectory)) {
            throw new RuntimeException(sprintf('Could not create mPDF temp directory: %s', $mpdfTempDirectory));
        }

        if ([] === $pages) {
            throw new RuntimeException('GARAN PDF pages must not be empty.');
        }

        $this->requireFontFile($fontDirectory . '/Inter-Regular.ttf');
        $this->requireFontFile($fontDirectory . '/Inter-ExtraBold.ttf');

        $renderedPages = $this->preparePages($pages);
        $relativeFilePath = self::RELATIVE_OUTPUT_DIRECTORY . '/' . $this->createFileName(
            $preferredBaseName,
            json_encode($renderedPages, JSON_THROW_ON_ERROR)
        );
        $absoluteFilePath = $this->projectDir . '/' . $relativeFilePath;

        $configVariables = new ConfigVariables();
        $fontVariables = new FontVariables();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'tempDir' => $mpdfTempDirectory,
            'format' => 'A5',
            'fontDir' => array_merge($configVariables->getDefaults()['fontDir'], [$fontDirectory]),
            'fontdata' => array_merge($fontVariables->getDefaults()['fontdata'], [
                'inter-regular' => [
                    'R' => 'Inter-Regular.ttf',
                ],
                'inter-extrabold' => [
                    'R' => 'Inter-ExtraBold.ttf',
                ],
            ]),
            'default_font' => 'inter-regular',
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
            'useSubstitutions' => false,
        ]);
        $mpdf->SetMargins(self::PDF_PAGE_MARGIN_MM, self::PDF_PAGE_MARGIN_MM, self::PDF_PAGE_MARGIN_MM);
        $mpdf->SetAutoPageBreak(false, self::PDF_PAGE_MARGIN_MM);

        foreach ($renderedPages as $pageIndex => $renderedPage) {
            if ($pageIndex > 0) {
                $mpdf->AddPage();
            }

            $mpdf->WriteHTML($renderedPage['html']);
        }

        $mpdf->Output($absoluteFilePath, Destination::FILE);

        return [
            'relativePath' => $relativeFilePath,
        ];
    }

    private function createFileName(string $preferredBaseName, string $svgMarkup): string
    {
        $baseName = preg_replace('/\.pdf$/i', '', $preferredBaseName) ?? $preferredBaseName;
        $suffix = substr(sha1($svgMarkup), 0, 12);

        return sprintf('%s-%s.pdf', $baseName, $suffix);
    }

    /**
     * @param list<array{
     *   svgMarkup: string,
     *   productNames: list<string>,
     *   euLinkText: string,
     *   euLinkUrl: string
     * }> $pages
     *
     * @return list<array{html: string, svgMarkup: string}>
     */
    private function preparePages(array $pages): array
    {
        $renderedPages = [];

        foreach ($pages as $page) {
            [$preparedSvgMarkup] = $this->prepareSvgForPdf($page['svgMarkup']);

            $renderedPages[] = [
                'html' => $this->buildHtml(
                    $preparedSvgMarkup,
                    $page['productNames'],
                    $page['euLinkText'],
                    $page['euLinkUrl']
                ),
                'svgMarkup' => $preparedSvgMarkup,
            ];
        }

        return $renderedPages;
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

        if (!$svgElement->hasAttribute('xmlns')) {
            $svgElement->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        }

        $svgElement->setAttribute('width', $this->formatMillimetres(self::PDF_LABEL_WIDTH_MM));
        $svgElement->setAttribute('height', $this->formatMillimetres($labelHeightMm));
        $svgElement->setAttribute('preserveAspectRatio', 'xMidYMin meet');

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

    /**
     * @param list<string> $productNames
     */
    private function buildHtml(
        string $svgMarkup,
        array $productNames,
        string $euLinkText,
        string $euLinkUrl
    ): string {
        $machineReadableText = $this->buildMachineReadableText($svgMarkup);
        $productNamesHtml = implode('', array_map(
            static fn (string $productName): string => sprintf(
                '<div style="margin:0 0 1.5mm 0;">%s</div>',
                htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            ),
            $productNames
        ));

        return sprintf(
            '<div style="font-family:inter-regular;font-size:10.5pt;line-height:1.35;">'
            . '<div style="margin:0 0 %s 0;">%s</div>'
            . '<div style="width:%s;margin:0 auto %s auto;">%s</div>'
            . '<div style="margin:0;"><a href="%s">%s</a></div>'
            . '<div style="font-size:0.1pt;line-height:0;color:%s;">%s</div>'
            . '</div>',
            $this->formatMillimetres(self::PRODUCT_NAME_MARGIN_BOTTOM_MM),
            $productNamesHtml,
            $this->formatMillimetres(self::PDF_LABEL_WIDTH_MM),
            $this->formatMillimetres(self::SVG_MARGIN_BOTTOM_MM),
            $svgMarkup,
            htmlspecialchars($euLinkUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($euLinkText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            self::MACHINE_READABLE_TEXT_COLOR,
            htmlspecialchars($machineReadableText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    private function buildMachineReadableText(string $svgMarkup): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($svgMarkup);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$loaded) {
            return '';
        }

        $values = [];

        foreach (['brand', 'model', 'duration'] as $fieldName) {
            $textNodes = $document->getElementsByTagName('text');

            foreach ($textNodes as $textNode) {
                if (!$textNode instanceof DOMElement || $textNode->getAttribute('data-field') !== $fieldName) {
                    continue;
                }

                $textValue = trim($textNode->textContent ?? '');

                if ($textValue !== '') {
                    $values[] = $textValue;
                }

                break;
            }
        }

        return implode(' ', $values);
    }

    private function requireFontFile(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Required GARAN PDF font file is missing: %s', $path));
        }
    }
}
