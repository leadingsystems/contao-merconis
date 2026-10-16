<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Support;

final class LocalizedFieldValueResolver
{
    /**
     * @param array<string, mixed> $values
     */
    public static function resolve(array $values, string $field, string $language): ?string
    {
        $normalizedLanguage = self::normalizeLanguage($language);

        if ($normalizedLanguage !== '') {
            $localizedValue = self::optionalString($values[$field . '_' . $normalizedLanguage] ?? null);

            if ($localizedValue !== null) {
                return $localizedValue;
            }
        }

        return self::optionalString($values[$field] ?? null);
    }

    public static function normalizeLanguage(string $language): string
    {
        $normalizedLanguage = strtolower(trim($language));

        if ($normalizedLanguage === '') {
            return '';
        }

        return preg_replace('/[^a-z].*$/', '', $normalizedLanguage) ?: $normalizedLanguage;
    }

    private static function optionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue === '' ? null : $normalizedValue;
    }
}
