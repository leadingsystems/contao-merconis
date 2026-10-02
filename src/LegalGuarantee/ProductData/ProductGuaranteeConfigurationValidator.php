<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

final class ProductGuaranteeConfigurationValidator
{
    public const MESSAGE_GARAN_DISABLED_MISSING_BRAND = 'garan_disabled_missing_brand';
    public const MESSAGE_GARAN_DISABLED_MISSING_MODEL = 'garan_disabled_missing_model';
    public const MESSAGE_GARAN_DISABLED_MISSING_DURATION = 'garan_disabled_missing_duration';
    public const MESSAGE_GARAN_DISABLED_INVALID_DURATION = 'garan_disabled_invalid_duration';

    /**
     * @param array<string, mixed> $productData
     */
    public function validateProduct(array $productData): GuaranteeValidationResult
    {
        $normalizedData = $this->normalizeSharedFields($productData);
        $durationResult = $this->normalizeDurationInput($productData['guaranteeDurationYears'] ?? null);
        $normalizedData['guaranteeDurationYears'] = $durationResult['storedValue'];
        $effectiveData = [
            'enableGaran' => $normalizedData['enableGaran'],
            'guaranteeDurationYears' => $durationResult['effectiveValue'],
            'guaranteeBrand' => $normalizedData['guaranteeBrand'],
            'guaranteeModelIdentifier' => $normalizedData['guaranteeModelIdentifier'],
        ];
        $messages = $durationResult['messages'];

        if ($this->isTruthy($normalizedData['enableGaran'] ?? null)) {
            $messages = array_merge(
                $messages,
                $this->validateGaranRequirements($effectiveData, $durationResult['messages'])
            );
        }

        return new GuaranteeValidationResult(
            $normalizedData,
            $effectiveData,
            $messages,
        );
    }

    /**
     * @param array<string, mixed> $variantData
     * @param array<string, mixed> $productData
     */
    public function validateVariant(array $variantData, array $productData): GuaranteeValidationResult
    {
        $normalizedVariantData = $this->normalizeSharedFields($variantData);
        $normalizedProductData = $this->normalizeSharedFields($productData);
        $durationResult = $this->normalizeDurationInput($variantData['guaranteeDurationYears'] ?? null);
        $normalizedVariantData['guaranteeDurationYears'] = $durationResult['storedValue'];
        $messages = $durationResult['messages'];
        $effectiveData = $this->buildEffectiveVariantData(
            $normalizedVariantData,
            $normalizedProductData,
            $durationResult['effectiveValue'],
        );

        if ($this->isTruthy($normalizedVariantData['garanOverride'] ?? null)
            && $this->isTruthy($normalizedVariantData['enableGaran'] ?? null)
        ) {
            $messages = array_merge(
                $messages,
                $this->validateGaranRequirements($effectiveData, $durationResult['messages'])
            );
        }

        return new GuaranteeValidationResult(
            $normalizedVariantData,
            $effectiveData,
            $messages,
        );
    }

    public function getInitialGuaranteeBrand(?string $productProducer): string
    {
        $productProducer = trim((string) $productProducer);

        if ('' === $productProducer) {
            return '';
        }

        return mb_substr($productProducer, 0, 40);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalizeSharedFields(array $data): array
    {
        $data['enableGll'] = $this->normalizeCheckboxValue($data['enableGll'] ?? null);
        $data['enableGaran'] = $this->normalizeCheckboxValue($data['enableGaran'] ?? null);
        $data['garanOverride'] = $this->normalizeCheckboxValue($data['garanOverride'] ?? null);
        $data['guaranteeBrand'] = trim((string) ($data['guaranteeBrand'] ?? ''));
        $data['guaranteeModelIdentifier'] = trim((string) ($data['guaranteeModelIdentifier'] ?? ''));

        return $data;
    }

    private function normalizeCheckboxValue(mixed $value): string
    {
        return $this->isTruthy($value) ? '1' : '';
    }

    private function isTruthy(mixed $value): bool
    {
        return !in_array($value, [null, '', 0, '0', false], true);
    }

    /**
     * @param array<string, mixed> $effectiveData
     * @param list<array{code: string, parameters: array<int, string>}> $durationMessages
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    private function validateGaranRequirements(array $effectiveData, array $durationMessages): array
    {
        $messages = $this->validateBrandAndModelRequirements($effectiveData);

        if (
            '' === ($effectiveData['guaranteeDurationYears'] ?? '')
            && [] === $durationMessages
        ) {
            $messages[] = [
                'code' => self::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
                'parameters' => [],
            ];
        }

        return $messages;
    }

    /**
     * @param array<string, mixed> $effectiveData
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    private function validateBrandAndModelRequirements(array $effectiveData): array
    {
        $messages = [];

        if ('' === ($effectiveData['guaranteeBrand'] ?? '')) {
            $messages[] = [
                'code' => self::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                'parameters' => [],
            ];
        }

        if ('' === ($effectiveData['guaranteeModelIdentifier'] ?? '')) {
            $messages[] = [
                'code' => self::MESSAGE_GARAN_DISABLED_MISSING_MODEL,
                'parameters' => [],
            ];
        }

        return $messages;
    }

    /**
     * @param array<string, mixed> $variantData
     * @param array<string, mixed> $productData
     *
     * @return array<string, mixed>
     */
    private function buildEffectiveVariantData(
        array $variantData,
        array $productData,
        string $normalizedVariantDuration,
    ): array {
        if (!$this->isTruthy($variantData['garanOverride'] ?? null)) {
            return [
                'enableGaran' => $this->normalizeCheckboxValue($productData['enableGaran'] ?? null),
                'guaranteeDurationYears' => $this->normalizeStoredDurationValue(
                    $productData['guaranteeDurationYears'] ?? null
                ),
                'guaranteeBrand' => trim((string) ($productData['guaranteeBrand'] ?? '')),
                'guaranteeModelIdentifier' => trim((string) ($productData['guaranteeModelIdentifier'] ?? '')),
            ];
        }

        return [
            'enableGaran' => $this->normalizeCheckboxValue($variantData['enableGaran'] ?? null),
            'guaranteeDurationYears' => '' !== $normalizedVariantDuration
                ? $normalizedVariantDuration
                : $this->normalizeStoredDurationValue($productData['guaranteeDurationYears'] ?? null),
            'guaranteeBrand' => '' !== ($variantData['guaranteeBrand'] ?? '')
                ? $variantData['guaranteeBrand']
                : trim((string) ($productData['guaranteeBrand'] ?? '')),
            'guaranteeModelIdentifier' => '' !== ($variantData['guaranteeModelIdentifier'] ?? '')
                ? $variantData['guaranteeModelIdentifier']
                : trim((string) ($productData['guaranteeModelIdentifier'] ?? '')),
        ];
    }

    /**
     * @return array{
     *   effectiveValue: string,
     *   storedValue: string|null,
     *   messages: list<array{code: string, parameters: array<int, string>}>
     * }
     */
    private function normalizeDurationInput(mixed $rawValue): array
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
                    'code' => self::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
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
                    'code' => self::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
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

    private function normalizeStoredDurationValue(mixed $rawValue): string
    {
        $value = trim((string) $rawValue);

        if ('' === $value) {
            return '';
        }

        $normalizedValue = str_replace(',', '.', $value);

        return preg_replace('/\.0$/', '', $normalizedValue) ?? $normalizedValue;
    }

    private function formatStoredYearsFromMonths(int $months): string
    {
        if (0 === $months % 12) {
            return (string) intdiv($months, 12);
        }

        return number_format($months / 12, 1, '.', '');
    }
}
