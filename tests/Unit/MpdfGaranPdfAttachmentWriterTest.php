<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\MpdfGaranPdfAttachmentWriter;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class MpdfGaranPdfAttachmentWriterTest extends TestCase
{
    public function testLegalGuaranteePdfPreparesSvgWithExplicitMillimetreDimensions(): void
    {
        $writer = new MpdfGaranPdfAttachmentWriter('/tmp');
        $prepareSvgForPdfMethod = new ReflectionMethod($writer, 'prepareSvgForPdf');
        $prepareSvgForPdfMethod->setAccessible(true);

        [$preparedSvg, $labelHeightMm] = $prepareSvgForPdfMethod->invoke(
            $writer,
            '<svg viewBox="0 0 269.29 283.46"></svg>'
        );

        self::assertSame(124.209, round($labelHeightMm, 3));
        self::assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $preparedSvg);
        self::assertStringContainsString('width="118mm"', $preparedSvg);
        self::assertStringContainsString('height="124.209mm"', $preparedSvg);
        self::assertStringContainsString('preserveAspectRatio="xMidYMin meet"', $preparedSvg);
        self::assertStringNotContainsString('width="180mm"', $preparedSvg);
    }

    public function testLegalGuaranteePdfBuildHtmlIncludesNamesAndLink(): void
    {
        $writer = new MpdfGaranPdfAttachmentWriter('/tmp');
        $buildHtmlMethod = new ReflectionMethod($writer, 'buildHtml');
        $buildHtmlMethod->setAccessible(true);

        $html = $buildHtmlMethod->invoke(
            $writer,
            '<svg viewBox="0 0 269.29 283.46" width="118mm" height="124.209mm"></svg>',
            ['Produkt Alpha (Variante Blau)', 'Produkt Alpha (Variante Gruen)'],
            'EU information',
            'https://europa.example.test/garan'
        );

        self::assertStringStartsWith('<div ', $html);
        self::assertStringContainsString('viewBox="0 0 269.29 283.46"', $html);
        self::assertStringContainsString('width="118mm"', $html);
        self::assertStringContainsString('height="124.209mm"', $html);
        self::assertStringContainsString('Produkt Alpha (Variante Blau)', $html);
        self::assertStringContainsString('Produkt Alpha (Variante Gruen)', $html);
        self::assertStringContainsString('href="https://europa.example.test/garan"', $html);
        self::assertStringContainsString('EU information', $html);
        self::assertStringNotContainsString('data:image/svg+xml;base64,', $html);
    }

}
