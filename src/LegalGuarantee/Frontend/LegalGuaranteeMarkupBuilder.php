<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

use DOMDocument;
use DOMElement;
use RuntimeException;

final class LegalGuaranteeMarkupBuilder
{
    private const SVG_ID_PREFIX = 'merconis-legal-guarantee-svg-';
    private const POPOVER_ID_PREFIX = 'merconis-legal-guarantee-popover-';

    /**
     * Anzahl der Verwendungen je Kennung im aktuellen Request. Ab der zweiten Verwendung
     * erhält die Kennung einen fortlaufenden Zähler (Spec 27, Abschnitt 8.3).
     *
     * @var array<string, int>
     */
    private array $usageCountByIdentifier = [];

    /**
     * Bereitet ein SVG für die Ausgabe als Inline-SVG mit `<title>`, `role="img"` und
     * `aria-labelledby` auf. Das Präfix der Fragment-IDs bildet `$idScope` (Typ und Bezug,
     * zum Beispiel `garan-6560`).
     *
     * @param array<string, string> $attributes
     *
     * @throws RuntimeException Wenn das SVG nicht gelesen oder geschrieben werden kann
     */
    public function buildSvgMarkup(string $svgMarkup, string $title, string $idScope, array $attributes = []): string
    {
        $document = $this->loadSvgDocument($svgMarkup);
        $svgElement = $document->documentElement;
        $renderPrefix = $this->reserveIdentifier(self::SVG_ID_PREFIX . $idScope) . '-';
        $this->rewriteFragmentIdentifiers($document, $renderPrefix);
        $this->removeTitleElements($svgElement);

        $titleId = $renderPrefix . 'title';

        $svgElement->setAttribute('role', 'img');
        $svgElement->setAttribute('aria-labelledby', $titleId);
        $svgElement->setAttribute('focusable', 'false');

        foreach ($attributes as $name => $value) {
            $svgElement->setAttribute($name, $value);
        }

        $titleElement = $document->createElementNS('http://www.w3.org/2000/svg', 'title');
        $titleElement->setAttribute('id', $titleId);
        $titleElement->appendChild($document->createTextNode($title));
        $svgElement->insertBefore($titleElement, $svgElement->firstChild);

        return $this->serializeSvgElement($document, $svgElement);
    }

    /**
     * Variante für das Nested Label im GARAN-Button: ohne `<title>`, für Screenreader
     * ausgeblendet (Spec 27, Abschnitt 12). Den Namen trägt der Button.
     *
     * @throws RuntimeException Wenn das SVG nicht gelesen oder geschrieben werden kann
     */
    public function buildDecorativeSvgMarkup(string $svgMarkup, string $idScope): string
    {
        $document = $this->loadSvgDocument($svgMarkup);
        $svgElement = $document->documentElement;
        $this->rewriteFragmentIdentifiers(
            $document,
            $this->reserveIdentifier(self::SVG_ID_PREFIX . $idScope) . '-'
        );
        $this->removeTitleElements($svgElement);

        $svgElement->removeAttribute('role');
        $svgElement->removeAttribute('aria-labelledby');
        $svgElement->setAttribute('aria-hidden', 'true');
        $svgElement->setAttribute('focusable', 'false');

        return $this->serializeSvgElement($document, $svgElement);
    }

    /**
     * Liefert die ID der geöffneten Ansicht eines Buttons aus Typ und Bezug, zum Beispiel
     * `merconis-legal-guarantee-popover-lgn-6560`.
     */
    public function reservePopoverId(string $idScope): string
    {
        return $this->reserveIdentifier(self::POPOVER_ID_PREFIX . $idScope);
    }

    private function reserveIdentifier(string $identifier): string
    {
        $usageCount = ($this->usageCountByIdentifier[$identifier] ?? 0) + 1;
        $this->usageCountByIdentifier[$identifier] = $usageCount;

        return 1 === $usageCount ? $identifier : $identifier . '-' . $usageCount;
    }

    /**
     * @throws RuntimeException Wenn das SVG nicht gelesen werden kann
     */
    private function loadSvgDocument(string $svgMarkup): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($svgMarkup);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$loaded || null === $document->documentElement) {
            throw new RuntimeException('SVG markup could not be parsed.');
        }

        return $document;
    }

    private function removeTitleElements(DOMElement $svgElement): void
    {
        $existingTitleNodes = $svgElement->getElementsByTagName('title');

        while ($existingTitleNodes->length > 0) {
            $existingTitleNode = $existingTitleNodes->item(0);

            if (null === $existingTitleNode || null === $existingTitleNode->parentNode) {
                break;
            }

            $existingTitleNode->parentNode->removeChild($existingTitleNode);
        }
    }

    /**
     * @throws RuntimeException Wenn das SVG nicht geschrieben werden kann
     */
    private function serializeSvgElement(DOMDocument $document, DOMElement $svgElement): string
    {
        $serializedSvg = $document->saveXML($svgElement);

        if (false === $serializedSvg) {
            throw new RuntimeException('SVG markup could not be serialized.');
        }

        return $serializedSvg;
    }

    private function rewriteFragmentIdentifiers(DOMDocument $document, string $renderPrefix): void
    {
        /** @var list<DOMElement> $elementsWithIds */
        $elementsWithIds = [];
        $idMap = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if (!$element instanceof DOMElement || !$element->hasAttribute('id')) {
                continue;
            }

            $currentId = $element->getAttribute('id');

            if ('' === $currentId) {
                continue;
            }

            $elementsWithIds[] = $element;
            $idMap[$currentId] = $renderPrefix . $currentId;
        }

        if ([] === $idMap) {
            return;
        }

        foreach ($elementsWithIds as $element) {
            $currentId = $element->getAttribute('id');
            $element->setAttribute('id', $idMap[$currentId]);
        }

        foreach ($document->getElementsByTagName('*') as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }

            $attributeNames = [];

            if ($element->hasAttributes()) {
                foreach ($element->attributes as $attribute) {
                    if (null === $attribute) {
                        continue;
                    }

                    $attributeNames[] = $attribute->nodeName;
                }
            }

            foreach ($attributeNames as $attributeName) {
                if ('id' === $attributeName) {
                    continue;
                }

                $rewrittenValue = $this->rewriteAttributeValue(
                    $element->getAttribute($attributeName),
                    $attributeName,
                    $idMap
                );

                $element->setAttribute($attributeName, $rewrittenValue);
            }

            if ('style' === $element->localName) {
                $rewrittenCssContent = $this->rewriteCssContent(
                    $element->textContent,
                    $idMap
                );

                while ($element->firstChild !== null) {
                    $element->removeChild($element->firstChild);
                }

                $element->appendChild($document->createTextNode($rewrittenCssContent));
            }
        }
    }

    /**
     * @param array<string, string> $idMap
     */
    private function rewriteAttributeValue(string $value, string $attributeName, array $idMap): string
    {
        if ('' === $value) {
            return $value;
        }

        if (in_array($attributeName, ['aria-labelledby', 'aria-describedby'], true)) {
            $tokens = preg_split('/\s+/', trim($value)) ?: [];
            $rewrittenTokens = [];

            foreach ($tokens as $token) {
                $rewrittenTokens[] = $idMap[$token] ?? $token;
            }

            return implode(' ', $rewrittenTokens);
        }

        $rewrittenValue = $this->rewriteCssContent($value, $idMap);

        if (str_starts_with($rewrittenValue, '#')) {
            $fragmentId = substr($rewrittenValue, 1);

            if (isset($idMap[$fragmentId])) {
                return '#' . $idMap[$fragmentId];
            }
        }

        return $rewrittenValue;
    }

    /**
     * @param array<string, string> $idMap
     */
    private function rewriteCssContent(string $value, array $idMap): string
    {
        if ([] === $idMap || '' === $value) {
            return $value;
        }

        return preg_replace_callback(
            '/url\(#([^)]+)\)/',
            static function (array $matches) use ($idMap): string {
                $fragmentId = $matches[1];

                if (!isset($idMap[$fragmentId])) {
                    return $matches[0];
                }

                return 'url(#' . $idMap[$fragmentId] . ')';
            },
            $value
        ) ?? $value;
    }
}
