<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

final class ProductGuaranteeConfigurationApplier
{
    public function __construct(
        private readonly ProductGuaranteeConfigurationValidator $validator = new ProductGuaranteeConfigurationValidator(),
    ) {
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{
     *   row: array<string, mixed>,
     *   messages: list<array{code: string, field: string, parameters: array<int, string>}>
     * }
     */
    public function applyToProductRow(
        array $row,
        bool $applyGuaranteeBrandDefault = true,
        string $producerFieldName = 'producer',
    ): array {
        if (
            $applyGuaranteeBrandDefault
            && '' === trim((string) ($row['guaranteeBrand'] ?? ''))
        ) {
            $row['guaranteeBrand'] = $this->validator->getInitialGuaranteeBrand(
                (string) ($row[$producerFieldName] ?? '')
            );
        }

        $result = $this->validator->validateProduct($row);

        foreach (
            array('enableGll', 'enableGaran', 'guaranteeDurationYears', 'guaranteeBrand', 'guaranteeModelIdentifier')
            as $fieldName
        ) {
            $row[$fieldName] = $result->getNormalizedData()[$fieldName] ?? null;
        }

        return array(
            'row' => $row,
            'messages' => $this->decorateMessages($result->getMessages()),
        );
    }

    /**
     * @param array<string, mixed> $variantRow
     * @param array<string, mixed> $productData
     *
     * @return array{
     *   row: array<string, mixed>,
     *   messages: list<array{code: string, field: string, parameters: array<int, string>}>
     * }
     */
    public function applyToVariantRow(array $variantRow, array $productData): array
    {
        $result = $this->validator->validateVariant($variantRow, $productData);

        foreach (
            array('garanOverride', 'enableGaran', 'guaranteeDurationYears', 'guaranteeBrand', 'guaranteeModelIdentifier')
            as $fieldName
        ) {
            $variantRow[$fieldName] = $result->getNormalizedData()[$fieldName] ?? null;
        }

        return array(
            'row' => $variantRow,
            'messages' => $this->decorateMessages($result->getMessages()),
        );
    }

    /**
     * @param array<string, mixed> $productData
     * @param array<string, mixed>|null $variantData
     *
     * @return array<string, mixed>
     */
    public function buildExportColumns(array $productData, ?array $variantData = null): array
    {
        if ($variantData === null) {
            return array(
                'enableGll' => $this->normalizeCheckboxValue($productData['enableGll'] ?? null),
                'garanOverride' => '',
                'enableGaran' => $this->normalizeCheckboxValue($productData['enableGaran'] ?? null),
                'guaranteeDurationYears' => $this->normalizeExportScalar($productData['guaranteeDurationYears'] ?? null),
                'guaranteeBrand' => trim((string) ($productData['guaranteeBrand'] ?? '')),
                'guaranteeModelIdentifier' => trim((string) ($productData['guaranteeModelIdentifier'] ?? '')),
            );
        }

        return array(
            'enableGll' => '',
            'garanOverride' => $this->normalizeCheckboxValue($variantData['garanOverride'] ?? null),
            'enableGaran' => $this->normalizeCheckboxValue($variantData['enableGaran'] ?? null),
            'guaranteeDurationYears' => $this->normalizeExportScalar($variantData['guaranteeDurationYears'] ?? null),
            'guaranteeBrand' => trim((string) ($variantData['guaranteeBrand'] ?? '')),
            'guaranteeModelIdentifier' => trim((string) ($variantData['guaranteeModelIdentifier'] ?? '')),
        );
    }

    /**
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     *
     * @return list<array{code: string, field: string, parameters: array<int, string>}>
     */
    private function decorateMessages(array $messages): array
    {
        $decoratedMessages = array();

        foreach ($messages as $message) {
            $decoratedMessages[] = array(
                'code' => $message['code'],
                'field' => $this->getFieldNameForMessageCode($message['code']),
                'parameters' => $message['parameters'],
            );
        }

        return $decoratedMessages;
    }

    private function getFieldNameForMessageCode(string $messageCode): string
    {
        return match ($messageCode) {
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND => 'guaranteeBrand',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL => 'guaranteeModelIdentifier',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_DURATION_OUT_OF_RANGE,
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_DURATION_BELOW_MINIMUM,
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DURATION_ROUNDED_DOWN => 'guaranteeDurationYears',
            default => 'enableGaran',
        };
    }

    private function normalizeCheckboxValue(mixed $value): string
    {
        return in_array($value, array(null, '', 0, '0', false), true) ? '' : '1';
    }

    private function normalizeExportScalar(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return str_replace(',', '.', $value);
    }
}
