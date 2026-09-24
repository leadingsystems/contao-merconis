<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use RuntimeException;

final class GaranLabelRenderer
{
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    private const BRAND_NODE_XPATH = '//svg:text[@transform="translate(6.32 74.52)"]';
    private const MODEL_NODE_XPATH = '//svg:text[@transform="translate(196.75 74.52)"]';
    private const DURATION_NODE_XPATH = '//svg:text[@transform="translate(5.07 150.57)"]';

    public function __construct(
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
    ) {
    }

    public function render(string $brand, string $modelIdentifier, string $durationText): string
    {
        $templatePath = $this->assetLocator->getGaranColourTemplatePath();
        $templateContents = file_get_contents($templatePath);

        if (false === $templateContents) {
            throw new RuntimeException(sprintf('GARAN template could not be read: %s', $templatePath));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($templateContents);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$loaded) {
            throw new RuntimeException(sprintf('GARAN template is not valid XML: %s', $templatePath));
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', self::SVG_NAMESPACE);

        $this->replaceTextNodeContents($document, $xpath, self::BRAND_NODE_XPATH, $brand);
        $this->replaceTextNodeContents($document, $xpath, self::MODEL_NODE_XPATH, $modelIdentifier);
        $this->replaceTextNodeContents($document, $xpath, self::DURATION_NODE_XPATH, $durationText);

        $renderedSvg = $document->saveXML($document->documentElement);

        if (false === $renderedSvg) {
            throw new RuntimeException('GARAN SVG could not be serialised.');
        }

        return $renderedSvg;
    }

    private function replaceTextNodeContents(DOMDocument $document, DOMXPath $xpath, string $query, string $value): void
    {
        $textElement = $xpath->query($query)?->item(0);

        if (!$textElement instanceof DOMElement) {
            throw new RuntimeException(sprintf('Required GARAN text node not found: %s', $query));
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
