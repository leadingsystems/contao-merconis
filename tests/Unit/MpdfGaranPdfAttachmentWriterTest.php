<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\MpdfGaranPdfAttachmentWriter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;

final class MpdfGaranPdfAttachmentWriterTest extends TestCase
{
    public function testLegalGuaranteePdfSplitsRenderedSvgIntoStaticAndValueParts(): void
    {
        $writer = new MpdfGaranPdfAttachmentWriter('/tmp', new NullLogger());
        $splitRenderedSvgForPdfMethod = new ReflectionMethod($writer, 'splitRenderedSvgForPdf');
        $splitRenderedSvgForPdfMethod->setAccessible(true);

        $result = $splitRenderedSvgForPdfMethod->invoke(
            $writer,
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 269.29 283.46" width="118mm" height="124.209mm">'
            . '<defs><clipPath id="garan-brand-clip"><rect x="0" y="0" width="1" height="1" /></clipPath></defs>'
            . '<rect x="0" y="0" width="269.29" height="283.46" />'
            . '<text data-field="brand" transform="translate(6.32 74.52)" clip-path="url(#garan-brand-clip)"><tspan x="0" y="0">Brand</tspan></text>'
            . '<text data-field="model" transform="translate(262.97 74.52)" text-anchor="end"><tspan x="0" y="0">Model</tspan></text>'
            . '<text data-field="duration" transform="translate(115.07 150.57)" text-anchor="end" font-size="47px"><tspan x="0" y="0">2,5</tspan></text>'
            . '</svg>'
        );

        self::assertSame(124.209, round($result['labelHeightMm'], 3));
        self::assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $result['staticSvgMarkup']);
        self::assertStringContainsString('viewBox="0 0 269.29 283.46"', $result['staticSvgMarkup']);
        self::assertStringNotContainsString('data-field=', $result['staticSvgMarkup']);
        self::assertStringNotContainsString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 269.29 283.46" width=', $result['staticSvgMarkup']);
        self::assertStringNotContainsString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 269.29 283.46" height=', $result['staticSvgMarkup']);
        self::assertStringContainsString('<defs>', $result['valueSvgMarkup']);
        self::assertStringContainsString('data-field="brand"', $result['valueSvgMarkup']);
        self::assertStringContainsString('data-field="model"', $result['valueSvgMarkup']);
        self::assertStringContainsString('data-field="duration"', $result['valueSvgMarkup']);
        self::assertStringContainsString('text-anchor="end"', $result['valueSvgMarkup']);
        self::assertStringContainsString('font-size="47px"', $result['valueSvgMarkup']);
        self::assertSame(3, substr_count($result['valueSvgMarkup'], '<text '));
        self::assertSame(3, substr_count($result['valueSvgMarkup'], '<tspan '));
        self::assertStringNotContainsString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 269.29 283.46" width=', $result['valueSvgMarkup']);
        self::assertStringNotContainsString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 269.29 283.46" height=', $result['valueSvgMarkup']);
    }

    public function testLegalGuaranteePdfBuildLabelHtmlIncludesNamesImagesAndLink(): void
    {
        $writer = new MpdfGaranPdfAttachmentWriter('/tmp', new NullLogger());
        $buildLabelHtmlMethod = new ReflectionMethod($writer, 'buildLabelHtml');
        $buildLabelHtmlMethod->setAccessible(true);

        $html = $buildLabelHtmlMethod->invoke(
            $writer,
            ['Produkt Alpha (Variante Blau)', 'Produkt Alpha (Variante Gruen)'],
            'garanStatic',
            'garanValues0',
            124.209,
            'EU information',
            'https://europa.example.test/garan'
        );

        self::assertStringStartsWith('<div ', $html);
        self::assertStringContainsString('Produkt Alpha (Variante Blau)', $html);
        self::assertStringContainsString('Produkt Alpha (Variante Gruen)', $html);
        self::assertStringContainsString('src="var:garanStatic"', $html);
        self::assertStringContainsString('src="var:garanValues0"', $html);
        self::assertStringContainsString('margin-top:-124.209mm', $html);
        self::assertStringContainsString('href="https://europa.example.test/garan"', $html);
        self::assertStringContainsString('EU information', $html);
        self::assertStringNotContainsString('data:image/svg+xml;base64,', $html);
        self::assertStringNotContainsString('font-size:0.1pt', $html);
    }

    public function testLegalGuaranteePdfBuildNoticeHtmlContainsNoticeWithoutImages(): void
    {
        $writer = new MpdfGaranPdfAttachmentWriter('/tmp', new NullLogger());
        $buildNoticeHtmlMethod = new ReflectionMethod($writer, 'buildNoticeHtml');
        $buildNoticeHtmlMethod->setAccessible(true);

        $html = $buildNoticeHtmlMethod->invoke(
            $writer,
            ['Produkt Alpha (Variante Blau)'],
            'Die Kennzeichnung der Herstellergarantie konnte nicht erzeugt werden.'
        );

        self::assertStringContainsString('Produkt Alpha (Variante Blau)', $html);
        self::assertStringContainsString('Die Kennzeichnung der Herstellergarantie konnte nicht erzeugt werden.', $html);
        self::assertStringNotContainsString('<img ', $html);
        self::assertStringNotContainsString('var:garanStatic', $html);
        self::assertStringNotContainsString('href=', $html);
    }

    public function testBuildRelativeOutputDirectoryUsesOrderDateFolders(): void
    {
        $writer = new MpdfGaranPdfAttachmentWriter('/tmp', new NullLogger());
        $method = new ReflectionMethod($writer, 'buildRelativeOutputDirectory');
        $method->setAccessible(true);

        self::assertSame(
            'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/2026/11/12',
            $method->invoke($writer, 1_794_441_600)
        );
    }

    public function testSanitizeOrderNumberKeepsAllowedCharactersAndReplacesOthers(): void
    {
        $writer = new MpdfGaranPdfAttachmentWriter('/tmp', new NullLogger());
        $method = new ReflectionMethod($writer, 'sanitizeOrderNumber');
        $method->setAccessible(true);

        self::assertSame('2026-11--4', $method->invoke($writer, '2026/11 #4'));
    }

    public function testDetermineNextSequenceNumberUsesHighestExistingSuffix(): void
    {
        $directory = sys_get_temp_dir() . '/merconis-garan-sequence-' . uniqid('', true);
        mkdir($directory, 0777, true);
        file_put_contents($directory . '/garan-labels-ORDER-77.pdf', 'one');
        file_put_contents($directory . '/garan-labels-ORDER-77_2.pdf', 'two');
        file_put_contents($directory . '/garan-labels-ORDER-77_5.pdf', 'five');

        $writer = new MpdfGaranPdfAttachmentWriter('/tmp', new NullLogger());
        $method = new ReflectionMethod($writer, 'determineNextSequenceNumber');
        $method->setAccessible(true);

        self::assertSame(6, $method->invoke($writer, $directory, 'ORDER-77'));
    }

    public function testReserveOutputFileCreatesSecondFileWhenBaseNameAlreadyExists(): void
    {
        $directory = sys_get_temp_dir() . '/merconis-garan-reserve-' . uniqid('', true);
        mkdir($directory, 0777, true);
        file_put_contents($directory . '/garan-labels-ORDER-77.pdf', 'existing');

        $writer = new MpdfGaranPdfAttachmentWriter('/tmp', new NullLogger());
        $method = new ReflectionMethod($writer, 'reserveOutputFile');
        $method->setAccessible(true);

        $reservedFile = $method->invoke(
            $writer,
            'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/2026/11/12',
            $directory,
            'ORDER-77'
        );

        self::assertSame(
            'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/2026/11/12/garan-labels-ORDER-77_2.pdf',
            $reservedFile['relativePath']
        );
        self::assertFileExists($reservedFile['absolutePath']);

        fclose($reservedFile['fileHandle']);
    }
}
