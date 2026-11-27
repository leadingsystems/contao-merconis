<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\GaranNestedLabelRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\GuaranteeDurationFormatter;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use RuntimeException;

/**
 * Die Position der Dauer ergibt sich allein aus `transform` und `text-anchor` der Vorlage;
 * der Renderer übernimmt keine Koordinaten aus ihr.
 */
final class GaranNestedLabelRenderer implements GaranNestedLabelRendererInterface
{
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    private const ORIGINAL_DURATION_FONT_SIZE = '41.56px';
    private const DECIMAL_DURATION_FONT_SIZE = '24.5px';

    public function __construct(
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
        private readonly string $version,
    ) {
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @throws RuntimeException Wenn die Dauer leer oder ungültig ist, die Vorlage nicht
     *                          gelesen werden kann oder ihr der Textknoten der Dauer fehlt
     */
    public function render(string $durationMonths): string
    {
        $normalisedDurationMonths = $this->requireValidDurationMonths($durationMonths);
        $templatePath = $this->assetLocator->getGaranNestedTemplatePath($this->version);
        $templateContents = file_get_contents($templatePath);

        if (false === $templateContents) {
            throw new RuntimeException(sprintf('GARAN nested template could not be read: %s', $templatePath));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($templateContents);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$loaded) {
            throw new RuntimeException(sprintf('GARAN nested template is not valid XML: %s', $templatePath));
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', self::SVG_NAMESPACE);

        $durationElement = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);

        if (!$durationElement instanceof DOMElement) {
            throw new RuntimeException('Required GARAN nested text node not found: duration');
        }

        while ($durationElement->firstChild !== null) {
            $durationElement->removeChild($durationElement->firstChild);
        }

        $tspanElement = $document->createElementNS(self::SVG_NAMESPACE, 'tspan');
        $tspanElement->setAttribute('x', '0');
        $tspanElement->setAttribute('y', '0');
        $tspanElement->appendChild($document->createTextNode(
            GuaranteeDurationFormatter::formatMonthsForDisplay($normalisedDurationMonths)
        ));
        $durationElement->appendChild($tspanElement);

        $durationElement->setAttribute(
            'font-size',
            0 !== ((int) $normalisedDurationMonths) % 12
                ? self::DECIMAL_DURATION_FONT_SIZE
                : self::ORIGINAL_DURATION_FONT_SIZE
        );

        $renderedSvg = $document->saveXML($document->documentElement);

        if (false === $renderedSvg) {
            throw new RuntimeException('GARAN nested SVG could not be serialised.');
        }

        return $renderedSvg;
    }

    /**
     * Ungültig ist eine Dauer, die keine positive ganze Zahl von Monaten ist. Die
     * Zulässigkeit nach Spec 27, Abschnitt 3.2, wird zur Laufzeit nicht erneut geprüft
     * (Spec 27, Abschnitt 4, "Ausgabe").
     *
     * @throws RuntimeException Wenn die Dauer leer oder ungültig ist
     */
    private function requireValidDurationMonths(string $durationMonths): string
    {
        $normalisedDurationMonths = trim($durationMonths);

        if ('' === $normalisedDurationMonths) {
            throw new RuntimeException('Required GARAN field is empty: duration');
        }

        if (!preg_match('/^\d+$/', $normalisedDurationMonths) || 0 === (int) $normalisedDurationMonths) {
            throw new RuntimeException(sprintf('GARAN duration is invalid: %s', $normalisedDurationMonths));
        }

        return $normalisedDurationMonths;
    }
}
