<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

final class ProductGuaranteeConfigurationValidator
{
    public const MESSAGE_GARAN_DISABLED_MISSING_BRAND = 'garan_disabled_missing_brand';
    public const MESSAGE_GARAN_DISABLED_MISSING_MODEL = 'garan_disabled_missing_model';
    public const MESSAGE_GARAN_DISABLED_MISSING_DURATION = 'garan_disabled_missing_duration';
    public const MESSAGE_GARAN_DISABLED_INVALID_DURATION = 'garan_disabled_invalid_duration';
    public const MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG = 'garan_disabled_brand_too_long';
    public const MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG = 'garan_disabled_model_too_long';
    public const MESSAGE_GARAN_DISABLED_GLL_REQUIRED = 'garan_disabled_gll_required';
    public const MESSAGE_GARAN_INHERITED_GLL_REQUIRED = 'garan_inherited_gll_required';
    public const MESSAGE_GARAN_DISABLED_GLL_VARIANT_OVERRIDE = 'garan_disabled_gll_variant_override';
    private const GUARANTEE_BRAND_MAX_LENGTH = 40;
    private const GUARANTEE_MODEL_IDENTIFIER_MAX_LENGTH = 25;

    public function __construct(
        private readonly ProductGuaranteeValidationHelper $validationHelper = new ProductGuaranteeValidationHelper(),
    ) {
    }

    /**
     * @param array<string, mixed> $productData
     * @param list<array<string, mixed>> $relatedVariants
     */
    public function validateProduct(
        array $productData,
        array $relatedVariants = array(),
        mixed $rawDurationInput = null
    ): GuaranteeValidationResult
    {
        $normalizedData = $this->normalizeSharedFields($productData);
        $durationResult = $this->validationHelper->normalizeDurationInput(
            $rawDurationInput ?? ($productData['guaranteeDurationYears'] ?? null)
        );
        $normalizedData['guaranteeDurationYears'] = $durationResult['storedValue'];
        $effectiveData = [
            'enableGaran' => $normalizedData['enableGaran'],
            'guaranteeDurationYears' => $durationResult['effectiveValue'],
            'guaranteeBrand' => $normalizedData['guaranteeBrand'],
            'guaranteeModelIdentifier' => $normalizedData['guaranteeModelIdentifier'],
        ];
        $messages = array_merge(
            $durationResult['messages'],
            $this->validateBrandAndModelLengthRequirements($effectiveData)
        );

        $gllMessages = $this->validateProductGllDependency($normalizedData, $relatedVariants);

        if ($gllMessages !== array()) {
            $messages = array_merge($messages, $gllMessages);
        } elseif ($this->isTruthy($normalizedData['enableGaran'] ?? null)) {
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
    public function validateVariant(array $variantData, array $productData, mixed $rawDurationInput = null): GuaranteeValidationResult
    {
        $normalizedVariantData = $this->normalizeSharedFields($variantData);
        $normalizedProductData = $this->normalizeSharedFields($productData);
        $durationResult = $this->validationHelper->normalizeDurationInput(
            $rawDurationInput ?? ($variantData['guaranteeDurationYears'] ?? null)
        );
        $normalizedVariantData['guaranteeDurationYears'] = $durationResult['storedValue'];
        $effectiveData = $this->buildEffectiveVariantData(
            $normalizedVariantData,
            $normalizedProductData,
            $durationResult['effectiveValue'],
        );
        $messages = array_merge(
            $durationResult['messages'],
            $this->validateVariantBrandAndModelLengthRequirements($normalizedVariantData, $effectiveData)
        );

        if (
            $this->isTruthy($effectiveData['enableGaran'] ?? null)
            && !$this->isTruthy($normalizedProductData['enableGll'] ?? null)
        ) {
            $messages[] = [
                'code' => $this->isTruthy($normalizedVariantData['garanOverride'] ?? null)
                    && $this->isTruthy($normalizedVariantData['enableGaran'] ?? null)
                    ? self::MESSAGE_GARAN_DISABLED_GLL_REQUIRED
                    : self::MESSAGE_GARAN_INHERITED_GLL_REQUIRED,
                'parameters' => [],
            ];
        } elseif ($this->isTruthy($normalizedVariantData['garanOverride'] ?? null)
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
        $productProducer = $this->normalizeTextValue($productProducer);

        if ('' === $productProducer) {
            return '';
        }

        return mb_substr($productProducer, 0, 40);
    }

    public function getFieldNameForMessageCode(string $messageCode): ?string
    {
        return $this->validationHelper->getFieldNameForMessageCode($messageCode);
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
        $data['guaranteeBrand'] = $this->normalizeTextValue($data['guaranteeBrand'] ?? null);
        $data['guaranteeModelIdentifier'] = $this->normalizeTextValue($data['guaranteeModelIdentifier'] ?? null);

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
        $messages = $this->validateBrandAndModelPresenceRequirements($effectiveData);

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
    private function validateBrandAndModelPresenceRequirements(array $effectiveData): array
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
     * @param array<string, mixed> $effectiveData
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    private function validateBrandAndModelLengthRequirements(array $effectiveData): array
    {
        return array_values($this->getBrandAndModelLengthMessages($effectiveData));
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
                'guaranteeBrand' => $this->normalizeTextValue($productData['guaranteeBrand'] ?? null),
                'guaranteeModelIdentifier' => $this->normalizeTextValue(
                    $productData['guaranteeModelIdentifier'] ?? null
                ),
            ];
        }

        return [
            'enableGaran' => $this->normalizeCheckboxValue($variantData['enableGaran'] ?? null),
            'guaranteeDurationYears' => '' !== $normalizedVariantDuration
                ? $normalizedVariantDuration
                : $this->normalizeStoredDurationValue($productData['guaranteeDurationYears'] ?? null),
            'guaranteeBrand' => '' !== ($variantData['guaranteeBrand'] ?? '')
                ? $variantData['guaranteeBrand']
                : $this->normalizeTextValue($productData['guaranteeBrand'] ?? null),
            'guaranteeModelIdentifier' => '' !== ($variantData['guaranteeModelIdentifier'] ?? '')
                ? $variantData['guaranteeModelIdentifier']
                : $this->normalizeTextValue($productData['guaranteeModelIdentifier'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $normalizedVariantData
     * @param array<string, mixed> $effectiveData
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    private function validateVariantBrandAndModelLengthRequirements(
        array $normalizedVariantData,
        array $effectiveData
    ): array {
        $messagesByField = $this->getBrandAndModelLengthMessages($normalizedVariantData);

        foreach (['guaranteeBrand', 'guaranteeModelIdentifier'] as $fieldName) {
            if (($normalizedVariantData[$fieldName] ?? '') === ($effectiveData[$fieldName] ?? '')) {
                continue;
            }

            $effectiveFieldMessage = $this->getLengthMessageForField(
                $fieldName,
                (string) ($effectiveData[$fieldName] ?? '')
            );

            if ($effectiveFieldMessage === null) {
                continue;
            }

            $messagesByField[$fieldName] = $effectiveFieldMessage;
        }

        return array_values($messagesByField);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, array{code: string, parameters: array<int, string>}>
     */
    private function getBrandAndModelLengthMessages(array $data): array
    {
        $messages = [];

        foreach (['guaranteeBrand', 'guaranteeModelIdentifier'] as $fieldName) {
            $fieldMessage = $this->getLengthMessageForField($fieldName, (string) ($data[$fieldName] ?? ''));

            if ($fieldMessage === null) {
                continue;
            }

            $messages[$fieldName] = $fieldMessage;
        }

        return $messages;
    }

    /**
     * @return array{code: string, parameters: array<int, string>}|null
     */
    private function getLengthMessageForField(string $fieldName, string $value): ?array
    {
        return match ($fieldName) {
            'guaranteeBrand' => mb_strlen($value) > self::GUARANTEE_BRAND_MAX_LENGTH
                ? [
                    'code' => self::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                    'parameters' => [(string) self::GUARANTEE_BRAND_MAX_LENGTH],
                ]
                : null,
            'guaranteeModelIdentifier' => mb_strlen($value) > self::GUARANTEE_MODEL_IDENTIFIER_MAX_LENGTH
                ? [
                    'code' => self::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
                    'parameters' => [(string) self::GUARANTEE_MODEL_IDENTIFIER_MAX_LENGTH],
                ]
                : null,
            default => null,
        };
    }

    public function normalizeTextValue(mixed $value): string
    {
        return trim(
            html_entity_decode(
                (string) $value,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );
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

    /**
     * @param array<string, mixed> $productData
     * @param list<array<string, mixed>> $relatedVariants
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    private function validateProductGllDependency(array $productData, array $relatedVariants): array
    {
        if ($this->isTruthy($productData['enableGll'] ?? null)) {
            return array();
        }

        $affectedVariantIdentifiers = $this->getAffectedVariantIdentifiers($relatedVariants);

        if ($affectedVariantIdentifiers !== array()) {
            return [[
                'code' => self::MESSAGE_GARAN_DISABLED_GLL_VARIANT_OVERRIDE,
                'parameters' => [implode(', ', $affectedVariantIdentifiers)],
            ]];
        }

        if (!$this->isTruthy($productData['enableGaran'] ?? null)) {
            return array();
        }

        return [[
            'code' => self::MESSAGE_GARAN_DISABLED_GLL_REQUIRED,
            'parameters' => [],
        ]];
    }

    /**
     * @param list<array<string, mixed>> $relatedVariants
     *
     * @return list<string>
     */
    private function getAffectedVariantIdentifiers(array $relatedVariants): array
    {
        $identifiers = array();

        foreach ($relatedVariants as $variantData) {
            if (
                !$this->isTruthy($variantData['garanOverride'] ?? null)
                || !$this->isTruthy($variantData['enableGaran'] ?? null)
            ) {
                continue;
            }

            $identifier = trim((string) ($variantData['lsShopVariantCode'] ?? ''));

            if ($identifier === '') {
                $identifier = trim((string) ($variantData['title'] ?? ''));
            }

            if ($identifier === '' && isset($variantData['id'])) {
                $identifier = '#'.(string) $variantData['id'];
            }

            if ($identifier === '') {
                continue;
            }

            $identifiers[] = $identifier;
        }

        return array_values(array_unique($identifiers));
    }
}
