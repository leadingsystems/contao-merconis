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

        self::assertStringContainsString('data:image/svg+xml;base64,', $html);
        self::assertStringContainsString('width:180mm;', $html);
        self::assertStringContainsString('height:189.472mm;', $html);
        self::assertStringNotContainsString('width:100%;', $html);
    }

    public function testLegalGuaranteePdfWriteCreatesAnA4PdfFile(): void
    {
        $projectDirectory = sys_get_temp_dir() . '/merconis-garan-pdf-' . uniqid('', true);
        $writer = new MpdfGaranPdfAttachmentWriter($projectDirectory);
        $svgMarkup = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 269.29 283.46">
  <rect width="269.29" height="283.46" fill="#ffffff"/>
  <text x="12" y="24">GARAN test</text>
</svg>
SVG;

        try {
            $result = $writer->write($svgMarkup, 'garan-test.pdf');
            $pdfPath = $projectDirectory . '/' . $result['relativePath'];
            $pdfContents = (string) file_get_contents($pdfPath);

            self::assertFileExists($pdfPath);
            self::assertMatchesRegularExpression(
                '/\/MediaBox\s*\[\s*0\s+0\s+595(?:\.\d+)?\s+841(?:\.\d+)?\s*\]/',
                $pdfContents
            );
        } finally {
            $this->removeDirectory($projectDirectory);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
