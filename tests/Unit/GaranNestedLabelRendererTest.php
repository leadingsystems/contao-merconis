<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranNestedLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GaranNestedLabelRendererTest extends TestCase
{
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function provideDurationValuesAndFontSizes(): iterable
    {
        yield 'minimum decimal' => ['30', '2,5', '24.5px'];
        yield 'worst-case decimal' => ['246', '20,5', '24.5px'];
        yield 'maximum decimal' => ['354', '29,5', '24.5px'];
        yield 'minimum integer' => ['36', '3', '41.56px'];
        yield 'two-digit integer' => ['120', '10', '41.56px'];
        yield 'worst-case and maximum integer' => ['360', '30', '41.56px'];
        yield 'surrounding whitespace' => [' 42 ', '3,5', '24.5px'];
    }

    #[DataProvider('provideDurationValuesAndFontSizes')]
    public function testRenderSetsDurationValueAndFontSizeOfTheCase(
        string $durationMonths,
        string $expectedText,
        string $expectedFontSize
    ): void {
        $renderer = new GaranNestedLabelRenderer($this->createAssetLocator(), 'v1.0');

        $xpath = $this->createXPath($renderer->render($durationMonths));
        $durationElement = $this->getDurationElement($xpath);
        $tspanElements = $durationElement->getElementsByTagNameNS(self::SVG_NAMESPACE, 'tspan');

        self::assertSame(1, $xpath->query('//svg:text')?->length);
        self::assertSame(1, $tspanElements->length);
        self::assertSame($expectedText, $tspanElements->item(0)?->textContent);
        self::assertSame($expectedText, $durationElement->textContent);
        self::assertSame($expectedFontSize, $durationElement->getAttribute('font-size'));
        self::assertSame('translate(67.55 46.65)', $durationElement->getAttribute('transform'));
        self::assertSame('end', $durationElement->getAttribute('text-anchor'));
        self::assertSame('-.02em', $durationElement->getAttribute('letter-spacing'));
        self::assertSame('Inter-ExtraBold, Inter', $durationElement->getAttribute('font-family'));
        self::assertSame('0', $tspanElements->item(0)?->getAttribute('x'));
        self::assertSame('0', $tspanElements->item(0)?->getAttribute('y'));
    }

    public function testRenderChangesNothingButTheDurationTextNode(): void
    {
        $assetLocator = $this->createAssetLocator();
        $renderer = new GaranNestedLabelRenderer($assetLocator, 'v1.0');

        $renderedDocument = $this->loadDocument($renderer->render('246'));
        $templateDocument = $this->loadDocument(
            (string) file_get_contents($assetLocator->getGaranNestedTemplatePath('v1.0'))
        );

        self::assertSame(
            $this->serialiseWithoutDurationTextNode($templateDocument),
            $this->serialiseWithoutDurationTextNode($renderedDocument)
        );
        self::assertSame('0 0 368.5 56.69', $renderedDocument->documentElement?->getAttribute('viewBox'));
    }

    public function testRenderInsertsDurationOnlyAsTextContent(): void
    {
        $renderer = new GaranNestedLabelRenderer($this->createAssetLocator(), 'v1.0');

        $renderedSvg = $renderer->render('60');

        self::assertStringContainsString('<tspan x="0" y="0">5</tspan>', $renderedSvg);
        self::assertStringNotContainsString('>XX<', $renderedSvg);
        self::assertStringNotContainsString('class="cls-', $renderedSvg);
        self::assertStringNotContainsString('clip-path', $renderedSvg);
        self::assertStringNotContainsString('<style', $renderedSvg);
    }

    public function testRenderNormalisesManipulatedTemplateTspanCoordinates(): void
    {
        $projectDir = $this->createTempProjectDirWithNestedTemplateOverride(
            'v1.0',
            '<tspan x="0" y="0">XX</tspan>',
            '<tspan x="42" y="99">XX</tspan><tspan x="7" y="7">YY</tspan>'
        );
        $renderer = new GaranNestedLabelRenderer(
            new OfficialGuaranteeAssetLocator($projectDir, new OfficialGuaranteeLanguageResolver()),
            'v1.0'
        );

        $durationElement = $this->getDurationElement($this->createXPath($renderer->render('36')));
        $tspanElements = $durationElement->getElementsByTagNameNS(self::SVG_NAMESPACE, 'tspan');

        self::assertSame(1, $tspanElements->length);
        self::assertSame('0', $tspanElements->item(0)?->getAttribute('x'));
        self::assertSame('0', $tspanElements->item(0)?->getAttribute('y'));
        self::assertSame('3', $durationElement->textContent);
    }

    public function testRenderFailsWhenTheDurationTextNodeIsMissing(): void
    {
        $projectDir = $this->createTempProjectDirWithNestedTemplateOverride(
            'v1.0',
            ' data-field="duration"',
            ''
        );
        $renderer = new GaranNestedLabelRenderer(
            new OfficialGuaranteeAssetLocator($projectDir, new OfficialGuaranteeLanguageResolver()),
            'v1.0'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required GARAN nested text node not found: duration');

        $renderer->render('36');
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideEmptyDurations(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['  '];
    }

    #[DataProvider('provideEmptyDurations')]
    public function testRenderFailsForEmptyDuration(string $durationMonths): void
    {
        $renderer = new GaranNestedLabelRenderer($this->createAssetLocator(), 'v1.0');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required GARAN field is empty: duration');

        $renderer->render($durationMonths);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideInvalidDurations(): iterable
    {
        yield 'formatted years' => ['2,5'];
        yield 'decimal point' => ['30.0'];
        yield 'negative' => ['-36'];
        yield 'zero' => ['0'];
        yield 'text' => ['XX'];
    }

    #[DataProvider('provideInvalidDurations')]
    public function testRenderFailsForInvalidDuration(string $durationMonths): void
    {
        $renderer = new GaranNestedLabelRenderer($this->createAssetLocator(), 'v1.0');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GARAN duration is invalid: ' . $durationMonths);

        $renderer->render($durationMonths);
    }

    public function testRenderUsesTemplateDirectoryOfRegisteredVersion(): void
    {
        $projectDir = $this->createTempProjectDirWithNestedTemplateOverride(
            'v1.1',
            'viewBox="0 0 368.5 56.69"',
            'viewBox="9 9 9 9"'
        );
        $renderer = new GaranNestedLabelRenderer(
            new OfficialGuaranteeAssetLocator($projectDir, new OfficialGuaranteeLanguageResolver()),
            'v1.1'
        );

        self::assertSame('v1.1', $renderer->getVersion());
        self::assertStringContainsString('viewBox="9 9 9 9"', $renderer->render('36'));
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }

    private function createTempProjectDirWithNestedTemplateOverride(
        string $version,
        string $search,
        string $replace
    ): string {
        $projectDir = sys_get_temp_dir() . '/merconis-garan-nested-label-' . uniqid('', true);
        $targetDirectory = $projectDir . '/src/Resources/legal-guarantee/garan/' . $version;
        $templateContents = (string) file_get_contents(
            dirname(__DIR__, 2) . '/src/Resources/legal-guarantee/garan/v1.0/garan-label-nested.svg'
        );

        self::assertStringContainsString($search, $templateContents);

        mkdir($targetDirectory, 0777, true);
        file_put_contents(
            $targetDirectory . '/garan-label-nested.svg',
            str_replace($search, $replace, $templateContents)
        );

        return $projectDir;
    }

    private function loadDocument(string $svgMarkup): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;

        self::assertTrue($document->loadXML($svgMarkup));

        return $document;
    }

    private function createXPath(string $svgMarkup): DOMXPath
    {
        $xpath = new DOMXPath($this->loadDocument($svgMarkup));
        $xpath->registerNamespace('svg', self::SVG_NAMESPACE);

        return $xpath;
    }

    private function getDurationElement(DOMXPath $xpath): DOMElement
    {
        $durationElement = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);

        self::assertInstanceOf(DOMElement::class, $durationElement);

        return $durationElement;
    }

    private function serialiseWithoutDurationTextNode(DOMDocument $document): string
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', self::SVG_NAMESPACE);
        $durationElement = $this->getDurationElement($xpath);
        $durationElement->parentNode?->removeChild($durationElement);

        return (string) $document->saveXML($document->documentElement);
    }
}
