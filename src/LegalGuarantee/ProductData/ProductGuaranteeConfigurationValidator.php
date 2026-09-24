<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

final class ProductGuaranteeConfigurationValidator
{
    public const MESSAGE_GARAN_DISABLED_MISSING_BRAND = 'garan_disabled_missing_brand';
    public const MESSAGE_GARAN_DISABLED_MISSING_MODEL = 'garan_disabled_missing_model';
    public const MESSAGE_GARAN_DISABLED_MISSING_DURATION = 'garan_disabled_missing_duration';
    public const MESSAGE_GARAN_DISABLED_INVALID_DURATION = 'garan_disabled_invalid_duration';
    public const MESSAGE_GARAN_DISABLED_DURATION_OUT_OF_RANGE = 'garan_disabled_duration_out_of_range';
    public const MESSAGE_GARAN_DISABLED_DURATION_BELOW_MINIMUM = 'garan_disabled_duration_below_minimum';
    public const MESSAGE_GARAN_DURATION_ROUNDED_DOWN = 'garan_duration_rounded_down';

    /**
     * @param array<string, mixed> $productData
     */
    public function validateProduct(array $productData): GuaranteeValidationResult
    {
        $normalizedData = $this->normalizeSharedFields($productData);
        $messages = [];

        if ($this->isTruthy($normalizedData['enableGaran'] ?? null)) {
            $durationResult = $this->normalizeDuration($normalizedData['guaranteeDurationYears'] ?? null);
            $normalizedData['guaranteeDurationYears'] = $durationResult['storedValue'];
            $messages = array_merge($messages, $durationResult['messages']);

            $effectiveData = $normalizedData;
            $effectiveData['guaranteeDurationYears'] = $durationResult['normalizedValue'];

            $validationMessages = $this->validateBrandAndModelRequirements($effectiveData);

            if (
                '' === ($effectiveData['guaranteeDurationYears'] ?? '')
                && [] === $durationResult['messages']
            ) {
                $validationMessages[] = [
                    'code' => self::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
                    'parameters' => [],
                ];
            }

            $messages = array_merge($messages, $validationMessages);

            if ($this->messagesRequireGaranDisable($durationResult['messages'], $validationMessages)) {
                $normalizedData['enableGaran'] = '';
                $effectiveData['enableGaran'] = '';
            }

            return new GuaranteeValidationResult(
                $normalizedData,
                $effectiveData,
                $messages,
            );
        }

        return new GuaranteeValidationResult(
            $normalizedData,
            [
                'enableGaran' => $normalizedData['enableGaran'],
                'guaranteeDurationYears' => $this->normalizeOutputDurationValue(
                    $normalizedData['guaranteeDurationYears'] ?? null
                ),
                'guaranteeBrand' => $normalizedData['guaranteeBrand'],
                'guaranteeModelIdentifier' => $normalizedData['guaranteeModelIdentifier'],
            ],
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

        if (!$this->isTruthy($normalizedVariantData['garanOverride'] ?? null)) {
            return new GuaranteeValidationResult(
                $normalizedVariantData,
                $this->buildEffectiveVariantData($normalizedVariantData, $normalizedProductData),
                [],
            );
        }

        $messages = [];

        if ($this->isTruthy($normalizedVariantData['enableGaran'] ?? null)) {
            $durationResult = $this->normalizeDuration(
                $normalizedVariantData['guaranteeDurationYears'] ?? null,
                $normalizedProductData['guaranteeDurationYears'] ?? null,
            );
            $normalizedVariantData['guaranteeDurationYears'] = $durationResult['storedValue'];
            $messages = array_merge($messages, $durationResult['messages']);

            $effectiveData = $this->buildEffectiveVariantData($normalizedVariantData, $normalizedProductData);
            $validationMessages = $this->validateBrandAndModelRequirements($effectiveData);

            if (
                '' === ($effectiveData['guaranteeDurationYears'] ?? '')
                && [] === $durationResult['messages']
            ) {
                $validationMessages[] = [
                    'code' => self::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
                    'parameters' => [],
                ];
            }

            $messages = array_merge($messages, $validationMessages);

            if ($this->messagesRequireGaranDisable($durationResult['messages'], $validationMessages)) {
                $normalizedVariantData['enableGaran'] = '';
                $effectiveData['enableGaran'] = '';
            }

            return new GuaranteeValidationResult(
                $normalizedVariantData,
                $effectiveData,
                $messages,
            );
        }

        return new GuaranteeValidationResult(
            $normalizedVariantData,
            $this->buildEffectiveVariantData($normalizedVariantData, $normalizedProductData),
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
    private function buildEffectiveVariantData(array $variantData, array $productData): array
    {
        if (!$this->isTruthy($variantData['garanOverride'] ?? null)) {
            return [
                'enableGaran' => $this->normalizeCheckboxValue($productData['enableGaran'] ?? null),
                'guaranteeDurationYears' => $this->normalizeOutputDurationValue(
                    $productData['guaranteeDurationYears'] ?? null
                ),
                'guaranteeBrand' => trim((string) ($productData['guaranteeBrand'] ?? '')),
                'guaranteeModelIdentifier' => trim((string) ($productData['guaranteeModelIdentifier'] ?? '')),
            ];
        }

        return [
            'enableGaran' => $this->normalizeCheckboxValue($variantData['enableGaran'] ?? null),
            'guaranteeDurationYears' => $this->normalizeOutputDurationValue(
                $variantData['guaranteeDurationYears'] ?? null,
                $productData['guaranteeDurationYears'] ?? null,
            ),
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
     *   normalizedValue: string,
     *   storedValue: string|null,
     *   messages: list<array{code: string, parameters: array<int, string>}>
     * }
     */
    private function normalizeDuration(mixed $rawValue, mixed $fallbackValue = null): array
    {
        $originalInput = trim((string) $rawValue);
        $messages = [];

        if ('' === $originalInput) {
            return [
                'normalizedValue' => $this->normalizeOutputDurationValue($fallbackValue),
                'storedValue' => null,
                'messages' => [],
            ];
        }

        $normalizedInput = str_replace(',', '.', $originalInput);

        if (!preg_match('/^\d{1,2}(?:\.\d+)?$/', $normalizedInput)) {
            return [
                'normalizedValue' => '',
                'storedValue' => null,
                'messages' => [[
                    'code' => self::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
                    'parameters' => [$originalInput],
                ]],
            ];
        }

        [$wholePart, $decimalPart] = array_pad(explode('.', $normalizedInput, 2), 2, '');
        $scaledThousandths = ((int) $wholePart * 1000) + (int) str_pad(substr($decimalPart, 0, 3), 3, '0');
        $roundedScaledThousandths = intdiv($scaledThousandths, 500) * 500;
        $roundedValue = $this->formatHalfYearValue($roundedScaledThousandths);

        if ($roundedScaledThousandths !== $scaledThousandths) {
            $messages[] = [
                'code' => self::MESSAGE_GARAN_DURATION_ROUNDED_DOWN,
                'parameters' => [$originalInput, $roundedValue],
            ];
        }

        if ($roundedScaledThousandths > 99500) {
            $messages[] = [
                'code' => self::MESSAGE_GARAN_DISABLED_DURATION_OUT_OF_RANGE,
                'parameters' => [$roundedValue],
            ];

            return [
                'normalizedValue' => '',
                'storedValue' => $roundedValue,
                'messages' => $messages,
            ];
        }

        if ($roundedScaledThousandths < 2500) {
            $messages[] = [
                'code' => self::MESSAGE_GARAN_DISABLED_DURATION_BELOW_MINIMUM,
                'parameters' => [$roundedValue],
            ];

            return [
                'normalizedValue' => '',
                'storedValue' => $roundedValue,
                'messages' => $messages,
            ];
        }

        return [
            'normalizedValue' => $roundedValue,
            'storedValue' => $roundedValue,
            'messages' => $messages,
        ];
    }

    private function normalizeOutputDurationValue(mixed $rawValue, mixed $fallbackValue = null): string
    {
        $value = trim((string) $rawValue);

        if ('' === $value) {
            $value = trim((string) $fallbackValue);
        }

        if ('' === $value) {
            return '';
        }

        return str_replace(',', '.', $value);
    }

    private function formatHalfYearValue(int $scaledThousandths): string
    {
        $tenths = intdiv($scaledThousandths, 100);
        $wholeYears = intdiv($tenths, 10);
        $decimalTenths = $tenths % 10;

        return sprintf('%d.%d', $wholeYears, $decimalTenths);
    }

    /**
     * @param list<array{code: string, parameters: array<int, string>}> $durationMessages
     * @param list<array{code: string, parameters: array<int, string>}> $validationMessages
     */
    private function messagesRequireGaranDisable(array $durationMessages, array $validationMessages): bool
    {
        if ([] !== $validationMessages) {
            return true;
        }

        foreach ($durationMessages as $durationMessage) {
            if ($durationMessage['code'] === self::MESSAGE_GARAN_DURATION_ROUNDED_DOWN) {
                continue;
            }

            return true;
        }

        return false;
    }
}
