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
                'guaranteeDurationYears' => $this->normalizeExportDurationMonths($productData['guaranteeDurationYears'] ?? null),
                'guaranteeBrand' => trim((string) ($productData['guaranteeBrand'] ?? '')),
                'guaranteeModelIdentifier' => trim((string) ($productData['guaranteeModelIdentifier'] ?? '')),
            );
        }

        return array(
            'enableGll' => '',
            'garanOverride' => $this->normalizeCheckboxValue($variantData['garanOverride'] ?? null),
            'enableGaran' => $this->normalizeCheckboxValue($variantData['enableGaran'] ?? null),
            'guaranteeDurationYears' => $this->normalizeExportDurationMonths($variantData['guaranteeDurationYears'] ?? null),
            'guaranteeBrand' => trim((string) ($variantData['guaranteeBrand'] ?? '')),
            'guaranteeModelIdentifier' => trim((string) ($variantData['guaranteeModelIdentifier'] ?? '')),
        );
    }

    /**
     * @param list<array{code: string, field: string, parameters: array<int, string>}> $messages
     */
    public function hasBlockingWriteMessages(array $messages): bool
    {
        return [] !== $messages;
    }

    /**
     * @param list<array{code: string, field: string, parameters: array<int, string>}> $messages
     */
    public function formatWriteErrorMessage(array $messages): string
    {
        $formattedMessages = array();

        foreach ($messages as $message) {
            $formattedMessages[] = sprintf(
                'field "%s": %s',
                $message['field'] ?? 'enableGaran',
                $this->formatWriteErrorDetail($message)
            );
        }

        return implode('; ', $formattedMessages);
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
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION => 'guaranteeDurationYears',
            default => 'enableGaran',
        };
    }

    private function normalizeCheckboxValue(mixed $value): string
    {
        return in_array($value, array(null, '', 0, '0', false), true) ? '' : '1';
    }

    /**
     * @param array{code?: string, parameters?: array<int, string>} $message
     */
    private function formatWriteErrorDetail(array $message): string
    {
        $parameters = isset($message['parameters']) && is_array($message['parameters'])
            ? $message['parameters']
            : array();

        return match ($message['code'] ?? '') {
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND => 'GARAN was not saved because "Brand/Trademark" is missing.',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL => 'GARAN was not saved because "Model identifier" is missing.',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION => 'GARAN was not saved because the guarantee duration is missing.',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION => sprintf(
                'GARAN was not saved because "%s" is not an allowed guarantee duration. Use whole months from 30 to 360 in steps of 6.',
                $parameters[0] ?? ''
            ),
            default => 'GARAN data is invalid.',
        };
    }

    private function normalizeExportDurationMonths(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $normalizedValue = str_replace(',', '.', $value);

        if (!is_numeric($normalizedValue)) {
            return '';
        }

        return (string) ((int) round(((float) $normalizedValue) * 12));
    }
}
