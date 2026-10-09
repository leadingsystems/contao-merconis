<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Contao;

use Contao\Database;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;

final class EInvoicingCheckoutSnapshotHook
{
    private const DEFAULT_PAYMENT_MEANS_CODE = '58';

    /**
     * Persistiert den Payment Means Code im Bestell-Snapshot,
     * nachdem der Bestelldatensatz angelegt wurde.
     *
     * @param array<string, mixed> $order
     */
    public function afterCheckout(int $orderIdInDb, array $order): void
    {
        if ($orderIdInDb <= 0) {
            return;
        }

        $paymentMethodId = (int) ($order['paymentMethod']['id'] ?? 0);
        $paymentMethod = $this->loadRecordById('tl_ls_shop_payment_methods', $paymentMethodId);
        $meansCode = $this->optionalString($paymentMethod[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE] ?? null)
            ?? self::DEFAULT_PAYMENT_MEANS_CODE;

        Database::getInstance()
            ->prepare(
                'UPDATE `tl_ls_shop_orders` SET `'
                . EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE
                . '` = ? WHERE `id` = ?'
            )
            ->execute($meansCode, $orderIdInDb);

        $this->clearCachedOrder($orderIdInDb, $order['orderIdentificationHash'] ?? null);
    }

    /**
     * Ergänzt den Positions-Snapshot im `extendedInfo` um E-Invoicing-Daten.
     *
     * @param array<string, mixed> $cartItem
     * @param object               $product
     *
     * @return array<string, mixed>
     */
    public function storeCartItemInOrder(array $cartItem, object $product): array
    {
        $taxClassId = (int) ($cartItem['taxClass'] ?? 0);
        $taxRate = $this->floatValue($cartItem['taxPercentage'] ?? null);
        $taxConfiguration = $this->loadRecordById('tl_ls_shop_steuersaetze', $taxClassId);

        $extendedInfo = is_array($cartItem['extendedInfo'] ?? null) ? $cartItem['extendedInfo'] : [];
        $taxCategoryCode = $this->resolveTaxCategoryCode($taxConfiguration, $taxRate);

        if ($taxCategoryCode !== null) {
            $extendedInfo[EInvoicingSnapshotFields::ITEM_TAX_CATEGORY_CODE] = $taxCategoryCode;
        } else {
            unset($extendedInfo[EInvoicingSnapshotFields::ITEM_TAX_CATEGORY_CODE]);
        }

        $taxExemptionReason = $this->optionalString(
            $taxConfiguration[EInvoicingSnapshotFields::ITEM_TAX_EXEMPTION_REASON] ?? null
        );
        $taxExemptionReasonCode = $this->optionalString(
            $taxConfiguration[EInvoicingSnapshotFields::ITEM_TAX_EXEMPTION_REASON_CODE] ?? null
        );

        if ($taxExemptionReason !== null) {
            $extendedInfo[EInvoicingSnapshotFields::ITEM_TAX_EXEMPTION_REASON] = $taxExemptionReason;
        } else {
            unset($extendedInfo[EInvoicingSnapshotFields::ITEM_TAX_EXEMPTION_REASON]);
        }

        if ($taxExemptionReasonCode !== null) {
            $extendedInfo[EInvoicingSnapshotFields::ITEM_TAX_EXEMPTION_REASON_CODE] = $taxExemptionReasonCode;
        } else {
            unset($extendedInfo[EInvoicingSnapshotFields::ITEM_TAX_EXEMPTION_REASON_CODE]);
        }

        $cartItem['extendedInfo'] = $extendedInfo;

        return $cartItem;
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

    private function clearCachedOrder(int $orderId, mixed $orderIdentificationHash): void
    {
        unset($GLOBALS['merconis_globals']['order'][$orderId]);

        if (is_scalar($orderIdentificationHash) && $orderIdentificationHash !== '') {
            unset($GLOBALS['merconis_globals']['order'][(string) $orderIdentificationHash]);
        }
    }

    /**
     * @param array<string, mixed> $taxConfiguration
     */
    private function resolveTaxCategoryCode(array $taxConfiguration, ?float $taxRate): ?string
    {
        $configuredCategory = strtoupper(
            $this->optionalString($taxConfiguration['ls_shop_einvoicingTaxCategory'] ?? null) ?? 'AUTO'
        );

        if ($configuredCategory !== 'AUTO') {
            return $configuredCategory;
        }

        if ($taxRate !== null && $taxRate > 0.0) {
            return 'S';
        }

        return null;
    }

    private function optionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue === '' ? null : $normalizedValue;
    }

    private function floatValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalizedValue = trim(str_replace(',', '.', $value));

        return is_numeric($normalizedValue) ? (float) $normalizedValue : null;
    }
}
