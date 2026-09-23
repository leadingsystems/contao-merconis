<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

use DOMDocument;
use RuntimeException;

final class LegalGuaranteeMarkupBuilder
{
    /**
     * @param array<string, string> $attributes
     */
    public function buildSvgMarkup(string $svgMarkup, string $title, array $attributes = []): string
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

        $svgElement = $document->documentElement;
        $titleId = 'merconis-legal-guarantee-title-' . substr(md5($title . $svgMarkup), 0, 12);

        $svgElement->setAttribute('role', 'img');
        $svgElement->setAttribute('aria-labelledby', $titleId);
        $svgElement->setAttribute('focusable', 'false');

        foreach ($attributes as $name => $value) {
            $svgElement->setAttribute($name, $value);
        }

        $existingTitleNodes = $svgElement->getElementsByTagName('title');

        while ($existingTitleNodes->length > 0) {
            $existingTitleNode = $existingTitleNodes->item(0);

            if (null === $existingTitleNode || null === $existingTitleNode->parentNode) {
                break;
            }

            $existingTitleNode->parentNode->removeChild($existingTitleNode);
        }

        $titleElement = $document->createElementNS('http://www.w3.org/2000/svg', 'title');
        $titleElement->setAttribute('id', $titleId);
        $titleElement->appendChild($document->createTextNode($title));
        $svgElement->insertBefore($titleElement, $svgElement->firstChild);

        $serializedSvg = $document->saveXML($svgElement);

        if (false === $serializedSvg) {
            throw new RuntimeException('SVG markup could not be serialized.');
        }

        return '<div class="merconis-legal-guarantee-label">' . $serializedSvg . '</div>';
    }

    /**
     * @param array<string, string> $translations
     */
    public function buildCheckoutGllLinks(
        string $pageUrl,
        string $pageTitle,
        string $euUrl,
        array $translations,
    ): string {
        return sprintf(
            '<div class="merconis-legal-guarantee-checkout-links">'
            . '<a href="%s" target="_blank" rel="noreferrer noopener">%s</a> '
            . '<a href="%s" target="_blank" rel="noreferrer noopener">%s</a>'
            . '</div>',
            $this->escape($pageUrl),
            $this->escape($pageTitle),
            $this->escape($euUrl),
            $this->escape($translations['gllEuLinkText'] ?? '')
        );
    }

    /**
     * @param list<array{
     *   productTitle: string,
     *   variantTitle: string,
     *   labelMarkup: string,
     *   euUrl: string
     * }> $items
     * @param array<string, string> $translations
     */
    public function buildCheckoutGaranBlock(array $items, array $translations): string
    {
        if ([] === $items) {
            return '';
        }

        $html = '<div class="merconis-legal-guarantee-checkout-block">';
        $html .= '<h3>' . $this->escape($translations['garanBlockHeadline'] ?? '') . '</h3>';

        foreach ($items as $item) {
            $html .= '<div class="merconis-legal-guarantee-checkout-item">';
            $html .= '<p class="merconis-legal-guarantee-checkout-item-title">';
            $html .= $this->escape($item['productTitle']);

            if ('' !== $item['variantTitle']) {
                $html .= '<br><span class="variant-title">' . $this->escape($item['variantTitle']) . '</span>';
            }

            $html .= '</p>';
            $html .= $item['labelMarkup'];
            $html .= sprintf(
                '<p class="merconis-legal-guarantee-checkout-item-link">'
                . '<a href="%s" target="_blank" rel="noreferrer noopener">%s</a>'
                . '</p>',
                $this->escape($item['euUrl']),
                $this->escape($translations['garanEuLinkText'] ?? '')
            );
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
