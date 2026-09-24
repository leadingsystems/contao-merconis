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
        $renderer = new GaranLabelRenderer($this->createAssetLocator());

        $renderedSvg = $renderer->render(
            'ACME & Co <Premium>',
            'Model > 42',
            '5.5',
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
            '5.5',
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
            $this->createAssetLocator()->getGaranColourTemplatePath()
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

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }

    private function getTextContent(DOMXPath $xpath, string $query): string
    {
        $textElement = $xpath->query($query)?->item(0);

        self::assertInstanceOf(DOMElement::class, $textElement);

        return trim($textElement->textContent);
    }
}
