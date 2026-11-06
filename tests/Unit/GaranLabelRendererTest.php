<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GaranLabelRendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['TL_CONFIG']['garanLabelTruncationLength']);
    }

    public function testRenderReplacesOnlyTheThreeEditableTextFields(): void
    {
        $renderer = new GaranLabelRenderer($this->createAssetLocator(), 'v1.0');

        $renderedSvg = $renderer->render(
            'ACME & Co <Premium>',
            'Model > 42',
            '2.5',
            ','
        );

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadXML($renderedSvg);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        self::assertSame(
            'ACME & Co <Premium>',
            $this->getTextContent($xpath, '//svg:text[@data-field="brand"]')
        );
        self::assertSame(
            'Model > 42',
            $this->getTextContent($xpath, '//svg:text[@data-field="model"]')
        );
        self::assertSame(
            '2,5',
            $this->getTextContent($xpath, '//svg:text[@data-field="duration"]')
        );

        self::assertStringNotContainsString('Brand/', $renderedSvg);
        self::assertStringNotContainsString('Model identifier', $renderedSvg);
        self::assertStringContainsString('viewBox="0 0 269.29 283.46"', $renderedSvg);
        self::assertStringContainsString('ACME &amp; Co &lt;Premium&gt;', $renderedSvg);
        self::assertStringContainsString('data-field="brand"', $renderedSvg);
        self::assertStringContainsString('data-field="model"', $renderedSvg);
        self::assertStringContainsString('data-field="duration"', $renderedSvg);
        self::assertStringContainsString('text-anchor="end"', $renderedSvg);
        self::assertStringContainsString('id="garan-duration-clip"', $renderedSvg);
        self::assertStringContainsString('M229.14,0v39.02', $renderedSvg);
        self::assertStringNotContainsString('garan-label-nested', $renderedSvg);
        self::assertStringNotContainsString('class="cls-', $renderedSvg);

        $templateContents = (string) file_get_contents(
            $this->createAssetLocator()->getGaranColourTemplatePath('v1.0')
        );
        $templateDocument = new DOMDocument('1.0', 'UTF-8');
        $templateDocument->loadXML($templateContents);
        $templateXpath = new DOMXPath($templateDocument);
        $templateXpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        self::assertSame(
            $templateXpath->query('//svg:clipPath')?->length,
            $xpath->query('//svg:clipPath')?->length
        );
        self::assertSame(3, $xpath->query('//svg:clipPath')?->length);
        self::assertSame(0, $xpath->query('//svg:style')?->length);
        self::assertSame(3, $xpath->query('//svg:text')?->length);
        self::assertSame(3, $templateXpath->query('//svg:text')?->length);
    }

    public function testRenderTruncatesBrandAndModelUsingConfiguredThreshold(): void
    {
        $GLOBALS['TL_CONFIG']['garanLabelTruncationLength'] = '5';
        $renderer = new GaranLabelRenderer($this->createAssetLocator(), 'v1.0');

        $renderedSvg = $renderer->render(
            'ABCDEFGHIJ',
            '1234567890',
            '2.5',
            ','
        );

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadXML($renderedSvg);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        self::assertSame('ABCDE...', $this->getTextContent($xpath, '//svg:text[@data-field="brand"]'));
        self::assertSame('12345...', $this->getTextContent($xpath, '//svg:text[@data-field="model"]'));
    }

    public function testRenderUsesCanonicalAnchorsForBrandModelAndDuration(): void
    {
        $renderer = new GaranLabelRenderer($this->createAssetLocator(), 'v1.0');

        $renderedSvg = $renderer->render('ACME', 'X100', '20.5', ',');

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadXML($renderedSvg);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        $brandElement = $xpath->query('//svg:text[@data-field="brand"]')?->item(0);
        $modelElement = $xpath->query('//svg:text[@data-field="model"]')?->item(0);
        $durationElement = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);

        self::assertInstanceOf(DOMElement::class, $brandElement);
        self::assertInstanceOf(DOMElement::class, $modelElement);
        self::assertInstanceOf(DOMElement::class, $durationElement);
        self::assertSame('0', $this->getFirstTspanAttribute($brandElement, 'x'));
        self::assertSame('0', $this->getFirstTspanAttribute($brandElement, 'y'));
        self::assertSame('0', $this->getFirstTspanAttribute($modelElement, 'x'));
        self::assertSame('0', $this->getFirstTspanAttribute($modelElement, 'y'));
        self::assertSame('0', $this->getFirstTspanAttribute($durationElement, 'x'));
        self::assertSame('0', $this->getFirstTspanAttribute($durationElement, 'y'));
        self::assertSame('end', $durationElement->getAttribute('text-anchor'));
        self::assertSame('translate(115.07 150.57)', $durationElement->getAttribute('transform'));
    }

    public function testRenderNormalisesManipulatedTemplateTspanCoordinates(): void
    {
        $projectDir = $this->createTempProjectDirWithGaranTemplateOverride(
            'v1.0',
            'data-field="duration" clip-path="url(#garan-duration-clip)"><tspan x="0" y="0">XX</tspan>',
            'data-field="duration" clip-path="url(#garan-duration-clip)"><tspan x="42" y="99">XX</tspan>'
        );
        $renderer = new GaranLabelRenderer(
            new OfficialGuaranteeAssetLocator($projectDir, new OfficialGuaranteeLanguageResolver()),
            'v1.0'
        );

        $renderedSvg = $renderer->render('ACME', 'X100', '20.5', ',');

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadXML($renderedSvg);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        $durationElement = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);
        self::assertInstanceOf(DOMElement::class, $durationElement);
        self::assertSame('0', $this->getFirstTspanAttribute($durationElement, 'x'));
        self::assertSame('0', $this->getFirstTspanAttribute($durationElement, 'y'));
    }

    public function testRenderDoesNotTruncateShortValues(): void
    {
        $renderer = new GaranLabelRenderer($this->createAssetLocator(), 'v1.0');

        $renderedSvg = $renderer->render('Kurz', 'Modell', '2.5', ',');

        self::assertStringContainsString('>Kurz<', $renderedSvg);
        self::assertStringContainsString('>Modell<', $renderedSvg);
        self::assertStringNotContainsString('Kurz...', $renderedSvg);
        self::assertStringNotContainsString('Modell...', $renderedSvg);
    }

    /**
     * @dataProvider provideDurationFontSizes
     */
    public function testRenderUsesExpectedDurationFontSize(
        string $durationText,
        string $expectedText,
        string $expectedFontSize
    ): void {
        $renderer = new GaranLabelRenderer($this->createAssetLocator(), 'v1.0');

        $renderedSvg = $renderer->render('Brand', 'Model', $durationText, ',');

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadXML($renderedSvg);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        $durationElement = $xpath->query('//svg:text[@data-field="duration"]')?->item(0);

        self::assertInstanceOf(DOMElement::class, $durationElement);
        self::assertSame($expectedText, trim($durationElement->textContent));
        self::assertSame($expectedFontSize, $durationElement->getAttribute('font-size'));
    }

    /**
     * @dataProvider provideEmptyRequiredValues
     */
    public function testRenderRejectsEmptyRequiredValues(
        string $brand,
        string $modelIdentifier,
        string $durationText,
        string $expectedField
    ): void
    {
        $renderer = new GaranLabelRenderer($this->createAssetLocator(), 'v1.0');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required GARAN field is empty: ' . $expectedField);

        $renderer->render($brand, $modelIdentifier, $durationText, ',');
    }

    public function testRenderUsesTemplateDirectoryOfRegisteredVersion(): void
    {
        $projectDir = $this->createTempProjectDirWithGaranTemplateOverride(
            'v1.1',
            'viewBox="0 0 269.29 283.46"',
            'viewBox="9 9 9 9"'
        );
        $renderer = new GaranLabelRenderer(
            new OfficialGuaranteeAssetLocator($projectDir, new OfficialGuaranteeLanguageResolver()),
            'v1.1'
        );

        $renderedSvg = $renderer->render('ACME', 'MX-42', '2.5', ',');

        self::assertStringContainsString('viewBox="9 9 9 9"', $renderedSvg);
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }

    private function createTempProjectDirWithGaranTemplateOverride(
        string $version,
        string $search,
        string $replace
    ): string {
        $projectDir = sys_get_temp_dir() . '/merconis-garan-label-' . uniqid('', true);
        $targetDirectory = $projectDir . '/src/Resources/legal-guarantee/garan/' . $version;

        mkdir($targetDirectory, 0777, true);

        $templateContents = (string) file_get_contents(
            dirname(__DIR__, 2) . '/src/Resources/legal-guarantee/garan/v1.0/garan-label-colour.svg'
        );
        file_put_contents(
            $targetDirectory . '/garan-label-colour.svg',
            str_replace($search, $replace, $templateContents)
        );

        return $projectDir;
    }

    private function getTextContent(DOMXPath $xpath, string $query): string
    {
        $textElement = $xpath->query($query)?->item(0);

        self::assertInstanceOf(DOMElement::class, $textElement);

        return trim($textElement->textContent);
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function provideEmptyRequiredValues(): iterable
    {
        yield 'brand' => ['', 'Model', '2.5', 'brand'];
        yield 'model' => ['Brand', '', '2.5', 'model'];
        yield 'duration' => ['Brand', 'Model', '', 'duration'];
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function provideDurationFontSizes(): iterable
    {
        yield 'minimum decimal' => ['2.5', '2,5', '50.5px'];
        yield 'worst-case decimal' => ['20.5', '20,5', '50.5px'];
        yield 'maximum decimal' => ['29.5', '29,5', '50.5px'];
        yield 'minimum integer' => ['3', '3', '80px'];
        yield 'maximum integer' => ['30', '30', '80px'];
    }

    private function getFirstTspanAttribute(DOMElement $textElement, string $attributeName): string
    {
        $tspanElement = $textElement->getElementsByTagNameNS('http://www.w3.org/2000/svg', 'tspan')->item(0);

        self::assertInstanceOf(DOMElement::class, $tspanElement);

        return $tspanElement->getAttribute($attributeName);
    }
}
