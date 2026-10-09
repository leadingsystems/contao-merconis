<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

use Contao\Input;
use Contao\Message;

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

    public function isTruthy(mixed $value): bool
    {
        return !in_array($value, [null, '', 0, '0', false], true);
    }

    /**
     * @param list<string> $fieldNames
     *
     * @return array<string, mixed>
     */
    public function readPostedFieldValues(array $fieldNames): array
    {
        $postedValues = [];

        foreach ($fieldNames as $fieldName) {
            $postedValue = Input::post($fieldName);

            if ($postedValue === null) {
                continue;
            }

            $postedValues[$fieldName] = $postedValue;
        }

        return $postedValues;
    }

    /**
     * @param array<string, mixed> $currentData
     * @param array<string, mixed> $postedValues
     * @param list<string> $acceptedFieldNames
     *
     * @return array<string, mixed>
     */
    public function mergePostedFieldValues(array $currentData, array $postedValues, array $acceptedFieldNames): array
    {
        foreach ($acceptedFieldNames as $fieldName) {
            if (!array_key_exists($fieldName, $postedValues)) {
                continue;
            }

            $currentData[$fieldName] = $postedValues[$fieldName];
        }

        return $currentData;
    }

    /**
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    public function getMessagesForField(array $messages, string $fieldName): array
    {
        $fieldMessages = [];

        foreach ($messages as $message) {
            if ($this->getFieldNameForMessageCode($message['code']) !== $fieldName) {
                continue;
            }

            $fieldMessages[] = $message;
        }

        return $fieldMessages;
    }

    /**
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    public function getUnmappedMessages(array $messages): array
    {
        $unmappedMessages = [];

        foreach ($messages as $message) {
            if ($this->getFieldNameForMessageCode($message['code']) !== null) {
                continue;
            }

            $unmappedMessages[] = $message;
        }

        return $unmappedMessages;
    }

    /**
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     * @param list<string> $visibleFieldNames
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    public function getGlobalMessages(array $messages, array $visibleFieldNames): array
    {
        $globalMessages = [];

        foreach ($messages as $message) {
            $mappedFieldName = $this->getFieldNameForMessageCode($message['code']);

            if ($mappedFieldName !== null && in_array($mappedFieldName, $visibleFieldNames, true)) {
                continue;
            }

            $globalMessages[] = $message;
        }

        return $globalMessages;
    }

    /**
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     */
    public function addErrorMessages(array $messages, string $languageScope): void
    {
        foreach ($messages as $message) {
            $messageText = $this->buildMessageText($message, $languageScope);

            if ($messageText === null) {
                continue;
            }

            Message::addError($messageText);
        }
    }

    /**
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     */
    public function getFirstMessageText(array $messages, string $languageScope): ?string
    {
        $message = reset($messages);

        if (!is_array($message)) {
            return null;
        }

        return $this->buildMessageText($message, $languageScope);
    }

    /**
     * @param array{code: string, parameters: array<int, string>} $message
     */
    private function buildMessageText(array $message, string $languageScope): ?string
    {
        $messageTemplate = $GLOBALS['TL_LANG'][$languageScope]['guaranteeValidationMessages'][$message['code']] ?? null;

        if ($messageTemplate === null) {
            return null;
        }

        return vsprintf($messageTemplate, $message['parameters']);
    }

    private function formatStoredYearsFromMonths(int $months): string
    {
        if (0 === $months % 12) {
            return (string) intdiv($months, 12);
        }

        return number_format($months / 12, 1, '.', '');
    }
}
