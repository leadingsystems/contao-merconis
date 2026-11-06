<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\GaranLabelRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\GuaranteeDurationFormatter;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use RuntimeException;

final class GaranLabelRenderer implements GaranLabelRendererInterface
{
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    private const DEFAULT_TRUNCATION_LENGTH = 30;
    private const ORIGINAL_DURATION_FONT_SIZE = '80px';
    private const DECIMAL_DURATION_FONT_SIZE = '50.5px';

    public function __construct(
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
        private readonly string $version,
    ) {
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function render(string $brand, string $modelIdentifier, string $durationText, string $decimalSeparator): string
    {
        $brand = $this->truncateLabelText(
            $this->requireNonEmptyValue($brand, 'brand')
        );
        $modelIdentifier = $this->truncateLabelText(
            $this->requireNonEmptyValue($modelIdentifier, 'model')
        );
        $durationText = $this->requireNonEmptyValue($durationText, 'duration');
        $templatePath = $this->assetLocator->getGaranColourTemplatePath($this->version);
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

        $this->replaceTextNodeContents($document, $xpath, 'brand', $brand);
        $this->replaceTextNodeContents($document, $xpath, 'model', $modelIdentifier);
        $this->replaceTextNodeContents(
            $document,
            $xpath,
            'duration',
            GuaranteeDurationFormatter::formatYearsForDisplay($durationText, $decimalSeparator)
        );
        $this->configureDurationFontSize($xpath, $durationText);

        $renderedSvg = $document->saveXML($document->documentElement);

        if (false === $renderedSvg) {
            throw new RuntimeException('GARAN SVG could not be serialised.');
        }

        return $renderedSvg;
    }

    private function replaceTextNodeContents(DOMDocument $document, DOMXPath $xpath, string $field, string $value): void
    {
        $textElement = $xpath->query(sprintf('//svg:text[@data-field="%s"]', $field))?->item(0);

        if (!$textElement instanceof DOMElement) {
            throw new RuntimeException(sprintf('Required GARAN text node not found: %s', $field));
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

    private function requireNonEmptyValue(string $value, string $field): string
    {
        $trimmedValue = trim($value);

        if ('' === $trimmedValue) {
            throw new RuntimeException(sprintf('Required GARAN field is empty: %s', $field));
        }

        return $trimmedValue;
    }

    private function truncateLabelText(string $value): string
    {
        $truncationLength = $this->getTruncationLength();

        if (mb_strlen($value) <= $truncationLength) {
            return $value;
        }

        return mb_substr($value, 0, $truncationLength) . '...';
    }

    private function configureDurationFontSize(DOMXPath $xpath, string $durationText): void
    {
        $durationElement = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);

        if (!$durationElement instanceof DOMElement) {
            throw new RuntimeException('Required GARAN text node not found: duration');
        }

        $durationElement->setAttribute(
            'font-size',
            $this->isDecimalDuration($durationText)
                ? self::DECIMAL_DURATION_FONT_SIZE
                : self::ORIGINAL_DURATION_FONT_SIZE
        );
    }

    private function isDecimalDuration(string $durationText): bool
    {
        $normalisedDuration = trim($durationText);
        $durationYears = (float) $normalisedDuration;

        return abs($durationYears - round($durationYears)) > 0.00001;
    }

    private function getTruncationLength(): int
    {
        $configuredLength = (int) ($GLOBALS['TL_CONFIG']['garanLabelTruncationLength'] ?? 0);

        if ($configuredLength < 1) {
            return self::DEFAULT_TRUNCATION_LENGTH;
        }

        return $configuredLength;
    }
}
