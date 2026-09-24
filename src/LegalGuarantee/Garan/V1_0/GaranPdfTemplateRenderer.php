<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use RuntimeException;

final class GaranPdfTemplateRenderer
{
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

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

        $this->replaceTextNodeContents($document, $xpath, 'brand', $brand);
        $this->replaceTextNodeContents($document, $xpath, 'model', $modelIdentifier);
        $this->replaceTextNodeContents($document, $xpath, 'duration', $durationText);

        $renderedSvg = $document->saveXML($document->documentElement);

        if (false === $renderedSvg) {
            throw new RuntimeException('GARAN PDF SVG could not be serialised.');
        }

        return $renderedSvg;
    }

    private function replaceTextNodeContents(DOMDocument $document, DOMXPath $xpath, string $field, string $value): void
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
    }
}
