<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Contao;

use Contao\Database;
use Contao\StringUtil;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingCodeLists;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingTaxResolver;
use Merconis\Core\ls_shop_generalHelper;

final class EInvoicingCheckoutSnapshotHook
{
    private const SEPA_DIRECT_DEBIT_CODE = '59';

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

        $persistedOrder = ls_shop_generalHelper::getOrder($orderIdInDb, 'id', true);
        $snapshotOrder = $order;

        if (is_array($persistedOrder) && $persistedOrder !== []) {
            $snapshotOrder = $persistedOrder;
        }

        $taxContext = EInvoicingTaxResolver::resolveSnapshotTaxContextFromOrder(
            $snapshotOrder,
            [
                'ls_shop_country' => $GLOBALS['TL_CONFIG']['ls_shop_country'] ?? null,
                'ls_shop_ownVATID' => $GLOBALS['TL_CONFIG']['ls_shop_ownVATID'] ?? null,
                'ls_shop_euCountrycodes' => $GLOBALS['TL_CONFIG']['ls_shop_euCountrycodes'] ?? null,
            ]
        );
        $paymentMethodId = (int) ($order['paymentMethod']['id'] ?? $snapshotOrder['paymentMethod_id'] ?? 0);
        $paymentMethod = $this->loadRecordById('tl_ls_shop_payment_methods', $paymentMethodId);
        $meansCode = EInvoicingCodeLists::normalizeCode(
            $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] ?? null
        ) ?? '';
        $paymentTaxCategory = $this->resolveChargeTaxCategory(
            $snapshotOrder['paymentMethod_amountTaxedWith'] ?? null,
            $taxContext
        );
        $shippingTaxCategory = $this->resolveChargeTaxCategory(
            $snapshotOrder['shippingMethod_amountTaxedWith'] ?? null,
            $taxContext
        );
        $memberId = (int) ($snapshotOrder['customerNr'] ?? $order['customerNr'] ?? 0);
        $memberRecord = $memberId > 0 ? $this->loadRecordById('tl_member', $memberId) : [];

        $updateColumns = [
            EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_COLUMN => $meansCode,
            EInvoicingSnapshotFields::ORDER_TAX_COUNTRY_COLUMN => $taxContext['taxCountry'],
            EInvoicingSnapshotFields::ORDER_VAT_EXEMPTION_STATUS_COLUMN => $taxContext['vatExemptionStatus'],
            EInvoicingSnapshotFields::ORDER_SHIPPING_TAX_CATEGORY_COLUMN => $shippingTaxCategory ?? '',
            EInvoicingSnapshotFields::ORDER_PAYMENT_TAX_CATEGORY_COLUMN => $paymentTaxCategory ?? '',
        ];

        if ($meansCode === self::SEPA_DIRECT_DEBIT_CODE) {
            $updateColumns[EInvoicingSnapshotFields::ORDER_MANDATE_REFERENCE_COLUMN] = $this->optionalString(
                $memberRecord[EInvoicingSnapshotFields::MEMBER_MANDATE_REFERENCE_FIELD] ?? null
            ) ?? '';
            $updateColumns[EInvoicingSnapshotFields::ORDER_CREDITOR_ID_COLUMN] = $this->optionalString(
                $GLOBALS['TL_CONFIG'][EInvoicingSnapshotFields::SHOP_SETTINGS_CREDITOR_ID_FIELD] ?? null
            ) ?? '';
            $updateColumns[EInvoicingSnapshotFields::ORDER_DEBTOR_IBAN_COLUMN] = $this->optionalString(
                $memberRecord[EInvoicingSnapshotFields::MEMBER_DEBTOR_IBAN_FIELD] ?? null
            ) ?? '';
        }
        $setFragments = [];
        $params = [];

        foreach ($updateColumns as $column => $value) {
            $setFragments[] = sprintf('`%s` = ?', $column);
            $params[] = $value;
        }

        $params[] = $orderIdInDb;

        Database::getInstance()
            ->prepare(
                'UPDATE `tl_ls_shop_orders`'
                . ' SET ' . implode(', ', $setFragments)
                . ' WHERE `id` = ?'
            )
            ->execute(...$params);

        $this->clearCachedOrder($orderIdInDb, $snapshotOrder['orderIdentificationHash'] ?? $order['orderIdentificationHash'] ?? null);
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
        $taxedWith = $this->deserializeTaxedWith($taxedWithValue);
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

    /**
     * Der `afterCheckout`-Hook liefert die Steueraufschlüsselung noch
     * als serialisierten DB-Wert. Für echte Checkout-Läufe wird sie
     * hier auf dieselbe Array-Form normalisiert wie in den Fixtures.
     *
     * @return array<int|string, mixed>
     */
    private function deserializeTaxedWith(mixed $taxedWithValue): array
    {
        if (is_array($taxedWithValue)) {
            return $taxedWithValue;
        }

        if (!is_string($taxedWithValue) || trim($taxedWithValue) === '') {
            return [];
        }

        $deserializedValue = StringUtil::deserialize($taxedWithValue);

        return is_array($deserializedValue) ? $deserializedValue : [];
    }
}
