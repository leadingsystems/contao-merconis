<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;

final class MpdfGaranPdfAttachmentWriter implements GaranPdfAttachmentWriterInterface
{
    private const RELATIVE_OUTPUT_DIRECTORY = 'var/tmp/merconis/legal-guarantee';
    private const RELATIVE_MPDF_TEMP_DIRECTORY = 'var/tmp/merconis/mpdf';

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function write(string $svgMarkup, string $preferredBaseName): array
    {
        $outputDirectory = $this->projectDir . '/' . self::RELATIVE_OUTPUT_DIRECTORY;
        $mpdfTempDirectory = $this->projectDir . '/' . self::RELATIVE_MPDF_TEMP_DIRECTORY;

        if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
            throw new RuntimeException(sprintf('Could not create GARAN output directory: %s', $outputDirectory));
        }

        if (!is_dir($mpdfTempDirectory) && !mkdir($mpdfTempDirectory, 0775, true) && !is_dir($mpdfTempDirectory)) {
            throw new RuntimeException(sprintf('Could not create mPDF temp directory: %s', $mpdfTempDirectory));
        }

        $relativeFilePath = self::RELATIVE_OUTPUT_DIRECTORY . '/' . $this->createFileName($preferredBaseName, $svgMarkup);
        $absoluteFilePath = $this->projectDir . '/' . $relativeFilePath;
        $svgDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgMarkup);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'tempDir' => $mpdfTempDirectory,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetMargins(0, 0, 0);
        $mpdf->SetAutoPageBreak(false, 0);
        $mpdf->WriteHTML(
            $this->buildHtml($svgDataUri),
            HTMLParserMode::DEFAULT_MODE
        );
        $mpdf->Output($absoluteFilePath, Destination::FILE);

        return [
            'relativePath' => $relativeFilePath,
            'cleanupAfterSend' => true,
        ];
    }

    private function createFileName(string $preferredBaseName, string $svgMarkup): string
    {
        $baseName = preg_replace('/\.pdf$/i', '', $preferredBaseName) ?? $preferredBaseName;
        $suffix = substr(sha1($svgMarkup), 0, 12);

        return sprintf('%s-%s.pdf', $baseName, $suffix);
    }

    private function buildHtml(string $svgDataUri): string
    {
        return <<<HTML
<style>
@page {
    margin: 0;
    size: 95mm 100mm;
}
body {
    margin: 0;
    padding: 0;
}
img {
    display: block;
    width: 95mm;
    height: auto;
}
</style>
<img src="{$svgDataUri}" alt="">
HTML;
    }
}
