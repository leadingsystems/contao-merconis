<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\MpdfGaranPdfAttachmentWriter;
use PHPUnit\Framework\TestCase;

final class MpdfGaranPdfWriteTest extends TestCase
{
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
