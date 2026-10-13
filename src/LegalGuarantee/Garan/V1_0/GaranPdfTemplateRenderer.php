<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\GuaranteeDurationFormatter;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use RuntimeException;

final class GaranPdfTemplateRenderer
{
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    private const BRAND_MODEL_BASE_FONT_SIZE_PX = 9.0;
    private const DURATION_BASE_FONT_SIZE_PX = 80.0;
    private const BRAND_MAX_WIDTH_PX = 190.43;
    private const MODEL_MAX_WIDTH_PX = 66.22;
    private const DURATION_MAX_WIDTH_PX = 116.4;
    private const DURATION_LETTER_SPACING_EM = -0.03;

    public function __construct(
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
    ) {
    }

    public function render(string $brand, string $modelIdentifier, string $durationText): string
    {
        $templatePath = $this->assetLocator->getGaranPdfTemplatePath();
        $templateContents = file_get_contents($templatePath);

        if (false === $templateContents) {
            throw new RuntimeException(sprintf('GARAN PDF template could not be read: %s', $templatePath));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($templateContents);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$loaded) {
            throw new RuntimeException(sprintf('GARAN PDF template is not valid XML: %s', $templatePath));
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', self::SVG_NAMESPACE);
        $textFitCalculator = new GaranTextFitCalculator();
        $dejaVuSansDirectory = dirname(__DIR__, 7) . '/vendor/mpdf/mpdf/ttfonts';

        $brandElement = $this->replaceTextNodeContents($document, $xpath, 'brand', $brand);
        $textFitCalculator->applyFittedFontSize(
            $brandElement,
            $brand,
            self::BRAND_MODEL_BASE_FONT_SIZE_PX,
            self::BRAND_MAX_WIDTH_PX,
            $dejaVuSansDirectory . '/DejaVuSans.ttf'
        );

        $modelElement = $this->replaceTextNodeContents($document, $xpath, 'model', $modelIdentifier);
        $textFitCalculator->applyFittedFontSize(
            $modelElement,
            $modelIdentifier,
            self::BRAND_MODEL_BASE_FONT_SIZE_PX,
            self::MODEL_MAX_WIDTH_PX,
            $dejaVuSansDirectory . '/DejaVuSans.ttf'
        );

        $formattedDuration = GuaranteeDurationFormatter::formatYearsForDisplay($durationText);
        $durationElement = $this->replaceTextNodeContents(
            $document,
            $xpath,
            'duration',
            $formattedDuration
        );
        $textFitCalculator->applyFittedFontSize(
            $durationElement,
            $formattedDuration,
            self::DURATION_BASE_FONT_SIZE_PX,
            self::DURATION_MAX_WIDTH_PX,
            $dejaVuSansDirectory . '/DejaVuSans-Bold.ttf',
            self::DURATION_LETTER_SPACING_EM
        );

        $renderedSvg = $document->saveXML($document->documentElement);

        if (false === $renderedSvg) {
            throw new RuntimeException('GARAN PDF SVG could not be serialised.');
        }

        return $renderedSvg;
    }

    private function replaceTextNodeContents(
        DOMDocument $document,
        DOMXPath $xpath,
        string $field,
        string $value
    ): DOMElement
    {
        $textElement = $xpath->query(sprintf('//svg:text[@data-field="%s"]', $field))?->item(0);

        if (!$textElement instanceof DOMElement) {
            throw new RuntimeException(sprintf('Required GARAN PDF text node not found: %s', $field));
        }

        while ($textElement->firstChild !== null) {
            $textElement->removeChild($textElement->firstChild);
        }

        $tspanElement = $document->createElementNS(self::SVG_NAMESPACE, 'tspan');
        $tspanElement->setAttribute('x', '0');
        $tspanElement->setAttribute('y', '0');
        $tspanElement->appendChild($document->createTextNode($value));

        $textElement->appendChild($tspanElement);

        return $textElement;
    }
}
