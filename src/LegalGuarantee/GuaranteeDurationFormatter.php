<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

final class GuaranteeDurationFormatter
{
    public static function formatYearsForDisplay(string $durationYears, string $outputDecimalSeparator): string
    {
        $normalizedDuration = trim($durationYears);

        if ('' === $normalizedDuration) {
            return '';
        }

        $normalizedDuration = preg_replace('/\.0$/', '', $normalizedDuration) ?? $normalizedDuration;
        $normalizedDecimalSeparator = trim($outputDecimalSeparator);

        if ('' === $normalizedDecimalSeparator) {
            $normalizedDecimalSeparator = ',';
        }

        return str_replace('.', $normalizedDecimalSeparator, $normalizedDuration);
    }
}
