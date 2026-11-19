<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupLgnMode;

final class ProductGuaranteeConfigurationValidator
{
    public const MESSAGE_LGN_INVALID_MODE = 'lgn_invalid_mode';
    public const MESSAGE_GARAN_INVALID_MODE = 'garan_invalid_mode';
    public const MESSAGE_GARAN_DISABLED_MISSING_BRAND = 'garan_disabled_missing_brand';
    public const MESSAGE_GARAN_DISABLED_MISSING_MODEL = 'garan_disabled_missing_model';
    public const MESSAGE_GARAN_DISABLED_MISSING_DURATION = 'garan_disabled_missing_duration';
    public const MESSAGE_GARAN_DISABLED_INVALID_DURATION = 'garan_disabled_invalid_duration';
    public const MESSAGE_GARAN_DISABLED_LGN_REQUIRED = 'garan_disabled_lgn_required';
    public const MESSAGE_GARAN_DISABLED_LGN_REQUIRED_ALWAYS = 'garan_disabled_lgn_required_always';
    public const MESSAGE_GARAN_OVERRIDE_LGN_REQUIRED = 'garan_override_lgn_required';
    public const MESSAGE_GARAN_DISABLED_LGN_VARIANT_OVERRIDE = 'garan_disabled_lgn_variant_override';

    public const PRODUCT_LGN_MODES = ['inherit', 'always', 'never'];

    public function __construct(
        private readonly ProductGuaranteeValidationHelper $validationHelper = new ProductGuaranteeValidationHelper(),
        private readonly StandardMemberGroupLgnMode $standardMemberGroupLgnMode = new StandardMemberGroupLgnMode(),
    ) {
    }

    /**
     * Prüft eine Produktzeile aus Import oder Product Management API im Zustand nach dem
     * Schreiben (Spec 27, Abschnitte 4 und 10). Bezug für "LGN aus" ist der gespeicherte
     * Wert der Standard-Benutzergruppe; `$relatedVariants` ist der gespeicherte Stand der
     * Varianten. Der Validator ändert keine Werte; eine leere oder fehlende Angabe für
     * `lgnMode` ergibt `inherit`.
     *
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
        $normalizedData['lgnMode'] = $this->normalizeProductLgnModeValue($productData['lgnMode'] ?? null);
        $durationResult = $this->validationHelper->normalizeDurationInput(
            $rawDurationInput ?? ($productData['guaranteeDurationMonths'] ?? null)
        );
        $normalizedData['guaranteeDurationMonths'] = $durationResult['storedValue'];
        $effectiveData = [
            'enableGaran' => $normalizedData['enableGaran'],
            'guaranteeDurationMonths' => $durationResult['effectiveValue'],
            'guaranteeBrand' => $normalizedData['guaranteeBrand'],
            'guaranteeModelIdentifier' => $normalizedData['guaranteeModelIdentifier'],
        ];
        $messages = $durationResult['messages'];

        $lgnModeMessages = $this->validateProductLgnMode($productData['lgnMode'] ?? null);
        $lgnMessages = [] === $lgnModeMessages
            ? $this->validateProductLgnDependency($normalizedData, $relatedVariants)
            : $lgnModeMessages;

        if ($lgnMessages !== array()) {
            $messages = array_merge($messages, $lgnMessages);
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
     * R3: `override` wird abgelehnt, wenn für `lgnMode` des Elternprodukts "LGN aus" gilt.
     * Fehlt `lgnMode` in `$productData`, gilt `inherit`.
     *
     * @param array<string, mixed> $variantData
     * @param array<string, mixed> $productData
     */
    public function validateVariant(array $variantData, array $productData, mixed $rawDurationInput = null): GuaranteeValidationResult
    {
        $normalizedVariantData = $this->normalizeVariantData($variantData);
        $normalizedProductData = $this->normalizeSharedFields($productData);
        $durationResult = $this->validationHelper->normalizeDurationInput(
            $rawDurationInput ?? ($variantData['guaranteeDurationMonths'] ?? null)
        );
        $normalizedVariantData['guaranteeDurationMonths'] = $durationResult['storedValue'];
        $effectiveData = $this->buildEffectiveVariantData(
            $normalizedVariantData,
            $normalizedProductData,
            $durationResult['effectiveValue'],
        );
        $messages = $durationResult['messages'];

        $modeMessages = $this->validateVariantMode($variantData['garanMode'] ?? null);

        if ([] !== $modeMessages) {
            $messages = array_merge($messages, $modeMessages);
        }

        if (
            'override' === ($normalizedVariantData['garanMode'] ?? 'inherit')
            && $this->standardMemberGroupLgnMode->isLgnOffForProduct($productData['lgnMode'] ?? null)
        ) {
            $messages[] = [
                'code' => self::MESSAGE_GARAN_OVERRIDE_LGN_REQUIRED,
                'parameters' => [],
            ];
        } elseif ('override' === ($normalizedVariantData['garanMode'] ?? 'inherit')) {
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

        return $productProducer;
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
        $data['enableGaran'] = $this->normalizeCheckboxValue($data['enableGaran'] ?? null);
        $data['guaranteeBrand'] = $this->normalizeTextValue($data['guaranteeBrand'] ?? null);
        $data['guaranteeModelIdentifier'] = $this->normalizeTextValue($data['guaranteeModelIdentifier'] ?? null);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalizeVariantData(array $data): array
    {
        $data = $this->normalizeSharedFields($data);
        $data['garanMode'] = $this->normalizeVariantModeValue($data['garanMode'] ?? null);

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
            '' === ($effectiveData['guaranteeDurationMonths'] ?? '')
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
     * @param array<string, mixed> $variantData
     * @param array<string, mixed> $productData
     *
     * @return array<string, mixed>
     */
    private function buildEffectiveVariantData(
        array $variantData,
        array $productData,
        string $normalizedVariantDurationMonths,
    ): array {
        $variantMode = $variantData['garanMode'] ?? 'inherit';

        return match ($variantMode) {
            'disabled' => [
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            'override' => [
                'enableGaran' => '1',
                'guaranteeDurationMonths' => $normalizedVariantDurationMonths,
                'guaranteeBrand' => $this->normalizeTextValue($variantData['guaranteeBrand'] ?? null),
                'guaranteeModelIdentifier' => $this->normalizeTextValue(
                    $variantData['guaranteeModelIdentifier'] ?? null
                ),
            ],
            default => [
                'enableGaran' => $this->normalizeCheckboxValue($productData['enableGaran'] ?? null),
                'guaranteeDurationMonths' => $this->normalizeStoredDurationValue(
                    $productData['guaranteeDurationMonths'] ?? null
                ),
                'guaranteeBrand' => $this->normalizeTextValue($productData['guaranteeBrand'] ?? null),
                'guaranteeModelIdentifier' => $this->normalizeTextValue(
                    $productData['guaranteeModelIdentifier'] ?? null
                ),
            ],
        };
    }

    public function normalizeTextValue(mixed $value): string
    {
        return trim((string) $value);
    }

    private function normalizeStoredDurationValue(mixed $rawValue): string
    {
        return trim((string) $rawValue);
    }

    /**
     * @param array<string, mixed> $productData
     * @param list<array<string, mixed>> $relatedVariants
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    private function validateProductLgnDependency(array $productData, array $relatedVariants): array
    {
        $standardMemberGroupLgnMode = $this->standardMemberGroupLgnMode->getStoredLgnMode();

        if (!$this->standardMemberGroupLgnMode->isLgnOff($standardMemberGroupLgnMode, $productData['lgnMode'])) {
            return array();
        }

        $messages = [];
        $affectedVariantIdentifiers = $this->getOverrideVariantIdentifiers($relatedVariants);

        if ($affectedVariantIdentifiers !== array()) {
            $messages[] = [
                'code' => self::MESSAGE_GARAN_DISABLED_LGN_VARIANT_OVERRIDE,
                'parameters' => [implode(', ', $affectedVariantIdentifiers)],
            ];
        }

        if ($this->isTruthy($productData['enableGaran'] ?? null)) {
            $messages[] = [
                'code' => 'always' === $this->standardMemberGroupLgnMode->getLgnModeForEnabledGaran($standardMemberGroupLgnMode)
                    ? self::MESSAGE_GARAN_DISABLED_LGN_REQUIRED_ALWAYS
                    : self::MESSAGE_GARAN_DISABLED_LGN_REQUIRED,
                'parameters' => [],
            ];
        }

        return $messages;
    }

    /**
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    private function validateProductLgnMode(mixed $rawMode): array
    {
        $rawMode = trim((string) $rawMode);

        if ('' === $rawMode || in_array($rawMode, self::PRODUCT_LGN_MODES, true)) {
            return [];
        }

        return [[
            'code' => self::MESSAGE_LGN_INVALID_MODE,
            'parameters' => [$rawMode],
        ]];
    }

    /**
     * Ein ungültiger Wert wird abgelehnt (`validateProductLgnMode()`) und nie geschrieben.
     */
    private function normalizeProductLgnModeValue(mixed $value): string
    {
        $mode = trim((string) $value);

        return in_array($mode, self::PRODUCT_LGN_MODES, true)
            ? $mode
            : 'inherit';
    }

    /**
     * Kennungen der Varianten auf `override`: Artikelnummer, sonst Bezeichnung, sonst `#<id>`.
     *
     * @param list<array<string, mixed>> $relatedVariants
     *
     * @return list<string>
     */
    public function getOverrideVariantIdentifiers(array $relatedVariants): array
    {
        $identifiers = [];

        foreach ($relatedVariants as $variantData) {
            if ('override' !== $this->normalizeVariantModeValue($variantData['garanMode'] ?? null)) {
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

    /**
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    private function validateVariantMode(mixed $rawMode): array
    {
        $rawMode = trim((string) $rawMode);

        if ('' === $rawMode || in_array($rawMode, ['inherit', 'disabled', 'override'], true)) {
            return [];
        }

        return [[
            'code' => self::MESSAGE_GARAN_INVALID_MODE,
            'parameters' => [$rawMode],
        ]];
    }

    private function normalizeVariantModeValue(mixed $value): string
    {
        $mode = trim((string) $value);

        return in_array($mode, ['inherit', 'disabled', 'override'], true)
            ? $mode
            : 'inherit';
    }
}
