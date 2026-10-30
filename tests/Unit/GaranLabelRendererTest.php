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

final class GaranLabelRendererTest extends TestCase
{
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
            $this->getTextContent($xpath, '//svg:text[@transform="translate(6.32 74.52)"]')
        );
        self::assertSame(
            'Model > 42',
            $this->getTextContent($xpath, '//svg:text[@transform="translate(196.75 74.52)"]')
        );
        self::assertSame(
            '2,5',
            $this->getTextContent($xpath, '//svg:text[@transform="translate(5.07 150.57)"]')
        );

        self::assertStringNotContainsString('Brand/', $renderedSvg);
        self::assertStringNotContainsString('Model identifier', $renderedSvg);
        self::assertStringContainsString('viewBox="0 0 269.29 283.46"', $renderedSvg);
        self::assertStringContainsString('ACME &amp; Co &lt;Premium&gt;', $renderedSvg);
        self::assertStringContainsString('id="clippath-8"', $renderedSvg);
        self::assertStringContainsString('M229.14,0v39.02', $renderedSvg);
        self::assertStringNotContainsString('garan-label-nested', $renderedSvg);

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
        self::assertSame(
            $templateXpath->query('//svg:image')?->length,
            $xpath->query('//svg:image')?->length
        );
        self::assertSame(3, $xpath->query('//svg:text')?->length);
        self::assertSame(3, $templateXpath->query('//svg:text')?->length);
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
        $targetDirectory = $projectDir . '/src/Resources/public/legal-guarantee/garan/' . $version;

        mkdir($targetDirectory, 0777, true);

        $templateContents = (string) file_get_contents(
            dirname(__DIR__, 2) . '/src/Resources/public/legal-guarantee/garan/v1.0/garan-label-colour.svg'
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
}
