<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use PHPUnit\Framework\TestCase;

final class GaranLabelTemplateIntegrationTest extends TestCase
{
    private const TEMPLATE_PATH = __DIR__ . '/../../src/Resources/legal-guarantee/garan/v1.0/garan-label-colour.svg';
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

    public function testGaranTemplateContainsExpectedStructureAndWarningComment(): void
    {
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);

        self::assertSame(1, preg_match('/<!--(.*?)-->/s', $templateContents, $commentMatches));
        $warningComment = $commentMatches[1];

        self::assertStringContainsString('technically prepared', $warningComment);
        self::assertStringContainsString('data-field attributes', $warningComment);
        self::assertStringContainsString('path-based QR code', $warningComment);
        self::assertStringContainsString('Replacing it with the untouched EU original breaks the renderer.', $warningComment);
        self::assertStringNotContainsString('clipPath definitions', $warningComment);
        self::assertStringNotContainsStringIgnoringCase('clip', $warningComment);

        $xpath = $this->createXPathForSvg($templateContents);

        $svgElement = $xpath->query('/svg:svg')?->item(0);
        self::assertInstanceOf(DOMElement::class, $svgElement);
        self::assertSame(self::SVG_NAMESPACE, $svgElement->namespaceURI);
        self::assertSame('0 0 269.29 283.46', $svgElement->getAttribute('viewBox'));

        $textElements = $xpath->query('//svg:text');
        self::assertSame(3, $textElements?->length);

        foreach (['brand', 'model', 'duration'] as $field) {
            self::assertSame(1, $xpath->query(sprintf('//svg:text[@data-field="%s"]', $field))?->length, $field);
        }

        $brandText = $xpath->query('//svg:text[@data-field="brand"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $brandText);
        self::assertSame('translate(6.32 74.52)', $brandText->getAttribute('transform'));
        self::assertFalse($brandText->hasAttribute('text-anchor'));
        self::assertEqualsWithDelta(0.0, $this->extractFirstTspanX($brandText), 0.01);

        $modelText = $xpath->query('//svg:text[@data-field="model"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $modelText);
        self::assertSame('end', $modelText->getAttribute('text-anchor'));
        self::assertSame(262.97, $this->extractTranslateX($modelText->getAttribute('transform')));
        self::assertEqualsWithDelta(0.0, $this->extractFirstTspanX($modelText), 0.01);

        $durationText = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $durationText);
        self::assertSame('end', $durationText->getAttribute('text-anchor'));
        self::assertEqualsWithDelta(115.07, $this->extractTranslateX($durationText->getAttribute('transform')), 0.01);
        self::assertEqualsWithDelta(0.0, $this->extractFirstTspanX($durationText), 0.01);
    }

    public function testGaranTemplateContainsNoClipping(): void
    {
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);
        $xpath = $this->createXPathForSvg($templateContents);

        self::assertSame(0, $xpath->query('//svg:clipPath')?->length);
        self::assertSame(0, $xpath->query('//*[@clip-path]')?->length);
        self::assertSame(0, $xpath->query('//svg:text[@data-field and @clip-path]')?->length);
        self::assertStringNotContainsString('clip-path', $templateContents);
        self::assertStringNotContainsString('<clipPath', $templateContents);
    }

    public function testDurationAnchorLiesBeforeCalendarSymbol(): void
    {
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);
        $xpath = $this->createXPathForSvg($templateContents);

        $durationText = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $durationText);

        $calendarRect = $xpath->query('//svg:rect[@x="121.47" and @y="127.8"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $calendarRect);

        $durationLabelAnchorX = $this->extractTranslateX($durationText->getAttribute('transform'))
            + $this->extractFirstTspanX($durationText);

        self::assertEqualsWithDelta(115.07, $durationLabelAnchorX, 0.01);
        self::assertLessThan($this->extractFloatAttribute($calendarRect, 'x'), $durationLabelAnchorX);
    }

    public function testBuildSvgMarkupKeepsFragmentIdsUniquePerRender(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);

        $firstXPath = $this->createXPathForSvg(
            $this->extractSvgMarkup(
                $builder->buildSvgMarkup($templateContents, 'First GARAN label', 'garan-10')
            )
        );
        $secondXPath = $this->createXPathForSvg(
            $this->extractSvgMarkup(
                $builder->buildSvgMarkup($templateContents, 'Second GARAN label', 'garan-10')
            )
        );

        $firstIds = $this->collectIds($firstXPath);
        $secondIds = $this->collectIds($secondXPath);

        self::assertNotEmpty($firstIds);
        self::assertNotEmpty($secondIds);
        self::assertSame([], array_values(array_intersect($firstIds, $secondIds)));

        $this->assertFragmentReferencesResolveWithinRender($firstXPath);
        $this->assertFragmentReferencesResolveWithinRender($secondXPath);
    }

    private function createXPathForSvg(string $svgMarkup): DOMXPath
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($svgMarkup);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        self::assertTrue($loaded);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', self::SVG_NAMESPACE);

        return $xpath;
    }

    private function extractSvgMarkup(string $labelMarkup): string
    {
        self::assertSame(1, preg_match('/(<svg\b.*<\/svg>)/s', $labelMarkup, $matches));

        return $matches[1];
    }

    /**
     * @return list<string>
     */
    private function collectIds(DOMXPath $xpath): array
    {
        $ids = [];

        foreach ($xpath->query('//@id') ?? [] as $attributeNode) {
            $ids[] = (string) $attributeNode->nodeValue;
        }

        return $ids;
    }

    private function assertFragmentReferencesResolveWithinRender(DOMXPath $xpath): void
    {
        $ids = array_flip($this->collectIds($xpath));
        $svgElement = $xpath->query('/svg:svg')?->item(0);
        self::assertInstanceOf(DOMElement::class, $svgElement);
        self::assertArrayHasKey($svgElement->getAttribute('aria-labelledby'), $ids);

        foreach ($xpath->query('//@*') ?? [] as $attributeNode) {
            preg_match_all('/url\(#([^)]+)\)/', (string) $attributeNode->nodeValue, $matches);

            foreach ($matches[1] as $referencedId) {
                self::assertArrayHasKey($referencedId, $ids);
            }
        }
    }

    private function extractFloatAttribute(DOMElement $element, string $attributeName): float
    {
        self::assertTrue($element->hasAttribute($attributeName));

        return (float) $element->getAttribute($attributeName);
    }

    private function extractTranslateX(string $transform): float
    {
        self::assertSame(1, preg_match('/translate\(([0-9.]+)\s+[0-9.]+\)/', $transform, $matches));

        return (float) $matches[1];
    }

    private function extractFirstTspanX(DOMElement $textElement): float
    {
        $tspanElement = $textElement->getElementsByTagNameNS(self::SVG_NAMESPACE, 'tspan')->item(0);
        self::assertInstanceOf(DOMElement::class, $tspanElement);

        return $this->extractFloatAttribute($tspanElement, 'x');
    }
}
