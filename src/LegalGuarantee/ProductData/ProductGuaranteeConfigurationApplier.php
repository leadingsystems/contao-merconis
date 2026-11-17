<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

final class ProductGuaranteeConfigurationApplier
{
    public function __construct(
        private readonly ProductGuaranteeConfigurationValidator $validator = new ProductGuaranteeConfigurationValidator(),
        private readonly ProductGuaranteeValidationHelper $validationHelper = new ProductGuaranteeValidationHelper(),
    ) {
    }

    /**
     * @param array<string, mixed> $row
     * @param list<array<string, mixed>> $relatedVariants
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
        array $relatedVariants = array(),
        mixed $rawDurationInput = null,
    ): array {
        if (
            $applyGuaranteeBrandDefault
            && '' === trim((string) ($row['guaranteeBrand'] ?? ''))
        ) {
            $row['guaranteeBrand'] = $this->validator->getInitialGuaranteeBrand(
                (string) ($row[$producerFieldName] ?? '')
            );
        }

        $result = $this->validator->validateProduct($row, $relatedVariants, $rawDurationInput);

        foreach (
            ['gllMode', 'enableGaran', 'guaranteeDurationMonths', 'guaranteeBrand', 'guaranteeModelIdentifier']
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
    public function applyToVariantRow(array $variantRow, array $productData, mixed $rawDurationInput = null): array
    {
        $result = $this->validator->validateVariant($variantRow, $productData, $rawDurationInput);

        foreach (
            array('garanMode', 'guaranteeDurationMonths', 'guaranteeBrand', 'guaranteeModelIdentifier')
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
     * @param list<array{code: string, field: string, parameters: array<int, string>}> $messages
     */
    public function hasBlockingWriteMessages(array $messages): bool
    {
        return [] !== $messages;
    }

    /**
     * Meldung der Product Management API. Die Texte stehen in
     * `$GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuaranteeImport']`; der Aufrufer stellt
     * sicher, dass die Sprachdatei `default` geladen ist.
     *
     * @param list<array{code: string, field: string, parameters: array<int, string>}> $messages
     */
    public function formatWriteErrorMessage(array $messages): string
    {
        $formattedMessages = array();

        foreach ($messages as $message) {
            $formattedMessages[] = sprintf(
                $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuaranteeImport']['apiMessage'],
                $message['field'],
                $this->formatMessageText($message)
            );
        }

        return implode('; ', $formattedMessages);
    }

    /**
     * Text einer Meldung ohne Zeile und Feld, für Import-Log und Product Management API.
     *
     * @param array{code: string, parameters: array<int, string>} $message
     */
    public function formatMessageText(array $message): string
    {
        return vsprintf(
            $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuaranteeImport']['messages'][$message['code']],
            $message['parameters']
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
                'field' => $this->validationHelper->getFieldNameForMessageCode($message['code']) ?? 'enableGaran',
                'parameters' => $message['parameters'],
            );
        }

        return $decoratedMessages;
    }
}
