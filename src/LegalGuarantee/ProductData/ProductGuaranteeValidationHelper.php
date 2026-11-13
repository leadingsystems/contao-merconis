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

        return [
            'effectiveValue' => (string) $months,
            'storedValue' => (string) $months,
            'messages' => [],
        ];
    }

    public function getFieldNameForMessageCode(string $messageCode): ?string
    {
        return match ($messageCode) {
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED => 'enableGaran',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_VARIANT_OVERRIDE => 'enableGll',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INVALID_MODE,
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_GLL_REQUIRED => 'garanMode',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND => 'guaranteeBrand',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL => 'guaranteeModelIdentifier',
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION => 'guaranteeDurationMonths',
            default => null,
        };
    }

    public function isTruthy(mixed $value): bool
    {
        return !in_array($value, [null, '', 0, '0', false], true);
    }

    /**
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    public function getMessagesForField(array $messages, string $fieldName): array
    {
        return $this->getMessagesForFields($messages, [$fieldName]);
    }

    /**
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     * @param list<string> $fieldNames
     *
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    public function getMessagesForFields(array $messages, array $fieldNames): array
    {
        $fieldMessages = [];

        foreach ($messages as $message) {
            if (!in_array($this->getFieldNameForMessageCode($message['code']), $fieldNames, true)) {
                continue;
            }

            $fieldMessages[] = $message;
        }

        return $fieldMessages;
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
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     *
     * @throws \Exception
     */
    public function throwValidationExceptionForField(
        array $messages,
        string $fieldName,
        string $languageScope
    ): void {
        $messageText = $this->getFirstMessageText(
            $this->getMessagesForField($messages, $fieldName),
            $languageScope
        );

        if ($messageText === null) {
            return;
        }

        throw new \Exception($messageText);
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
}
