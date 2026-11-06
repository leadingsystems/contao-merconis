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

        self::assertStringContainsString('technically prepared', $templateContents);
        self::assertStringContainsString('data-field attributes', $templateContents);
        self::assertStringContainsString('clipPath definitions', $templateContents);
        self::assertStringContainsString('path-based QR code', $templateContents);
        self::assertStringContainsString('Replacing it with the untouched EU original breaks the renderer.', $templateContents);

        $xpath = $this->createXPathForSvg($templateContents);

        $svgElement = $xpath->query('/svg:svg')?->item(0);
        self::assertInstanceOf(DOMElement::class, $svgElement);
        self::assertSame(self::SVG_NAMESPACE, $svgElement->namespaceURI);
        self::assertSame('0 0 269.29 283.46', $svgElement->getAttribute('viewBox'));

        $textElements = $xpath->query('//svg:text');
        self::assertSame(3, $textElements?->length);

        $fieldToExpectedClipId = [
            'brand' => 'garan-brand-clip',
            'model' => 'garan-model-clip',
            'duration' => 'garan-duration-clip',
        ];

        foreach ($fieldToExpectedClipId as $field => $expectedClipId) {
            $textElement = $xpath->query(sprintf('//svg:text[@data-field="%s"]', $field))?->item(0);

            self::assertInstanceOf(DOMElement::class, $textElement);
            self::assertSame(sprintf('url(#%s)', $expectedClipId), $textElement->getAttribute('clip-path'));
            self::assertSame(
                1,
                $xpath->query(sprintf('//svg:clipPath[@id="%s"]', $expectedClipId))?->length
            );
        }

        $modelText = $xpath->query('//svg:text[@data-field="model"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $modelText);
        self::assertSame('end', $modelText->getAttribute('text-anchor'));
        self::assertSame(262.97, $this->extractTranslateX($modelText->getAttribute('transform')));

        $durationText = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $durationText);
        self::assertSame('end', $durationText->getAttribute('text-anchor'));
        self::assertEqualsWithDelta(115.07, $this->extractTranslateX($durationText->getAttribute('transform')), 0.01);
        self::assertEqualsWithDelta(0.0, $this->extractFirstTspanX($durationText), 0.01);
    }

    public function testGaranTemplateClipRectsUseLocalTextCoordinates(): void
    {
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);
        $xpath = $this->createXPathForSvg($templateContents);

        $this->assertClipRectMatchesLocalTextCoordinates(
            $xpath,
            'brand',
            ['x' => 0.0, 'y' => -11.0, 'width' => 184.0, 'height' => 14.0]
        );
        $this->assertClipRectMatchesLocalTextCoordinates(
            $xpath,
            'model',
            ['x' => -138.97, 'y' => -11.0, 'width' => 138.97, 'height' => 14.0],
            true
        );
        $this->assertClipRectMatchesLocalTextCoordinates(
            $xpath,
            'duration',
            ['x' => -110.0, 'y' => -70.27, 'width' => 116.0, 'height' => 74.75]
        );
    }

    public function testDurationClipEndsBeforeCalendarSymbol(): void
    {
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);
        $xpath = $this->createXPathForSvg($templateContents);

        $durationText = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $durationText);

        $clipPathReference = $durationText->getAttribute('clip-path');
        self::assertSame(1, preg_match('/^url\(#([^)]+)\)$/', $clipPathReference, $matches));

        $clipRect = $xpath->query(sprintf('//svg:clipPath[@id="%s"]/svg:rect', $matches[1]))?->item(0);
        self::assertInstanceOf(DOMElement::class, $clipRect);

        $calendarRect = $xpath->query('//svg:rect[@x="121.47" and @y="127.8"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $calendarRect);

        $durationClipLeftEdge = $this->extractFloatAttribute($clipRect, 'x');
        $durationClipRightEdge = $durationClipLeftEdge + $this->extractFloatAttribute($clipRect, 'width');
        $durationAnchorX = $this->extractFirstTspanX($durationText);
        $durationLabelAnchorX = $this->extractTranslateX($durationText->getAttribute('transform'))
            + $durationAnchorX;
        $durationClipRightEdgeInLabelCoordinates = $durationLabelAnchorX + $durationClipRightEdge;
        $calendarLeftEdge = $this->extractFloatAttribute($calendarRect, 'x');

        self::assertLessThanOrEqual($durationClipRightEdge, $durationAnchorX);
        self::assertGreaterThanOrEqual($durationClipLeftEdge, $durationAnchorX);
        self::assertEqualsWithDelta(6.0, $durationClipRightEdge, 0.01);
        self::assertEqualsWithDelta(115.07, $durationLabelAnchorX, 0.01);
        self::assertEqualsWithDelta(121.07, $durationClipRightEdgeInLabelCoordinates, 0.01);
        self::assertEqualsWithDelta(121.47, $calendarLeftEdge, 0.01);
        self::assertGreaterThan($durationClipRightEdgeInLabelCoordinates, $calendarLeftEdge);
    }

    public function testBuildSvgMarkupKeepsFragmentIdsUniquePerRender(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);

        $firstXPath = $this->createXPathForSvg(
            $this->extractSvgMarkup(
                $builder->buildSvgMarkup($templateContents, 'First GARAN label')
            )
        );
        $secondXPath = $this->createXPathForSvg(
            $this->extractSvgMarkup(
                $builder->buildSvgMarkup($templateContents, 'Second GARAN label')
            )
        );

        $firstIds = $this->collectIds($firstXPath);
        $secondIds = $this->collectIds($secondXPath);

        self::assertNotEmpty($firstIds);
        self::assertNotEmpty($secondIds);
        self::assertSame([], array_values(array_intersect($firstIds, $secondIds)));

        $this->assertClipPathReferencesResolveWithinRender($firstXPath);
        $this->assertClipPathReferencesResolveWithinRender($secondXPath);
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

    private function assertClipPathReferencesResolveWithinRender(DOMXPath $xpath): void
    {
        $ids = array_flip($this->collectIds($xpath));

        foreach ($xpath->query('//svg:text[@data-field]') ?? [] as $textNode) {
            self::assertInstanceOf(DOMElement::class, $textNode);

            $clipPathReference = $textNode->getAttribute('clip-path');
            self::assertSame(1, preg_match('/^url\(#([^)]+)\)$/', $clipPathReference, $matches));
            self::assertArrayHasKey($matches[1], $ids);
        }
    }

    /**
     * @param array{x: float, y: float, width: float, height: float} $expectedRect
     */
    private function assertClipRectMatchesLocalTextCoordinates(
        DOMXPath $xpath,
        string $field,
        array $expectedRect,
        bool $expectLeftAlignedRectToAnchor = false
    ): void {
        $textNode = $xpath->query(sprintf('//svg:text[@data-field="%s"]', $field))?->item(0);
        self::assertInstanceOf(DOMElement::class, $textNode);

        $clipPathReference = $textNode->getAttribute('clip-path');
        self::assertSame(1, preg_match('/^url\(#([^)]+)\)$/', $clipPathReference, $matches));

        $clipPathNode = $xpath->query(sprintf('//svg:clipPath[@id="%s"]', $matches[1]))?->item(0);
        self::assertInstanceOf(DOMElement::class, $clipPathNode);
        self::assertSame('userSpaceOnUse', $clipPathNode->getAttribute('clipPathUnits'));

        $rectNode = $xpath->query('svg:rect', $clipPathNode)?->item(0);
        self::assertInstanceOf(DOMElement::class, $rectNode);

        $rectX = $this->extractFloatAttribute($rectNode, 'x');
        $rectY = $this->extractFloatAttribute($rectNode, 'y');
        $rectWidth = $this->extractFloatAttribute($rectNode, 'width');
        $rectHeight = $this->extractFloatAttribute($rectNode, 'height');

        self::assertEqualsWithDelta($expectedRect['x'], $rectX, 0.01);
        self::assertEqualsWithDelta($expectedRect['y'], $rectY, 0.01);
        self::assertEqualsWithDelta($expectedRect['width'], $rectWidth, 0.01);
        self::assertEqualsWithDelta($expectedRect['height'], $rectHeight, 0.01);

        self::assertLessThanOrEqual(0.0, $rectX);
        self::assertGreaterThanOrEqual(0.0, $rectX + $rectWidth);
        self::assertLessThanOrEqual(0.0, $rectY);
        self::assertGreaterThanOrEqual(0.0, $rectY + $rectHeight);

        if ($expectLeftAlignedRectToAnchor) {
            self::assertLessThan(0.0, $rectX);
            self::assertEqualsWithDelta(0.0, $rectX + $rectWidth, 0.01);
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
