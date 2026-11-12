<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use DOMDocument;
use DOMElement;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Output\Destination;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class MpdfGaranPdfAttachmentWriter implements GaranPdfAttachmentWriterInterface
{
    private const RELATIVE_OUTPUT_DIRECTORY = 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels';
    private const RELATIVE_MPDF_TEMP_DIRECTORY = 'var/tmp/merconis/mpdf';
    private const PDF_PAGE_MARGIN_MM = 15;
    private const PDF_LABEL_WIDTH_MM = 118;
    private const PRODUCT_NAME_MARGIN_BOTTOM_MM = 6;
    private const LABEL_MARGIN_BOTTOM_MM = 6;

    public function __construct(
        private readonly string $projectDir,
        private readonly LoggerInterface $contaoErrorLogger,
    ) {
    }

    public function write(array $pages, string $orderNumber, int $orderDateUnixTimestamp): array
    {
        $relativeOutputDirectory = $this->buildRelativeOutputDirectory($orderDateUnixTimestamp);
        $outputDirectory = $this->projectDir . '/' . $relativeOutputDirectory;
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
        $relativeFilePath = '';
        $absoluteFilePath = '';

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
        $mpdf->imageVars = [];

        $staticSvgMarkup = null;

        foreach ($renderedPages as $pageIndex => $renderedPage) {
            if ($pageIndex > 0) {
                $mpdf->AddPage();
            }

            if (isset($renderedPage['staticSvgMarkup'], $renderedPage['valueSvgMarkup'])) {
                $staticSvgMarkup ??= $renderedPage['staticSvgMarkup'];
                $mpdf->imageVars['garanStatic'] = $staticSvgMarkup;
                $mpdf->imageVars['garanValues' . $pageIndex] = $renderedPage['valueSvgMarkup'];
            }

            $mpdf->WriteHTML($renderedPage['html']);
        }

        $pdfContents = $mpdf->Output('', Destination::STRING_RETURN);

        if (!is_string($pdfContents) || '' === $pdfContents) {
            throw new RuntimeException('GARAN PDF output is empty.');
        }

        [
            'fileHandle' => $fileHandle,
            'relativePath' => $relativeFilePath,
            'absolutePath' => $absoluteFilePath,
        ] = $this->reserveOutputFile($relativeOutputDirectory, $outputDirectory, $orderNumber);

        try {
            $writtenBytes = fwrite($fileHandle, $pdfContents);

            if (false === $writtenBytes || $writtenBytes !== strlen($pdfContents)) {
                throw new RuntimeException(sprintf('Could not write GARAN PDF output file completely: %s', $absoluteFilePath));
            }
        } catch (Throwable $exception) {
            fclose($fileHandle);
            @unlink($absoluteFilePath);

            throw $exception;
        }

        fclose($fileHandle);

        return [
            'relativePath' => $relativeFilePath,
        ];
    }

    private function buildRelativeOutputDirectory(int $orderDateUnixTimestamp): string
    {
        $datePath = date('Y/m/d', $orderDateUnixTimestamp > 0 ? $orderDateUnixTimestamp : time());

        return self::RELATIVE_OUTPUT_DIRECTORY . '/' . $datePath;
    }

    /**
     * @return array{fileHandle: resource, relativePath: string, absolutePath: string}
     */
    private function reserveOutputFile(
        string $relativeOutputDirectory,
        string $absoluteOutputDirectory,
        string $orderNumber
    ): array {
        $sanitizedOrderNumber = $this->sanitizeOrderNumber($orderNumber);
        $sequenceNumber = $this->determineNextSequenceNumber($absoluteOutputDirectory, $sanitizedOrderNumber);

        while (true) {
            $fileName = $this->buildFileName($sanitizedOrderNumber, $sequenceNumber);
            $absoluteFilePath = $absoluteOutputDirectory . '/' . $fileName;
            $reservationHandle = @fopen($absoluteFilePath, 'x');

            if (false !== $reservationHandle) {
                return [
                    'fileHandle' => $reservationHandle,
                    'relativePath' => $relativeOutputDirectory . '/' . $fileName,
                    'absolutePath' => $absoluteFilePath,
                ];
            }

            $sequenceNumber = max(
                $sequenceNumber + 1,
                $this->determineNextSequenceNumber($absoluteOutputDirectory, $sanitizedOrderNumber)
            );
        }
    }

    private function sanitizeOrderNumber(string $orderNumber): string
    {
        $sanitizedOrderNumber = preg_replace('/[^A-Za-z0-9._-]/', '-', trim($orderNumber)) ?? '';

        return '' !== $sanitizedOrderNumber ? $sanitizedOrderNumber : 'order';
    }

    private function determineNextSequenceNumber(string $absoluteOutputDirectory, string $sanitizedOrderNumber): int
    {
        $highestSequenceNumber = 0;
        $directoryEntries = scandir($absoluteOutputDirectory);

        if (!is_array($directoryEntries)) {
            throw new RuntimeException(sprintf('Could not read GARAN output directory: %s', $absoluteOutputDirectory));
        }

        $fileNamePattern = sprintf(
            '/^garan-labels-%s(?:_(\d+))?\.pdf$/',
            preg_quote($sanitizedOrderNumber, '/')
        );

        foreach ($directoryEntries as $directoryEntry) {
            if (!preg_match($fileNamePattern, $directoryEntry, $matches)) {
                continue;
            }

            $sequenceNumber = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 1;
            $highestSequenceNumber = max($highestSequenceNumber, $sequenceNumber);
        }

        return $highestSequenceNumber > 0 ? $highestSequenceNumber + 1 : 1;
    }

    private function buildFileName(string $sanitizedOrderNumber, int $sequenceNumber): string
    {
        if ($sequenceNumber <= 1) {
            return sprintf('garan-labels-%s.pdf', $sanitizedOrderNumber);
        }

        return sprintf('garan-labels-%s_%d.pdf', $sanitizedOrderNumber, $sequenceNumber);
    }

    /**
     * @param list<array{
     *   type: 'label'|'notice',
     *   productNames: list<string>,
     *   noticeText: string,
     *   orderNumber: string,
     *   articleNumbers: list<string>,
     *   svgMarkup?: string,
     *   euLinkText?: string,
     *   euLinkUrl?: string
     * }> $pages
     *
     * @return list<array{
     *   html: string,
     *   staticSvgMarkup?: string,
     *   valueSvgMarkup?: string
     * }>
     */
    private function preparePages(array $pages): array
    {
        $renderedPages = [];

        foreach ($pages as $page) {
            if (($page['type'] ?? '') === 'notice') {
                $renderedPages[] = [
                    'html' => $this->buildNoticeHtml($page['productNames'], $page['noticeText']),
                ];
                continue;
            }

            try {
                [
                    'labelHeightMm' => $labelHeightMm,
                    'staticSvgMarkup' => $staticSvgMarkup,
                    'valueSvgMarkup' => $valueSvgMarkup,
                ] = $this->splitRenderedSvgForPdf((string) ($page['svgMarkup'] ?? ''));

                $renderedPages[] = [
                    'html' => $this->buildLabelHtml(
                        $page['productNames'],
                        'garanStatic',
                        'garanValues' . count($renderedPages),
                        $labelHeightMm,
                        (string) ($page['euLinkText'] ?? ''),
                        (string) ($page['euLinkUrl'] ?? '')
                    ),
                    'staticSvgMarkup' => $staticSvgMarkup,
                    'valueSvgMarkup' => $valueSvgMarkup,
                ];
            } catch (Throwable $exception) {
                $this->logUnavailableLabel($page['orderNumber'], $page['articleNumbers'], $exception);

                $renderedPages[] = [
                    'html' => $this->buildNoticeHtml($page['productNames'], $page['noticeText']),
                ];
            }
        }

        return $renderedPages;
    }

    /**
     * @return array{
     *   labelHeightMm: float,
     *   staticSvgMarkup: string,
     *   valueSvgMarkup: string
     * }
     */
    private function splitRenderedSvgForPdf(string $svgMarkup): array
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

        $this->ensureSvgNamespace($svgElement);

        return [
            'labelHeightMm' => $labelHeightMm,
            'staticSvgMarkup' => $this->buildStaticSvgMarkup($svgElement),
            'valueSvgMarkup' => $this->buildValueSvgMarkup($svgElement),
        ];
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
    private function buildLabelHtml(
        array $productNames,
        string $staticImageVar,
        string $valueImageVar,
        float $labelHeightMm,
        string $euLinkText,
        string $euLinkUrl
    ): string {
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
            . '<div style="width:%s;margin:0 auto %s auto;">'
            . '<img src="var:%s" style="display:block;width:%s;" alt="" />'
            . '<div style="margin-top:-%s;">'
            . '<img src="var:%s" style="display:block;width:%s;" alt="" />'
            . '</div>'
            . '</div>'
            . '<div style="margin:0;"><a href="%s">%s</a></div>'
            . '</div>',
            $this->formatMillimetres(self::PRODUCT_NAME_MARGIN_BOTTOM_MM),
            $productNamesHtml,
            $this->formatMillimetres(self::PDF_LABEL_WIDTH_MM),
            $this->formatMillimetres(self::LABEL_MARGIN_BOTTOM_MM),
            htmlspecialchars($staticImageVar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $this->formatMillimetres(self::PDF_LABEL_WIDTH_MM),
            $this->formatMillimetres($labelHeightMm),
            htmlspecialchars($valueImageVar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $this->formatMillimetres(self::PDF_LABEL_WIDTH_MM),
            htmlspecialchars($euLinkUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($euLinkText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    /**
     * @param list<string> $productNames
     */
    private function buildNoticeHtml(array $productNames, string $noticeText): string
    {
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
            . '<div style="margin:0;">%s</div>'
            . '</div>',
            $this->formatMillimetres(self::PRODUCT_NAME_MARGIN_BOTTOM_MM),
            $productNamesHtml,
            htmlspecialchars($noticeText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    private function requireFontFile(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Required GARAN PDF font file is missing: %s', $path));
        }
    }

    private function ensureSvgNamespace(DOMElement $svgElement): void
    {
        if (!$svgElement->hasAttribute('xmlns')) {
            $svgElement->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        }
    }

    private function buildStaticSvgMarkup(DOMElement $sourceSvgElement): string
    {
        $staticDocument = new DOMDocument('1.0', 'UTF-8');
        $staticDocument->loadXML($sourceSvgElement->ownerDocument?->saveXML($sourceSvgElement) ?: '');
        $staticSvgElement = $staticDocument->documentElement;

        if (!$staticSvgElement instanceof DOMElement) {
            throw new RuntimeException('GARAN static SVG could not be created.');
        }

        foreach ($this->findRenderableTextElements($staticSvgElement) as $textElement) {
            $textElement->parentNode?->removeChild($textElement);
        }

        $this->stripRootDimensions($staticSvgElement);

        return $this->saveSvgElement($staticDocument, $staticSvgElement, 'static');
    }

    private function buildValueSvgMarkup(DOMElement $sourceSvgElement): string
    {
        $valueDocument = new DOMDocument('1.0', 'UTF-8');
        $valueSvgElement = $valueDocument->createElementNS('http://www.w3.org/2000/svg', 'svg');
        $valueDocument->appendChild($valueSvgElement);
        $valueSvgElement->setAttribute('viewBox', $sourceSvgElement->getAttribute('viewBox'));
        $valueSvgElement->setAttribute('xmlns', 'http://www.w3.org/2000/svg');

        $defsElement = $this->findDefsElement($sourceSvgElement);

        if (null === $defsElement) {
            throw new RuntimeException('GARAN value SVG requires a `defs` element.');
        }

        $valueSvgElement->appendChild($valueDocument->importNode($defsElement, true));

        foreach ($this->requireRenderableTextElements($sourceSvgElement) as $textElement) {
            $valueSvgElement->appendChild($valueDocument->importNode($textElement, true));
        }

        return $this->saveSvgElement($valueDocument, $valueSvgElement, 'value');
    }

    /**
     * @return list<DOMElement>
     */
    private function findRenderableTextElements(DOMElement $svgElement): array
    {
        $textElements = [];

        foreach ($svgElement->getElementsByTagName('text') as $textNode) {
            if ($textNode instanceof DOMElement && $textNode->hasAttribute('data-field')) {
                $textElements[] = $textNode;
            }
        }

        return $textElements;
    }

    /**
     * @return array{0: DOMElement, 1: DOMElement, 2: DOMElement}
     */
    private function requireRenderableTextElements(DOMElement $svgElement): array
    {
        $textElementsByField = [];

        foreach ($this->findRenderableTextElements($svgElement) as $textElement) {
            $fieldName = trim($textElement->getAttribute('data-field'));

            if (isset($textElementsByField[$fieldName])) {
                throw new RuntimeException(sprintf('GARAN value SVG contains duplicate field "%s".', $fieldName));
            }

            $textElementsByField[$fieldName] = $textElement;
        }

        foreach (['brand', 'model', 'duration'] as $fieldName) {
            if (!isset($textElementsByField[$fieldName])) {
                throw new RuntimeException(sprintf('GARAN value SVG is missing field "%s".', $fieldName));
            }
        }

        return [
            $textElementsByField['brand'],
            $textElementsByField['model'],
            $textElementsByField['duration'],
        ];
    }

    private function findDefsElement(DOMElement $svgElement): ?DOMElement
    {
        foreach ($svgElement->getElementsByTagName('defs') as $defsNode) {
            if ($defsNode instanceof DOMElement) {
                return $defsNode;
            }
        }

        return null;
    }

    private function stripRootDimensions(DOMElement $svgElement): void
    {
        $svgElement->removeAttribute('width');
        $svgElement->removeAttribute('height');
        $svgElement->removeAttribute('preserveAspectRatio');
    }

    private function saveSvgElement(DOMDocument $document, DOMElement $svgElement, string $kind): string
    {
        $svgMarkup = $document->saveXML($svgElement);

        if (false === $svgMarkup) {
            throw new RuntimeException(sprintf('GARAN %s SVG markup could not be serialised.', $kind));
        }

        return $svgMarkup;
    }

    /**
     * @param list<string> $articleNumbers
     */
    private function logUnavailableLabel(string $orderNumber, array $articleNumbers, Throwable $exception): void
    {
        $normalisedArticleNumbers = array_values(array_filter(array_map(
            static fn (string $articleNumber): string => trim($articleNumber),
            $articleNumbers
        ), static fn (string $articleNumber): bool => $articleNumber !== ''));

        $this->contaoErrorLogger->error(sprintf(
            'GARAN label generation failed for order %s and article numbers [%s]: %s',
            $orderNumber !== '' ? $orderNumber : 'unknown',
            [] !== $normalisedArticleNumbers ? implode(', ', $normalisedArticleNumbers) : 'unknown',
            $exception->getMessage()
        ));
    }
}
