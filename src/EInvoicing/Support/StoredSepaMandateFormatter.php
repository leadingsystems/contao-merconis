<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Support;

final class StoredSepaMandateFormatter
{
    /**
     * @param array<string, mixed> $memberRecord
     */
    public static function hasStoredMandate(array $memberRecord): bool
    {
        return self::optionalString($memberRecord[EInvoicingSnapshotFields::MEMBER_MANDATE_REFERENCE_FIELD] ?? null) !== null;
    }

    /**
     * @param array<string, mixed> $memberRecord
     */
    public static function resolveAccountHolder(array $memberRecord): ?string
    {
        $company = self::optionalString($memberRecord['company'] ?? null);

        if ($company !== null) {
            return $company;
        }

        $firstName = self::optionalString($memberRecord['firstname'] ?? null);
        $lastName = self::optionalString($memberRecord['lastname'] ?? null);
        $fullName = trim(implode(' ', array_filter([$firstName, $lastName], static fn (?string $value): bool => $value !== null)));

        return $fullName !== '' ? $fullName : null;
    }

    public static function maskIban(mixed $iban): ?string
    {
        $normalizedIban = self::optionalString($iban);

        if ($normalizedIban === null) {
            return null;
        }

        $normalizedIban = strtoupper(preg_replace('/\s+/', '', $normalizedIban) ?? '');
        $length = strlen($normalizedIban);

        if ($length === 0) {
            return null;
        }

        if ($length <= 8) {
            return trim(chunk_split(str_repeat('*', max(0, $length - 4)) . substr($normalizedIban, -4), 4, ' '));
        }

        $maskedBlockCount = (int) ceil(($length - 8) / 4);
        $maskedMiddle = implode(' ', array_fill(0, $maskedBlockCount, '****'));

        return trim(substr($normalizedIban, 0, 4) . ' ' . $maskedMiddle . ' ' . substr($normalizedIban, -4));
    }

    private static function optionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
