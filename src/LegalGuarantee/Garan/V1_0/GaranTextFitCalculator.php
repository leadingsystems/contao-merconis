<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use DOMElement;
use FontLib\Font;
use RuntimeException;

final class GaranTextFitCalculator
{
    /**
     * @var array<string, array{
     *     unitsPerEm: int,
     *     glyphWidths: array<int, int>,
     *     unicodeToGlyph: array<int, int>,
     *     kerningPairs: array<int, array<int, int>>,
     *     fallbackWidth: int
     * }>
     */
    private static array $fontMetricsCache = [];

    public function applyFittedFontSize(
        DOMElement $textElement,
        string $value,
        float $baseFontSizePx,
        float $maxWidthPx,
        string $fontPath,
        float $letterSpacingEm = 0.0
    ): void {
        $fittedFontSize = $this->calculateFittedFontSize(
            $value,
            $baseFontSizePx,
            $maxWidthPx,
            $fontPath,
            $letterSpacingEm
        );

        if ($fittedFontSize < $baseFontSizePx) {
            $textElement->setAttribute('font-size', $this->formatSvgFloat($fittedFontSize) . 'px');
        }
    }

    public function applyFittedInlineFontSizeStyle(
        DOMElement $textElement,
        string $value,
        float $baseFontSizePx,
        float $maxWidthPx,
        string $fontPath,
        float $letterSpacingEm = 0.0
    ): void {
        $fittedFontSize = $this->calculateFittedFontSize(
            $value,
            $baseFontSizePx,
            $maxWidthPx,
            $fontPath,
            $letterSpacingEm
        );

        if ($fittedFontSize < $baseFontSizePx) {
            $this->setInlineStyleDeclaration($textElement, 'font-size', $this->formatSvgFloat($fittedFontSize) . 'px');
        }
    }

    public function calculateFittedFontSize(
        string $value,
        float $baseFontSizePx,
        float $maxWidthPx,
        string $fontPath,
        float $letterSpacingEm = 0.0
    ): float {
        if ($baseFontSizePx <= 0.0) {
            throw new RuntimeException('GARAN text fit requires a positive base font size.');
        }

        if ($maxWidthPx <= 0.0) {
            throw new RuntimeException('GARAN text fit requires a positive max width.');
        }

        $measuredWidthPx = $this->measureTextWidthPx($value, $baseFontSizePx, $fontPath, $letterSpacingEm);

        if ($measuredWidthPx <= $maxWidthPx || $measuredWidthPx <= 0.0) {
            return $baseFontSizePx;
        }

        return round($baseFontSizePx * ($maxWidthPx / $measuredWidthPx), 3);
    }

    private function measureTextWidthPx(
        string $value,
        float $fontSizePx,
        string $fontPath,
        float $letterSpacingEm
    ): float {
        $metrics = $this->getFontMetrics($fontPath);
        $characters = $this->splitCharacters($value);
        $advanceWidth = 0;
        $previousGlyphId = null;

        foreach ($characters as $character) {
            $codePoint = $this->toCodePoint($character);
            $glyphId = $metrics['unicodeToGlyph'][$codePoint] ?? null;
            $advanceWidth += $glyphId !== null && isset($metrics['glyphWidths'][$glyphId])
                ? $metrics['glyphWidths'][$glyphId]
                : $metrics['fallbackWidth'];

            if (
                $previousGlyphId !== null
                && $glyphId !== null
                && isset($metrics['kerningPairs'][$previousGlyphId][$glyphId])
            ) {
                $advanceWidth += $metrics['kerningPairs'][$previousGlyphId][$glyphId];
            }

            $previousGlyphId = $glyphId;
        }

        $widthPx = ($advanceWidth / $metrics['unitsPerEm']) * $fontSizePx;

        if (count($characters) > 1 && $letterSpacingEm !== 0.0) {
            $widthPx += (count($characters) - 1) * $letterSpacingEm * $fontSizePx;
        }

        return max(0.0, $widthPx);
    }

    /**
     * @return array{
     *     unitsPerEm: int,
     *     glyphWidths: array<int, int>,
     *     unicodeToGlyph: array<int, int>,
     *     kerningPairs: array<int, array<int, int>>,
     *     fallbackWidth: int
     * }
     */
    private function getFontMetrics(string $fontPath): array
    {
        if (isset(self::$fontMetricsCache[$fontPath])) {
            return self::$fontMetricsCache[$fontPath];
        }

        $font = Font::load($fontPath);

        if ($font === null) {
            throw new RuntimeException(sprintf('GARAN font could not be loaded: %s', $fontPath));
        }

        $font->parse();

        $unicodeToGlyph = $font->getUnicodeCharMap();
        $glyphWidths = $font->getData('hmtx');
        $unitsPerEm = $font->getData('head', 'unitsPerEm');

        if (!is_array($unicodeToGlyph) || !is_array($glyphWidths) || !is_int($unitsPerEm) || $unitsPerEm <= 0) {
            throw new RuntimeException(sprintf('GARAN font metrics are incomplete: %s', $fontPath));
        }

        $spaceGlyphId = $unicodeToGlyph[32] ?? null;
        $fallbackWidth = $spaceGlyphId !== null && isset($glyphWidths[$spaceGlyphId][0])
            ? (int) $glyphWidths[$spaceGlyphId][0]
            : 0;

        $kernSubtable = $font->getData('kern', 'subtable');
        $kerningPairs = is_array($kernSubtable['tree'] ?? null) ? $kernSubtable['tree'] : [];

        return self::$fontMetricsCache[$fontPath] = [
            'unitsPerEm' => $unitsPerEm,
            'glyphWidths' => array_map(
                static fn (array $glyphMetric): int => (int) ($glyphMetric[0] ?? 0),
                $glyphWidths
            ),
            'unicodeToGlyph' => array_map(
                static fn (mixed $glyphId): int => (int) $glyphId,
                $unicodeToGlyph
            ),
            'kerningPairs' => $kerningPairs,
            'fallbackWidth' => $fallbackWidth,
        ];
    }

    /**
     * @return list<string>
     */
    private function splitCharacters(string $value): array
    {
        if ('' === $value) {
            return [];
        }

        if (function_exists('mb_str_split')) {
            return mb_str_split($value);
        }

        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($characters) ? $characters : str_split($value);
    }

    private function toCodePoint(string $character): int
    {
        if (function_exists('mb_ord')) {
            return mb_ord($character, 'UTF-8');
        }

        $encoded = mb_convert_encoding($character, 'UCS-4BE', 'UTF-8');
        $codePoint = unpack('N', $encoded);

        return (int) ($codePoint[1] ?? 0);
    }

    private function formatSvgFloat(float $value): string
    {
        return rtrim(rtrim(sprintf('%.3F', $value), '0'), '.');
    }

    private function setInlineStyleDeclaration(DOMElement $textElement, string $property, string $value): void
    {
        $style = trim($textElement->getAttribute('style'));

        if ($style !== '') {
            $pattern = sprintf('/(?:^|;)\s*%s\s*:[^;]*(?=;|$)/i', preg_quote($property, '/'));
            $style = trim((string) preg_replace($pattern, '', $style));
            $style = trim($style, " \t\n\r\0\x0B;");
        }

        $style .= ($style === '' ? '' : '; ') . sprintf('%s: %s', $property, $value);
        $textElement->setAttribute('style', $style);
    }
}
