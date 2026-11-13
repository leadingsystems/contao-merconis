<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

final class GuaranteeDurationFormatter
{
    public static function formatMonthsForDisplay(string $durationMonths): string
    {
        $normalizedDuration = trim($durationMonths);

        if ('' === $normalizedDuration) {
            return '';
        }

        if (!preg_match('/^\d+$/', $normalizedDuration)) {
            return $normalizedDuration;
        }

        $months = (int) $normalizedDuration;

        if (0 === $months % 12) {
            return (string) intdiv($months, 12);
        }

        return number_format($months / 12, 1, ',', '');
    }
}
