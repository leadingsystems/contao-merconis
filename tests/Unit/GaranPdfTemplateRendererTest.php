<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfTemplateRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use PHPUnit\Framework\TestCase;

final class GaranPdfTemplateRendererTest extends TestCase
{
    public function testRenderInjectsSnapshotValuesIntoPreparedPdfTemplate(): void
    {
        $renderer = new GaranPdfTemplateRenderer(
            new OfficialGuaranteeAssetLocator(
                dirname(__DIR__, 2),
                new OfficialGuaranteeLanguageResolver(),
            )
        );

        $renderedSvg = $renderer->render('Merconis', 'MX-42', '30.0');

        self::assertStringContainsString('data-field="brand"', $renderedSvg);
        self::assertStringContainsString('data-field="model"', $renderedSvg);
        self::assertStringContainsString('data-field="duration"', $renderedSvg);
        self::assertStringContainsString('>Merconis<', $renderedSvg);
        self::assertStringContainsString('>MX-42<', $renderedSvg);
        self::assertStringContainsString('>30<', $renderedSvg);
        self::assertStringNotContainsString('Brand/Trademark', $renderedSvg);
        self::assertStringNotContainsString('Model identifier', $renderedSvg);

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadXML($renderedSvg);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        self::assertSame('9px', $this->getTextElement($xpath, 'brand')->getAttribute('font-size'));
        self::assertSame('9px', $this->getTextElement($xpath, 'model')->getAttribute('font-size'));
        self::assertSame('80px', $this->getTextElement($xpath, 'duration')->getAttribute('font-size'));
    }

    public function testRenderReducesPdfFontSizeOnlyWhenTheValueWouldOverflow(): void
    {
        $renderer = new GaranPdfTemplateRenderer(
            new OfficialGuaranteeAssetLocator(
                dirname(__DIR__, 2),
                new OfficialGuaranteeLanguageResolver(),
            )
        );

        $renderedSvg = $renderer->render(str_repeat('M', 40), str_repeat('W', 25), '2.5');

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadXML($renderedSvg);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        $brandElement = $this->getTextElement($xpath, 'brand');
        $modelElement = $this->getTextElement($xpath, 'model');
        $durationElement = $this->getTextElement($xpath, 'duration');

        self::assertSame(str_repeat('M', 40), trim($brandElement->textContent));
        self::assertSame(str_repeat('W', 25), trim($modelElement->textContent));
        self::assertSame('2,5', trim($durationElement->textContent));

        self::assertLessThan(9.0, $this->getFontSizeValue($brandElement));
        self::assertLessThan(9.0, $this->getFontSizeValue($modelElement));
        self::assertLessThan(80.0, $this->getFontSizeValue($durationElement));
    }

    private function getTextElement(DOMXPath $xpath, string $field): DOMElement
    {
        $textElement = $xpath->query(sprintf('//svg:text[@data-field="%s"]', $field))?->item(0);

        self::assertInstanceOf(DOMElement::class, $textElement);

        return $textElement;
    }

    private function getFontSizeValue(DOMElement $textElement): float
    {
        return (float) rtrim($textElement->getAttribute('font-size'), 'px');
    }
}
