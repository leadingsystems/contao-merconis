<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\Tests\Support\TrueTypeAdvanceWidthReader;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/TrueTypeAdvanceWidthReader.php';

/**
 * Die Breitentabelle der GARAN-Version `v1.0` passt zur ausgelieferten Schrift
 * (Implementierungsentscheidung 0030, Abschnitt "Breitentabelle").
 */
final class GaranWidthTableIntegrationTest extends TestCase
{
    private const WIDTH_TABLE_PATH = __DIR__ . '/../../src/Resources/legal-guarantee/garan/v1.0/inter-regular-advance-widths.json';
    private const FONT_PATH = __DIR__ . '/../../src/Resources/public/legal-guarantee/fonts/inter/Inter-Regular.ttf';
    private const MAXIMUM_DEVIATION_IN_EM = 1e-6;

    public function testWidthTableMatchesInterRegularForAllCodePoints(): void
    {
        $widthTable = json_decode((string) file_get_contents(self::WIDTH_TABLE_PATH), true, 2, JSON_THROW_ON_ERROR);
        $fontWidths = (new TrueTypeAdvanceWidthReader())->readAdvanceWidthsInEm(self::FONT_PATH);

        self::assertIsArray($widthTable);
        self::assertNotEmpty($fontWidths);

        $tableCodePoints = array_map('intval', array_keys($widthTable));
        $fontCodePoints = array_keys($fontWidths);
        sort($tableCodePoints);

        self::assertSame($fontCodePoints, $tableCodePoints);

        foreach ($fontWidths as $codePoint => $fontWidthInEm) {
            self::assertEqualsWithDelta(
                $fontWidthInEm,
                $widthTable[$codePoint],
                self::MAXIMUM_DEVIATION_IN_EM,
                sprintf('Width of code point %d', $codePoint)
            );
        }
    }

    public function testWidthTableLiesInTheNonPublicGaranVersionDirectory(): void
    {
        self::assertFileExists(self::WIDTH_TABLE_PATH);
        self::assertStringNotContainsString('/public/', (string) realpath(self::WIDTH_TABLE_PATH));
    }
}
