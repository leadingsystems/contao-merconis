<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OfficialGuaranteeAssetIntegrationTest extends TestCase
{
    public function testInterFontDirectoryContainsExactlyTheExpectedFiles(): void
    {
        $fontDirectory = $this->createAssetLocator()->getInterFontDirectory();
        $fontFileNames = array_values(array_diff(scandir($fontDirectory) ?: [], ['.', '..']));
        sort($fontFileNames);

        self::assertSame(
            [
                'Inter-ExtraBold.ttf',
                'Inter-ExtraBold.woff2',
                'Inter-Regular.ttf',
                'Inter-Regular.woff2',
                'Inter-SemiBold.ttf',
                'Inter-SemiBold.woff2',
                'LICENSE.txt',
            ],
            $fontFileNames
        );
    }

    public function testFontStylesheetDeclaresWoff2BeforeTrueTypeForEveryInterFace(): void
    {
        $assetLocator = $this->createAssetLocator();
        $stylesheetContents = (string) file_get_contents($assetLocator->getFontStylesheetPath());

        self::assertSame(6, preg_match_all('/@font-face\s*\{.*?\}/s', $stylesheetContents));
        self::assertSame(6, preg_match_all(
            '/src:\s*url\("([^"]+)"\)\s*format\("([^"]+)"\),\s*url\("([^"]+)"\)\s*format\("([^"]+)"\);/m',
            $stylesheetContents,
            $matches,
            PREG_SET_ORDER
        ));
        self::assertStringNotContainsString('.otf', $stylesheetContents);
        self::assertStringNotContainsString('format("opentype")', $stylesheetContents);

        foreach ($matches as $match) {
            self::assertSame('woff2', $match[2]);
            self::assertSame('truetype', $match[4]);
            self::assertStringEndsWith('.woff2', $match[1]);
            self::assertStringEndsWith('.ttf', $match[3]);
            self::assertFileExists(dirname($assetLocator->getFontStylesheetPath()) . '/' . ltrim($match[1], './'));
            self::assertFileExists(dirname($assetLocator->getFontStylesheetPath()) . '/' . ltrim($match[3], './'));
        }
    }

    public function testOfficialGllPdfAssetsExposeSingleColourPagesWithOfficialLinks(): void
    {
        $assetLocator = $this->createAssetLocator();
        $expectedLinksByLanguage = $this->getExpectedLinksByLanguage();

        self::assertSame(array_keys($expectedLinksByLanguage), $assetLocator->getAvailableGllPdfLanguages());

        foreach ($expectedLinksByLanguage as $language => $expectedLinks) {
            $pdfPath = $assetLocator->getGllPdfPathByLanguage($language);
            self::assertNotNull($pdfPath);

            $pdfInspector = new VersionedPdfAssetInspector($pdfPath);

            self::assertSame(1, $pdfInspector->getPageCount(), $language);
            self::assertSame($expectedLinks, $pdfInspector->getLinkUris(), $language);
        }
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function getExpectedLinksByLanguage(): array
    {
        $languages = [
            'bg', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr',
            'ga', 'hr', 'hu', 'it', 'lt', 'lv', 'mt', 'nl', 'pl', 'pt',
            'ro', 'sk', 'sl', 'sv',
        ];
        $expectedLinksByLanguage = [];

        foreach ($languages as $language) {
            $expectedLinksByLanguage[$language] = $language === 'en'
                ? []
                : [
                    sprintf(
                        'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_%s.htm',
                        $language
                    ),
                ];
        }

        return $expectedLinksByLanguage;
    }
}

final class VersionedPdfAssetInspector
{
    private string $pdfContents;
    private string $searchableContents;

    public function __construct(private readonly string $pdfPath)
    {
        $pdfContents = file_get_contents($this->pdfPath);

        if (false === $pdfContents) {
            throw new RuntimeException(sprintf('Could not read PDF file: %s', $this->pdfPath));
        }

        $this->pdfContents = $pdfContents;
        $this->searchableContents = $this->buildSearchableContents($pdfContents);
    }

    public function getPageCount(): int
    {
        preg_match_all('/\/Type\s*\/Page\b/', $this->searchableContents, $matches);

        return count($matches[0]);
    }

    /**
     * @return list<string>
     */
    public function getLinkUris(): array
    {
        preg_match_all('/\/URI\s*\((.*?)\)/s', $this->searchableContents, $matches);

        return array_values(array_unique(array_map(
            static fn (string $uri): string => stripcslashes($uri),
            $matches[1]
        )));
    }

    private function buildSearchableContents(string $pdfContents): string
    {
        $segments = [$pdfContents];
        preg_match_all('/\d+\s+\d+\s+obj(.*?)endobj/s', $pdfContents, $matches);

        foreach ($matches[1] as $objectBody) {
            $streamContents = $this->extractStreamContents($objectBody);

            if (null === $streamContents) {
                continue;
            }

            if (!preg_match('/\/Filter\s*(?:\[[^\]]*\/FlateDecode[^\]]*\]|\/FlateDecode)/', $objectBody)) {
                continue;
            }

            $decodedStream = zlib_decode($streamContents);

            if (false === $decodedStream) {
                continue;
            }

            if (preg_match('/\/Type\s*\/ObjStm\b/', $objectBody) && preg_match('/\/First\s+(\d+)/', $objectBody, $firstMatch)) {
                $decodedStream = substr($decodedStream, (int) $firstMatch[1]);
            }

            $segments[] = $decodedStream;
        }

        return implode("\n", $segments);
    }

    private function extractStreamContents(string $objectBody): ?string
    {
        $streamPosition = strpos($objectBody, 'stream');
        $endStreamPosition = strpos($objectBody, 'endstream');

        if (false === $streamPosition || false === $endStreamPosition || $endStreamPosition <= $streamPosition) {
            return null;
        }

        $streamStart = $streamPosition + strlen('stream');

        if (substr($objectBody, $streamStart, 2) === "\r\n") {
            $streamStart += 2;
        } elseif (substr($objectBody, $streamStart, 1) === "\n" || substr($objectBody, $streamStart, 1) === "\r") {
            $streamStart += 1;
        }

        return substr($objectBody, $streamStart, $endStreamPosition - $streamStart);
    }
}
