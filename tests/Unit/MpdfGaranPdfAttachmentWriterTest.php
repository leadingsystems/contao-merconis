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

        self::assertSame(189.472, round($labelHeightMm, 3));
        self::assertStringContainsString('width="180mm"', $preparedSvg);
        self::assertStringContainsString('height="189.472mm"', $preparedSvg);
        self::assertStringContainsString('preserveAspectRatio="xMinYMin meet"', $preparedSvg);
        self::assertStringNotContainsString('width="95mm"', $preparedSvg);
    }

    public function testLegalGuaranteePdfBuildHtmlUsesFixedMillimetreImageSize(): void
    {
        $writer = new MpdfGaranPdfAttachmentWriter('/tmp');
        $buildHtmlMethod = new ReflectionMethod($writer, 'buildHtml');
        $buildHtmlMethod->setAccessible(true);

        $html = $buildHtmlMethod->invoke(
            $writer,
            '<svg viewBox="0 0 269.29 283.46" width="180mm" height="189.472mm"></svg>',
            189.472
        );

        self::assertStringStartsWith('<svg ', $html);
        self::assertStringContainsString('viewBox="0 0 269.29 283.46"', $html);
        self::assertStringContainsString('width="180mm"', $html);
        self::assertStringContainsString('height="189.472mm"', $html);
        self::assertStringNotContainsString('data:image/svg+xml;base64,', $html);
    }

}
