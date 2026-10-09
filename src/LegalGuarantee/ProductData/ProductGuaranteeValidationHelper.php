<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

final class ProductGuaranteeValidationHelper
{
    /**
     * @return array{
     *   effectiveValue: string,
     *   storedValue: string|null,
     *   messages: list<array{code: string, parameters: array<int, string>}>
     * }
     */
    public function normalizeDurationInput(mixed $rawValue): array
    {
        $originalInput = trim((string) $rawValue);

        if ('' === $originalInput) {
            return [
                'effectiveValue' => '',
                'storedValue' => null,
                'messages' => [],
            ];
        }

        if (!preg_match('/^\d+$/', $originalInput)) {
            return [
                'effectiveValue' => '',
                'storedValue' => null,
                'messages' => [[
                    'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
                    'parameters' => [$originalInput],
                ]],
            ];
        }

        $months = (int) $originalInput;

        if ($months < 30 || $months > 360 || 0 !== $months % 6) {
            return [
                'effectiveValue' => '',
                'storedValue' => null,
                'messages' => [[
                    'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
                    'parameters' => [$originalInput],
                ]],
            ];
        }

        $storedYears = $this->formatStoredYearsFromMonths($months);

        return [
            'effectiveValue' => $storedYears,
            'storedValue' => $storedYears,
            'messages' => [],
        ];
    }

    public function convertStoredYearsToMonthsInput(mixed $rawValue): string
    {
        $value = trim((string) $rawValue);

        if ('' === $value) {
            return '';
        }

        $normalizedValue = str_replace(',', '.', $value);

        if (!is_numeric($normalizedValue)) {
            return $value;
        }

        return (string) ((int) round(((float) $normalizedValue) * 12));
    }

    public function getFieldNameForMessageCode(string $messageCode): ?string
    {
        return match ($messageCode) {
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND => 'guaranteeBrand',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL => 'guaranteeModelIdentifier',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION => 'guaranteeDurationYears',
            default => null,
        };
    }

    private function formatStoredYearsFromMonths(int $months): string
    {
        if (0 === $months % 12) {
            return (string) intdiv($months, 12);
        }

        return number_format($months / 12, 1, '.', '');
    }
}
