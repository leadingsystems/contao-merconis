<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

final class GuaranteeDurationFormatter
{
    public static function formatYearsForDisplay(string $durationYears): string
    {
        $normalizedDuration = trim($durationYears);

        if ('' === $normalizedDuration) {
            return '';
        }

        $normalizedDuration = preg_replace('/\.0$/', '', $normalizedDuration) ?? $normalizedDuration;

        return str_replace('.', ',', $normalizedDuration);
    }
}
