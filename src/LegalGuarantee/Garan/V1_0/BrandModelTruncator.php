<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

use JsonException;
use RuntimeException;

/**
 * Kürzt Marke und Modell des vollen GARAN-Labels `v1.0` nach Breite
 * (Spec 27, Abschnitt 5.3; Implementierungsentscheidung 0030).
 *
 * Werte, Regel und Breitentabelle gehören zur Injektionslogik der Version. Nach dem ersten
 * Release braucht jede Änderung daran eine neue Version (ADR 0027, Entscheidung 1).
 */
final class BrandModelTruncator
{
    public const WIDTH_TABLE_FILENAME = 'inter-regular-advance-widths.json';

    // Zeile von 6.32 (Anker der Marke) bis 262.97 (Anker des Modells), gleich der Trennlinie.
    private const LINE_WIDTH = 256.65;
    private const FONT_SIZE = 9.0;
    private const MINIMUM_GAP = 13.5;
    private const MINIMUM_BRAND_WIDTH = 75.0;
    private const ELLIPSIS = '...';
    private const UNKNOWN_CHARACTER_WIDTH_IN_EM = 1.0;
    // Gleitkommatoleranz für die Vergleiche mit den Grenzen der Regel.
    private const WIDTH_COMPARISON_TOLERANCE = 1e-9;

    /**
     * @param array<int, float> $advanceWidthsInEm Vorschubbreite in em je Codepunkt
     */
    public function __construct(
        private readonly array $advanceWidthsInEm,
    ) {
    }

    public static function fromWidthTableFile(string $widthTablePath): self
    {
        $widthTableContents = is_file($widthTablePath) ? file_get_contents($widthTablePath) : false;

        if (false === $widthTableContents) {
            throw new RuntimeException(sprintf('GARAN width table could not be read: %s', $widthTablePath));
        }

        try {
            $decodedWidthTable = json_decode($widthTableContents, true, 2, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                sprintf('GARAN width table is not valid JSON: %s', $widthTablePath),
                0,
                $exception
            );
        }

        if (!is_array($decodedWidthTable) || [] === $decodedWidthTable) {
            throw new RuntimeException(sprintf('GARAN width table is empty: %s', $widthTablePath));
        }

        $advanceWidthsInEm = [];

        foreach ($decodedWidthTable as $codePoint => $widthInEm) {
            if (!is_numeric($codePoint) || (!is_int($widthInEm) && !is_float($widthInEm))) {
                throw new RuntimeException(sprintf('GARAN width table contains an invalid entry: %s', $widthTablePath));
            }

            $advanceWidthsInEm[(int) $codePoint] = (float) $widthInEm;
        }

        return new self($advanceWidthsInEm);
    }

    /**
     * @return array{brand: string, model: string}
     */
    public function truncate(string $brand, string $modelIdentifier): array
    {
        $availableWidth = self::LINE_WIDTH - self::MINIMUM_GAP;
        $brandWidth = $this->measure($brand);

        if ($brandWidth + $this->measure($modelIdentifier) <= $availableWidth + self::WIDTH_COMPARISON_TOLERANCE) {
            return ['brand' => $brand, 'model' => $modelIdentifier];
        }

        $fittedModelIdentifier = $this->fit(
            $modelIdentifier,
            $availableWidth - min($brandWidth, self::MINIMUM_BRAND_WIDTH)
        );

        return [
            'brand' => $this->fit($brand, $availableWidth - $this->measure($fittedModelIdentifier)),
            'model' => $fittedModelIdentifier,
        ];
    }

    /**
     * Breite in Einheiten der `viewBox`, ohne Kerning und Zeichenabstand.
     */
    public function measure(string $value): float
    {
        $widthInEm = 0.0;

        foreach (mb_str_split($value) as $character) {
            $widthInEm += $this->advanceWidthsInEm[mb_ord($character)] ?? self::UNKNOWN_CHARACTER_WIDTH_IN_EM;
        }

        return $widthInEm * self::FONT_SIZE;
    }

    private function fit(string $value, float $limit): string
    {
        if ($this->measure($value) <= $limit + self::WIDTH_COMPARISON_TOLERANCE) {
            return $value;
        }

        $characters = mb_str_split($value);

        for ($prefixLength = count($characters) - 1; $prefixLength > 0; --$prefixLength) {
            $candidate = $this->trimTrailingWhitespace(implode('', array_slice($characters, 0, $prefixLength)))
                . self::ELLIPSIS;

            if ($this->measure($candidate) <= $limit + self::WIDTH_COMPARISON_TOLERANCE) {
                return $candidate;
            }
        }

        return self::ELLIPSIS;
    }

    private function trimTrailingWhitespace(string $value): string
    {
        return (string) preg_replace('/(*UCP)\s+\z/u', '', $value);
    }
}
