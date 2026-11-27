<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranNestedLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\Tests\Support\TrueTypeAdvanceWidthReader;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/TrueTypeAdvanceWidthReader.php';

/**
 * Aufbereitete Nested-Vorlage der GARAN-Version `v1.0` (Implementierungsentscheidung 0032).
 */
final class GaranNestedLabelTemplateIntegrationTest extends TestCase
{
    private const TEMPLATE_PATH = __DIR__ . '/../../src/Resources/legal-guarantee/garan/v1.0/garan-label-nested.svg';
    private const FONT_PATH = __DIR__ . '/../../src/Resources/public/legal-guarantee/fonts/inter/Inter-ExtraBold.ttf';
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    private const DURATION_ANCHOR_X = 67.55;
    // Anfang des amtlichen Platzhalters `XX`; kein zulässiger Wert beginnt links davon.
    private const DURATION_LEFT_EDGE_X = 10.39;
    private const CALENDAR_LEFT_X = 70.87;
    private const MINIMUM_DURATION_MONTHS = 30;
    private const MAXIMUM_DURATION_MONTHS = 360;
    private const DURATION_MONTHS_STEP = 6;

    public function testNestedTemplateContainsExpectedStructureAndWarningComment(): void
    {
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);

        self::assertSame(1, preg_match('/<!--(.*?)-->/s', $templateContents, $commentMatches));
        $warningComment = $commentMatches[1];

        self::assertStringContainsString('technically prepared', $warningComment);
        self::assertStringContainsString('untouched EU original', $warningComment);
        self::assertStringContainsString('inline styles', $warningComment);
        self::assertStringContainsString('data-field attribute', $warningComment);
        self::assertStringContainsString('Replacing it with the untouched EU original breaks the renderer.', $warningComment);
        self::assertStringNotContainsStringIgnoringCase('clip', $warningComment);

        $xpath = $this->createXPathForSvg($templateContents);

        $svgElement = $xpath->query('/svg:svg')?->item(0);
        self::assertInstanceOf(DOMElement::class, $svgElement);
        self::assertSame(self::SVG_NAMESPACE, $svgElement->namespaceURI);
        self::assertSame('0 0 368.5 56.69', $svgElement->getAttribute('viewBox'));

        self::assertSame(1, $xpath->query('//svg:text')?->length);
        self::assertSame(1, $xpath->query('//svg:text[@data-field]')?->length);

        $durationText = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $durationText);
        self::assertSame('translate(67.55 46.65)', $durationText->getAttribute('transform'));
        self::assertSame('end', $durationText->getAttribute('text-anchor'));
        self::assertSame('Inter-ExtraBold, Inter', $durationText->getAttribute('font-family'));
        self::assertSame('41.56px', $durationText->getAttribute('font-size'));
        self::assertSame('700', $durationText->getAttribute('font-weight'));
        self::assertSame('-.02em', $durationText->getAttribute('letter-spacing'));
        self::assertSame('#231f20', $durationText->getAttribute('fill'));

        $tspanElements = $durationText->getElementsByTagNameNS(self::SVG_NAMESPACE, 'tspan');
        self::assertSame(1, $tspanElements->length);
        self::assertSame('0', $tspanElements->item(0)?->getAttribute('x'));
        self::assertSame('0', $tspanElements->item(0)?->getAttribute('y'));
    }

    public function testNestedTemplateUsesInlineStylesInsteadOfClasses(): void
    {
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);
        $xpath = $this->createXPathForSvg($templateContents);

        self::assertSame(0, $xpath->query('//svg:style')?->length);
        self::assertSame(0, $xpath->query('//svg:defs')?->length);
        self::assertSame(0, $xpath->query('//*[@class]')?->length);
        self::assertStringNotContainsString('cls-', $templateContents);

        $styledElements = $xpath->query('//svg:rect | //svg:line | //svg:path | //svg:polygon | //svg:text');
        self::assertGreaterThan(0, $styledElements?->length);

        foreach ($styledElements ?? [] as $styledElement) {
            self::assertInstanceOf(DOMElement::class, $styledElement);
            self::assertTrue($styledElement->hasAttribute('fill'), $styledElement->getNodePath() ?? '');
        }

        foreach ($xpath->query('//svg:line') ?? [] as $lineElement) {
            self::assertInstanceOf(DOMElement::class, $lineElement);
            self::assertSame('#231f20', $lineElement->getAttribute('stroke'));
            self::assertSame('.5px', $lineElement->getAttribute('stroke-width'));
        }
    }

    public function testNestedTemplateContainsNoClipping(): void
    {
        $templateContents = (string) file_get_contents(self::TEMPLATE_PATH);
        $xpath = $this->createXPathForSvg($templateContents);

        self::assertSame(0, $xpath->query('//svg:clipPath')?->length);
        self::assertSame(0, $xpath->query('//*[@clip-path]')?->length);
        self::assertStringNotContainsString('clip-path', $templateContents);
        self::assertStringNotContainsString('<clipPath', $templateContents);
    }

    public function testDurationAnchorLiesBeforeCalendarSymbolWithTheProportionalGap(): void
    {
        $xpath = $this->createXPathForSvg((string) file_get_contents(self::TEMPLATE_PATH));
        $durationText = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);
        $calendarRect = $xpath->query('//svg:rect[@x="70.87" and @y="34.81"]')?->item(0);

        self::assertInstanceOf(DOMElement::class, $durationText);
        self::assertInstanceOf(DOMElement::class, $calendarRect);

        $anchorX = $this->extractTranslateX($durationText->getAttribute('transform'));

        self::assertEqualsWithDelta(self::DURATION_ANCHOR_X, $anchorX, 0.001);
        self::assertEqualsWithDelta(self::CALENDAR_LEFT_X, (float) $calendarRect->getAttribute('x'), 0.001);
        // Abstand im Verhältnis zur Schriftgröße wie beim vollen Label (6.40 bei 80).
        self::assertEqualsWithDelta(6.40 * 41.56 / 80, self::CALENDAR_LEFT_X - $anchorX, 0.01);
    }

    public function testAllAdmissibleDurationsFitBetweenLeftEdgeAndAnchor(): void
    {
        $advanceWidthsInEm = (new TrueTypeAdvanceWidthReader())->readAdvanceWidthsInEm(self::FONT_PATH);
        $renderer = new GaranNestedLabelRenderer(
            new OfficialGuaranteeAssetLocator(dirname(__DIR__, 2), new OfficialGuaranteeLanguageResolver()),
            OfficialGuaranteeAssetLocator::GARAN_VERSION
        );
        $checkedValues = [];

        for (
            $durationMonths = self::MINIMUM_DURATION_MONTHS;
            $durationMonths <= self::MAXIMUM_DURATION_MONTHS;
            $durationMonths += self::DURATION_MONTHS_STEP
        ) {
            $durationText = $this->createXPathForSvg($renderer->render((string) $durationMonths))
                ->query('//svg:text[@data-field="duration"]')?->item(0);
            self::assertInstanceOf(DOMElement::class, $durationText);

            $displayedValue = $durationText->textContent;
            $fontSize = $this->extractPixelValue($durationText->getAttribute('font-size'));
            $widthInEm = 0.0;

            foreach (mb_str_split($displayedValue) as $character) {
                $codePoint = mb_ord($character);
                self::assertArrayHasKey($codePoint, $advanceWidthsInEm, sprintf('Glyph for "%s"', $character));
                $widthInEm += $advanceWidthsInEm[$codePoint];
            }

            $width = $widthInEm * $fontSize;
            $anchorX = $this->extractTranslateX($durationText->getAttribute('transform'));

            self::assertGreaterThanOrEqual(
                self::DURATION_LEFT_EDGE_X,
                $anchorX - $width,
                sprintf('"%s" (%d months) at %spx starts left of the left edge', $displayedValue, $durationMonths, $fontSize)
            );
            self::assertLessThan(self::CALENDAR_LEFT_X, $anchorX);

            $checkedValues[] = $displayedValue;
        }

        self::assertCount(56, $checkedValues);
        self::assertCount(28, array_filter($checkedValues, static fn (string $value): bool => !str_contains($value, ',')));
        self::assertContains('3', $checkedValues);
        self::assertContains('30', $checkedValues);
        self::assertContains('2,5', $checkedValues);
        self::assertContains('29,5', $checkedValues);
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

    private function extractTranslateX(string $transform): float
    {
        self::assertSame(1, preg_match('/^translate\(([0-9.]+)\s+[0-9.]+\)$/', $transform, $matches));

        return (float) $matches[1];
    }

    private function extractPixelValue(string $value): float
    {
        self::assertSame(1, preg_match('/^([0-9.]+)px$/', $value, $matches));

        return (float) $matches[1];
    }
}
