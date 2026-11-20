<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Support;

use RuntimeException;

/**
 * Liest je Unicode-Codepunkt der `cmap` die Vorschubbreite aus `hmtx`, geteilt durch
 * `unitsPerEm` aus `head`. Kein Kerning, kein Zeichenabstand.
 *
 * Ausgewertet werden alle Unicode-Subtabellen der `cmap` in den Formaten 4 und 12.
 * Abbildungen auf die Glyphe 0 (`.notdef`) zählen nicht als enthaltenes Zeichen.
 */
final class TrueTypeAdvanceWidthReader
{
    /**
     * @return array<int, float> Breite in em je Codepunkt, aufsteigend sortiert
     */
    public function readAdvanceWidthsInEm(string $fontPath): array
    {
        $fontData = file_get_contents($fontPath);

        if (false === $fontData) {
            throw new RuntimeException(sprintf('Font file could not be read: %s', $fontPath));
        }

        $tables = $this->readTableDirectory($fontData);
        $unitsPerEm = $this->readUint16($fontData, $this->requireTableOffset($tables, 'head') + 18);
        $numberOfHMetrics = $this->readUint16($fontData, $this->requireTableOffset($tables, 'hhea') + 34);

        if ($unitsPerEm <= 0 || $numberOfHMetrics <= 0) {
            throw new RuntimeException(sprintf('Font metrics are invalid: %s', $fontPath));
        }

        $advanceWidthsByGlyph = $this->readAdvanceWidthsByGlyph(
            $fontData,
            $this->requireTableOffset($tables, 'hmtx'),
            $numberOfHMetrics
        );
        $widthsInEm = [];

        foreach ($this->readUnicodeGlyphMap($fontData, $this->requireTableOffset($tables, 'cmap')) as $codePoint => $glyphId) {
            $advanceWidth = $advanceWidthsByGlyph[min($glyphId, $numberOfHMetrics - 1)];
            $widthsInEm[$codePoint] = $advanceWidth / $unitsPerEm;
        }

        ksort($widthsInEm);

        return $widthsInEm;
    }

    /**
     * @return array<string, int>
     */
    private function readTableDirectory(string $fontData): array
    {
        $numberOfTables = $this->readUint16($fontData, 4);
        $tables = [];

        for ($index = 0; $index < $numberOfTables; ++$index) {
            $recordOffset = 12 + 16 * $index;
            $tables[substr($fontData, $recordOffset, 4)] = $this->readUint32($fontData, $recordOffset + 8);
        }

        return $tables;
    }

    /**
     * @param array<string, int> $tables
     */
    private function requireTableOffset(array $tables, string $tag): int
    {
        if (!isset($tables[$tag])) {
            throw new RuntimeException(sprintf('Font table is missing: %s', $tag));
        }

        return $tables[$tag];
    }

    /**
     * @return list<int>
     */
    private function readAdvanceWidthsByGlyph(string $fontData, int $hmtxOffset, int $numberOfHMetrics): array
    {
        $advanceWidths = [];

        for ($glyphId = 0; $glyphId < $numberOfHMetrics; ++$glyphId) {
            $advanceWidths[] = $this->readUint16($fontData, $hmtxOffset + 4 * $glyphId);
        }

        return $advanceWidths;
    }

    /**
     * @return array<int, int> Glyphen-ID je Codepunkt
     */
    private function readUnicodeGlyphMap(string $fontData, int $cmapOffset): array
    {
        $numberOfSubtables = $this->readUint16($fontData, $cmapOffset + 2);
        $glyphMap = [];
        $hasUnicodeSubtable = false;

        for ($index = 0; $index < $numberOfSubtables; ++$index) {
            $recordOffset = $cmapOffset + 4 + 8 * $index;
            $platformId = $this->readUint16($fontData, $recordOffset);
            $encodingId = $this->readUint16($fontData, $recordOffset + 2);

            if (!$this->isUnicodeEncoding($platformId, $encodingId)) {
                continue;
            }

            $subtableOffset = $cmapOffset + $this->readUint32($fontData, $recordOffset + 4);
            $subtableMap = match ($this->readUint16($fontData, $subtableOffset)) {
                4 => $this->readFormat4Subtable($fontData, $subtableOffset),
                12 => $this->readFormat12Subtable($fontData, $subtableOffset),
                default => null,
            };

            if (null === $subtableMap) {
                continue;
            }

            $hasUnicodeSubtable = true;

            foreach ($subtableMap as $codePoint => $glyphId) {
                if (isset($glyphMap[$codePoint]) && $glyphMap[$codePoint] !== $glyphId) {
                    throw new RuntimeException(sprintf('Unicode cmap subtables disagree for code point %d.', $codePoint));
                }

                $glyphMap[$codePoint] = $glyphId;
            }
        }

        if (!$hasUnicodeSubtable) {
            throw new RuntimeException('Font has no Unicode cmap subtable in format 4 or 12.');
        }

        return $glyphMap;
    }

    private function isUnicodeEncoding(int $platformId, int $encodingId): bool
    {
        return 0 === $platformId || (3 === $platformId && (1 === $encodingId || 10 === $encodingId));
    }

    /**
     * @return array<int, int>
     */
    private function readFormat4Subtable(string $fontData, int $offset): array
    {
        $segmentCount = intdiv($this->readUint16($fontData, $offset + 6), 2);
        $endCodesOffset = $offset + 14;
        $startCodesOffset = $endCodesOffset + 2 * $segmentCount + 2;
        $idDeltasOffset = $startCodesOffset + 2 * $segmentCount;
        $idRangeOffsetsOffset = $idDeltasOffset + 2 * $segmentCount;
        $glyphMap = [];

        for ($segment = 0; $segment < $segmentCount; ++$segment) {
            $endCode = $this->readUint16($fontData, $endCodesOffset + 2 * $segment);
            $startCode = $this->readUint16($fontData, $startCodesOffset + 2 * $segment);
            $idDelta = $this->readUint16($fontData, $idDeltasOffset + 2 * $segment);
            $idRangeOffsetPosition = $idRangeOffsetsOffset + 2 * $segment;
            $idRangeOffset = $this->readUint16($fontData, $idRangeOffsetPosition);

            for ($codePoint = $startCode; $codePoint <= $endCode && $codePoint !== 0xFFFF; ++$codePoint) {
                if (0 === $idRangeOffset) {
                    $glyphId = ($codePoint + $idDelta) & 0xFFFF;
                } else {
                    $glyphId = $this->readUint16(
                        $fontData,
                        $idRangeOffsetPosition + $idRangeOffset + 2 * ($codePoint - $startCode)
                    );

                    if (0 !== $glyphId) {
                        $glyphId = ($glyphId + $idDelta) & 0xFFFF;
                    }
                }

                if (0 !== $glyphId) {
                    $glyphMap[$codePoint] = $glyphId;
                }
            }
        }

        return $glyphMap;
    }

    /**
     * @return array<int, int>
     */
    private function readFormat12Subtable(string $fontData, int $offset): array
    {
        $numberOfGroups = $this->readUint32($fontData, $offset + 12);
        $glyphMap = [];

        for ($group = 0; $group < $numberOfGroups; ++$group) {
            $groupOffset = $offset + 16 + 12 * $group;
            $startCode = $this->readUint32($fontData, $groupOffset);
            $endCode = $this->readUint32($fontData, $groupOffset + 4);
            $startGlyphId = $this->readUint32($fontData, $groupOffset + 8);

            for ($codePoint = $startCode; $codePoint <= $endCode; ++$codePoint) {
                $glyphId = $startGlyphId + ($codePoint - $startCode);

                if (0 !== $glyphId) {
                    $glyphMap[$codePoint] = $glyphId;
                }
            }
        }

        return $glyphMap;
    }

    private function readUint16(string $fontData, int $offset): int
    {
        $value = unpack('n', $fontData, $offset);

        if (false === $value) {
            throw new RuntimeException(sprintf('Font data ends before offset %d.', $offset));
        }

        return $value[1];
    }

    private function readUint32(string $fontData, int $offset): int
    {
        $value = unpack('N', $fontData, $offset);

        if (false === $value) {
            throw new RuntimeException(sprintf('Font data ends before offset %d.', $offset));
        }

        return $value[1];
    }
}
