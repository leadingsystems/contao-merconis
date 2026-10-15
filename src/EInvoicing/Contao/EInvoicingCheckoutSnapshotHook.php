<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Contao;

use Contao\Database;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingCodeLists;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingTaxResolver;

final class EInvoicingCheckoutSnapshotHook
{
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

        $taxContext = EInvoicingTaxResolver::resolveSnapshotTaxContextFromOrder(
            $order,
            [
                'ls_shop_country' => $GLOBALS['TL_CONFIG']['ls_shop_country'] ?? null,
                'ls_shop_ownVATID' => $GLOBALS['TL_CONFIG']['ls_shop_ownVATID'] ?? null,
                'ls_shop_euCountrycodes' => $GLOBALS['TL_CONFIG']['ls_shop_euCountrycodes'] ?? null,
            ]
        );
        $paymentMethodId = (int) ($order['paymentMethod']['id'] ?? 0);
        $paymentMethod = $this->loadRecordById('tl_ls_shop_payment_methods', $paymentMethodId);
        $meansCode = EInvoicingCodeLists::normalizeCode(
            $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] ?? null
        ) ?? '';
        $paymentTaxCategory = $this->resolveChargeTaxCategory(
            $order['paymentMethod_amountTaxedWith'] ?? null,
            $taxContext
        );
        $shippingTaxCategory = $this->resolveChargeTaxCategory(
            $order['shippingMethod_amountTaxedWith'] ?? null,
            $taxContext
        );

        Database::getInstance()
            ->prepare(
                'UPDATE `tl_ls_shop_orders`'
                . ' SET `'
                . EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_COLUMN
                . '` = ?, `'
                . EInvoicingSnapshotFields::ORDER_TAX_COUNTRY_COLUMN
                . '` = ?, `'
                . EInvoicingSnapshotFields::ORDER_VAT_EXEMPTION_STATUS_COLUMN
                . '` = ?, `'
                . EInvoicingSnapshotFields::ORDER_SHIPPING_TAX_CATEGORY_COLUMN
                . '` = ?, `'
                . EInvoicingSnapshotFields::ORDER_PAYMENT_TAX_CATEGORY_COLUMN
                . '` = ?'
                . ' WHERE `id` = ?'
            )
            ->execute(
                $meansCode,
                $taxContext['taxCountry'],
                $taxContext['vatExemptionStatus'],
                $shippingTaxCategory ?? '',
                $paymentTaxCategory ?? '',
                $orderIdInDb
            );

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
        $taxContext = EInvoicingTaxResolver::resolveSnapshotTaxContextFromCheckout();

        $extendedInfo = is_array($cartItem['extendedInfo'] ?? null) ? $cartItem['extendedInfo'] : [];
        $taxCategoryCode = $this->resolveTaxCategoryCode($taxConfiguration, $taxRate, $taxContext);
        $productId = $this->resolveProductId($product);
        $productRecord = $this->loadRecordById('tl_ls_shop_product', $productId);
        $unitCode = EInvoicingCodeLists::normalizeCode(
            $productRecord[EInvoicingSnapshotFields::PRODUCT_UNIT_CODE_FIELD] ?? null
        ) ?? EInvoicingCodeLists::DEFAULT_UNIT_CODE;

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

        $extendedInfo[EInvoicingSnapshotFields::ITEM_UNIT_CODE] = $unitCode;
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
     * @param array{
     *   taxCountry: string,
     *   isVatExempt: bool,
     *   isEuCountry: bool,
     *   vatExemptionStatus: string
     * } $taxContext
     */
    private function resolveTaxCategoryCode(array $taxConfiguration, ?float $taxRate, array $taxContext): ?string
    {
        return EInvoicingTaxResolver::resolveTaxCategory(
            $this->optionalString($taxConfiguration[EInvoicingSnapshotFields::TAX_CONFIGURATION_CATEGORY_FIELD] ?? null),
            $taxRate,
            $taxContext
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

    private function resolveProductId(object $product): int
    {
        if (property_exists($product, 'ls_ID') && is_numeric($product->ls_ID)) {
            return (int) $product->ls_ID;
        }

        if (isset($product->_id) && is_numeric($product->_id)) {
            return (int) $product->_id;
        }

        return 0;
    }

    /**
     * @param array<int|string, mixed>|mixed $taxedWithValue
     * @param array{
     *   taxCountry: string,
     *   isVatExempt: bool,
     *   isEuCountry: bool,
     *   vatExemptionStatus: string
     * } $taxContext
     */
    private function resolveChargeTaxCategory(mixed $taxedWithValue, array $taxContext): ?string
    {
        $taxedWith = is_array($taxedWithValue) ? $taxedWithValue : [];
        $taxEntry = $this->firstTaxedAmountEntry($taxedWith);

        if ($taxEntry === null) {
            return null;
        }

        $taxRate = $this->floatValue($taxEntry['taxRate'] ?? null);
        $taxClassId = (int) ($taxEntry['_taxClassId'] ?? 0);
        $taxConfiguration = $this->loadRecordById('tl_ls_shop_steuersaetze', $taxClassId);

        return $this->resolveTaxCategoryCode($taxConfiguration, $taxRate, $taxContext);
    }

    /**
     * @param array<int|string, mixed> $taxedWith
     *
     * @return array<string, mixed>|null
     */
    private function firstTaxedAmountEntry(array $taxedWith): ?array
    {
        foreach ($taxedWith as $taxClassId => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $entry['_taxClassId'] = (string) $taxClassId;

            return $entry;
        }

        return null;
    }
}
