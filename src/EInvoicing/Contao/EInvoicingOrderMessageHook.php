<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Contao;

use Contao\Config;
use Contao\Database;
use Contao\Date;
use DateInterval;
use DateTimeImmutable;

final class EInvoicingOrderMessageHook
{
    /**
     * Ersetzt E-Invoicing-Platzhalter, bevor die Merconis-Wildcards
     * für Betreff und Nachrichtentexte ausgewertet werden.
     *
     * @param array<string, mixed> $messageModel
     * @param array<string, mixed> $order
     *
     * @return array<string, mixed>
     */
    public function beforeSendingOrderMessage(array $messageModel, array $order): array
    {
        $paymentMethod = $this->resolvePaymentMethod($order);
        $memberGroup = $this->resolveMemberGroup($order);

        $language = $this->resolveLanguage($messageModel, $order);
        $termsDays = $this->optionalString($memberGroup['lsShopEinvoicingPaymentTermsDays'] ?? null)
            ?? $this->optionalString($paymentMethod['ls_shop_einvoicingPaymentTermsDays'] ?? null);
        $termsNote = $this->resolveLocalizedFieldValue($memberGroup, 'lsShopEinvoicingPaymentTermsNote', $language)
            ?? $this->resolveLocalizedFieldValue($paymentMethod, 'ls_shop_einvoicingPaymentTermsNote', $language)
            ?? '';
        $dueDate = $this->formatDueDate($termsDays);

        if (!isset($messageModel['multilanguage']) || !is_array($messageModel['multilanguage'])) {
            return $messageModel;
        }

        foreach (['subject', 'content_html', 'content_rawtext'] as $field) {
            if (!array_key_exists($field, $messageModel['multilanguage'])) {
                continue;
            }

            $messageModel['multilanguage'][$field] = $this->replacePlaceholders(
                $messageModel['multilanguage'][$field],
                [
                    'einvoicingPaymentTermsDays' => $termsDays ?? '',
                    'einvoicingPaymentTermsNote' => $termsNote,
                    'einvoicingPaymentDueDate' => $dueDate,
                ]
            );
        }

        return $messageModel;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function resolveLocalizedFieldValue(array $values, string $field, string $language): ?string
    {
        $normalizedLanguage = $this->normalizeLanguage($language);

        if ($normalizedLanguage !== '') {
            $localizedValue = $this->optionalString($values[$field . '_' . $normalizedLanguage] ?? null);

            if ($localizedValue !== null) {
                return $localizedValue;
            }
        }

        return $this->optionalString($values[$field] ?? null);
    }

    /**
     * @param array<string, mixed> $messageModel
     * @param array<string, mixed> $order
     */
    private function resolveLanguage(array $messageModel, array $order): string
    {
        $messageLanguage = $this->optionalString($messageModel['messageLanguage'] ?? null);

        if ($messageLanguage !== null) {
            return $this->normalizeLanguage($messageLanguage);
        }

        $orderLanguage = $this->optionalString($order['customerLanguage'] ?? null);

        if ($orderLanguage !== null) {
            return $this->normalizeLanguage($orderLanguage);
        }

        return 'en';
    }

    private function normalizeLanguage(string $language): string
    {
        $normalizedLanguage = strtolower(trim($language));

        if ($normalizedLanguage === '') {
            return '';
        }

        return preg_replace('/[^a-z].*$/', '', $normalizedLanguage) ?: $normalizedLanguage;
    }

    private function formatDueDate(?string $termsDays): string
    {
        if ($termsDays === null) {
            return '';
        }

        $dueDate = (new DateTimeImmutable('now'))->add(new DateInterval('P' . (int) $termsDays . 'D'));
        $dateFormat = (string) Config::get('dateFormat');

        if ($dateFormat === '') {
            $dateFormat = 'Y-m-d';
        }

        return Date::parse($dateFormat, $dueDate->getTimestamp());
    }

    /**
     * @param array<string, string> $replacements
     */
    private function replacePlaceholders(mixed $value, array $replacements): string
    {
        $text = is_scalar($value) ? (string) $value : '';

        foreach ($replacements as $placeholder => $replacement) {
            $text = preg_replace(
                '/(&#35;&#35;' . preg_quote($placeholder, '/') . '&#35;&#35;)|(##' . preg_quote($placeholder, '/') . '##)/siU',
                $replacement,
                $text
            ) ?? $text;
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadRecordById(string $table, int $id): array
    {
        if ($id <= 0) {
            return [];
        }

        $record = Database::getInstance()
            ->prepare(sprintf('SELECT * FROM `%s` WHERE `id` = ?', $table))
            ->limit(1)
            ->execute($id)
            ->row();

        return is_array($record) ? $record : [];
    }

    /**
     * @param array<string, mixed> $order
     *
     * @return array<string, mixed>
     */
    private function resolvePaymentMethod(array $order): array
    {
        if (isset($order['paymentMethod']) && is_array($order['paymentMethod'])) {
            return $order['paymentMethod'];
        }

        return $this->loadRecordById(
            'tl_ls_shop_payment_methods',
            (int) ($order['paymentMethod_id'] ?? 0)
        );
    }

    /**
     * @param array<string, mixed> $order
     *
     * @return array<string, mixed>
     */
    private function resolveMemberGroup(array $order): array
    {
        if (isset($order['memberGroup']) && is_array($order['memberGroup'])) {
            return $order['memberGroup'];
        }

        return $this->loadRecordById(
            'tl_member_group',
            (int) ($order['memberGroupInfo_id'] ?? 0)
        );
    }

    private function optionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue === '' ? null : $normalizedValue;
    }
}
