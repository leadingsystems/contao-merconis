<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

use DateInterval;
use DateTimeImmutable;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use LeadingSystems\MerconisBundle\EInvoicing\Model\SemanticInvoice;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;

final class SemanticInvoiceBuilder
{
    private const DEFAULT_GUIDELINE_ID = 'urn:cen.eu:en16931:2017';
    private const DEFAULT_INVOICE_TYPE_CODE = '380';
    private const DEFAULT_BILLED_QUANTITY_UNIT_CODE = 'C62';
    private const TAX_TYPE_CODE = 'VAT';
    private const DEFAULT_PAYMENT_MEANS_CODE = '58';

    /**
     * @param array<string, mixed>                   $orderData
     * @param array<string, mixed>                   $shopSettings
     * @param array<string, mixed>                   $paymentMethod
     * @param array<string, mixed>                   $shippingMethod
     * @param array<int|string, array<string, mixed>> $taxRateDataById
     * @param array<string, mixed>|null              $memberGroup
     *
     * @throws InvoiceValidationException
     */
    public function build(
        array $orderData,
        array $shopSettings,
        array $paymentMethod,
        array $shippingMethod,
        array $taxRateDataById,
        ?array $memberGroup,
        string $invoiceNumber,
        DateTimeImmutable $invoiceDate,
    ): SemanticInvoice {
        $violations = [];

        $invoiceNumber = $this->requireString($invoiceNumber, 'Die Rechnungsnummer (BT-1) fehlt.', $violations);
        $currencyCode = strtoupper($this->requireValue($shopSettings, 'ls_shop_currencyCode', 'Der Währungscode (BT-5) fehlt.', $violations));

        $seller = $this->buildSeller($shopSettings, $violations);
        $buyer = $this->buildBuyer($orderData, $violations);
        $payment = $this->buildPayment($orderData, $shopSettings, $paymentMethod, $memberGroup, $invoiceDate, $violations);

        $isGrossMode = $this->isGrossMode($shopSettings, $orderData);
        $lineItems = $this->buildLineItems($orderData, $isGrossMode, $violations);
        $documentCharges = $this->buildDocumentCharges($orderData, $paymentMethod, $shippingMethod, $taxRateDataById, $violations);
        $taxSummaries = $this->buildTaxSummaries($lineItems, $documentCharges);
        $totals = $this->buildTotals($orderData, $lineItems, $documentCharges, $taxSummaries);

        if ($lineItems === []) {
            $violations[] = 'Es muss mindestens eine Rechnungsposition vorhanden sein.';
        }

        if ($taxSummaries === []) {
            $violations[] = 'Es muss mindestens eine Steueraufschlüsselung (BG-23) vorhanden sein.';
        }

        if ($violations !== []) {
            throw new InvoiceValidationException($violations);
        }

        return new SemanticInvoice(
            self::DEFAULT_GUIDELINE_ID,
            self::DEFAULT_INVOICE_TYPE_CODE,
            $invoiceNumber,
            (string) ($orderData['orderNr'] ?? ''),
            $invoiceDate,
            $currencyCode,
            $seller,
            $buyer,
            $payment,
            $lineItems,
            $documentCharges,
            $taxSummaries,
            $totals,
            []
        );
    }

    /**
     * @param array<string, mixed> $shopSettings
     * @param list<string>         $violations
     *
     * @return array<string, mixed>
     */
    private function buildSeller(array $shopSettings, array &$violations): array
    {
        $vatId = $this->optionalString($shopSettings['ls_shop_ownVATID'] ?? null);
        $taxNumber = $this->optionalString($shopSettings['ls_shop_einvoicingSellerTaxNumber'] ?? null);

        if ($vatId === null && $taxNumber === null) {
            $violations[] = 'Es fehlt mindestens eine Verkäufer-Steuerkennung (BT-31 oder BT-32).';
        }

        return [
            'name' => $this->requireValue($shopSettings, 'ls_shop_einvoicingSellerName', 'Der Verkäufername (BT-27) fehlt.', $violations),
            'street' => $this->requireValue($shopSettings, 'ls_shop_einvoicingSellerStreet', 'Die Verkäuferstraße (BT-35) fehlt.', $violations),
            'postalCode' => $this->requireValue($shopSettings, 'ls_shop_einvoicingSellerPostalCode', 'Die Verkäufer-PLZ (BT-38) fehlt.', $violations),
            'city' => $this->requireValue($shopSettings, 'ls_shop_einvoicingSellerCity', 'Der Verkäufer-Ort (BT-37) fehlt.', $violations),
            'countryCode' => strtoupper($this->requireValue($shopSettings, 'ls_shop_country', 'Das Verkäuferland (BT-40) fehlt.', $violations)),
            'vatId' => $vatId,
            'taxNumber' => $taxNumber,
            'electronicAddress' => $this->requireValue(
                $shopSettings,
                'ls_shop_einvoicingSellerElectronicAddress',
                'Die Verkäufer-E-Adresse (BT-34) fehlt.',
                $violations
            ),
            'electronicAddressScheme' => strtoupper($this->requireValue(
                $shopSettings,
                'ls_shop_einvoicingSellerElectronicAddressScheme',
                'Das Schema der Verkäufer-E-Adresse (BT-34-1) fehlt.',
                $violations
            )),
            'iban' => $this->optionalString($shopSettings['ls_shop_einvoicingSellerIban'] ?? null),
            'bic' => $this->optionalString($shopSettings['ls_shop_einvoicingSellerBic'] ?? null),
            'email' => $this->optionalString($shopSettings['ls_shop_ownEmailAddress'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $orderData
     * @param list<string>         $violations
     *
     * @return array<string, mixed>
     */
    private function buildBuyer(array $orderData, array &$violations): array
    {
        $personalData = $this->arrayValue($orderData, 'customerData');
        $personalData = is_array($personalData['personalData'] ?? null) ? $personalData['personalData'] : [];
        $originalValues = is_array(($orderData['customerData']['personalData_originalOptionValues'] ?? null))
            ? $orderData['customerData']['personalData_originalOptionValues']
            : [];

        $firstName = $this->optionalString($personalData['firstname'] ?? null);
        $lastName = $this->optionalString($personalData['lastname'] ?? null);
        $company = $this->optionalString($personalData['company'] ?? null);
        $countryCode = $this->optionalString($originalValues['country'] ?? null);

        $buyerName = $company;

        if ($buyerName === null) {
            $buyerName = trim(implode(' ', array_filter([$firstName, $lastName], static fn (?string $value): bool => $value !== null)));
        }

        if ($buyerName === '') {
            $buyerName = null;
        }

        if ($buyerName === null) {
            $violations[] = 'Der Käufername (BT-44) fehlt.';
        }

        if ($countryCode === null) {
            $violations[] = 'Der Käufer-Ländercode (BT-55) fehlt.';
        }

        return [
            'name' => $buyerName ?? '',
            'street' => $this->requireString($personalData['street'] ?? null, 'Die Käuferstraße (BT-50) fehlt.', $violations),
            'postalCode' => $this->requireString($personalData['postal'] ?? null, 'Die Käufer-PLZ (BT-53) fehlt.', $violations),
            'city' => $this->requireString($personalData['city'] ?? null, 'Der Käufer-Ort (BT-52) fehlt.', $violations),
            'countryCode' => strtoupper((string) $countryCode),
            'vatId' => $this->optionalString($personalData['VATID'] ?? null),
            'taxNumber' => null,
            'electronicAddress' => null,
            'electronicAddressScheme' => null,
            'iban' => null,
            'bic' => null,
            'email' => $this->optionalString($personalData['email'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed>      $shopSettings
     * @param array<string, mixed>      $paymentMethod
     * @param array<string, mixed>|null $memberGroup
     * @param list<string>              $violations
     *
     * @return array<string, mixed>
     */
    private function buildPayment(
        array $orderData,
        array $shopSettings,
        array $paymentMethod,
        ?array $memberGroup,
        DateTimeImmutable $invoiceDate,
        array &$violations,
    ): array {
        $meansCode = $this->optionalString($orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE] ?? null);

        if ($meansCode === null) {
            $violations[] = 'Der Checkout-Snapshot für Payment Means Code (BT-81) fehlt.';
            $meansCode = $this->optionalString($paymentMethod['ls_shop_einvoicingPaymentMeansCode'] ?? null)
                ?? self::DEFAULT_PAYMENT_MEANS_CODE;
        }

        $termsDays = $this->optionalString($memberGroup['lsShopEinvoicingPaymentTermsDays'] ?? null)
            ?? $this->optionalString($shopSettings['ls_shop_einvoicingPaymentTermsDays'] ?? null);
        $termsNote = $this->optionalString($memberGroup['lsShopEinvoicingPaymentTermsNote'] ?? null)
            ?? $this->optionalString($shopSettings['ls_shop_einvoicingPaymentTermsNote'] ?? null);

        $dueDate = null;

        if ($termsDays !== null && $termsDays !== '') {
            $dueDate = $invoiceDate->add(new DateInterval('P' . (int) $termsDays . 'D'));
        }

        $iban = $this->optionalString($shopSettings['ls_shop_einvoicingSellerIban'] ?? null);
        $bic = $this->optionalString($shopSettings['ls_shop_einvoicingSellerBic'] ?? null);

        if (in_array($meansCode, ['30', '58'], true) && $iban === null) {
            $violations[] = 'Für Payment Means Code ' . $meansCode . ' ist eine IBAN (BT-84) erforderlich.';
        }

        return [
            'meansCode' => $meansCode,
            'dueDate' => $dueDate,
            'termsNote' => $termsNote,
            'iban' => $iban,
            'bic' => $bic,
        ];
    }

    /**
     * @param array<string, mixed>                    $orderData
     * @param list<string>                            $violations
     *
     * @return list<array<string, mixed>>
     */
    private function buildLineItems(
        array $orderData,
        bool $isGrossMode,
        array &$violations,
    ): array {
        $lineItems = [];
        $items = is_array($orderData['items'] ?? null) ? $orderData['items'] : [];

        foreach ($items as $lineId => $item) {
            if (!is_array($item)) {
                continue;
            }

            $taxRate = $this->floatValue($item['taxPercentage'] ?? null);

            if ($taxRate === null) {
                $violations[] = 'Für Position ' . $lineId . ' fehlt der Steuersatz (BT-152).';
                continue;
            }

            $taxCategoryCode = $this->resolveLineItemTaxCategoryCode($item, (string) $lineId, $violations);

            $quantity = $this->floatValue($item['quantity'] ?? null);

            if ($quantity === null || $quantity <= 0) {
                $violations[] = 'Für Position ' . $lineId . ' fehlt eine gültige Menge (BT-129).';
                continue;
            }

            $itemName = $this->resolveItemName($item);

            if ($itemName === null) {
                $violations[] = 'Für Position ' . $lineId . ' fehlt der Produktname.';
                continue;
            }

            $unitPrice = $this->floatValue($item['price'] ?? null);

            if ($unitPrice === null) {
                $violations[] = 'Für Position ' . $lineId . ' fehlt der Positionspreis.';
                continue;
            }

            $lineNetAmount = $this->resolveLineNetAmount($item, $unitPrice, $quantity, $taxRate, $isGrossMode);
            $unitNetAmount = $isGrossMode ? $this->money($unitPrice / (1 + $taxRate / 100)) : $this->money4($unitPrice);
            $unitGrossAmount = $isGrossMode ? $this->money4($unitPrice) : $this->money4($unitNetAmount * (1 + $taxRate / 100));

            $lineItems[] = [
                'lineId' => (string) $lineId,
                'name' => $itemName,
                'sellerAssignedId' => $this->optionalString($item['artNr'] ?? null),
                'quantity' => $quantity,
                'unitCode' => $this->resolveUnitCode($item),
                'unitNetAmount' => $unitNetAmount,
                'unitGrossAmount' => $unitGrossAmount,
                'lineNetAmount' => $lineNetAmount,
                'taxCategoryCode' => $taxCategoryCode,
                'taxRate' => $taxRate,
                'note' => null,
            ];
        }

        return $lineItems;
    }

    /**
     * @param array<string, mixed>                    $orderData
     * @param array<string, mixed>                    $paymentMethod
     * @param array<string, mixed>                    $shippingMethod
     * @param array<int|string, array<string, mixed>> $taxRateDataById
     * @param list<string>                            $violations
     *
     * @return list<array<string, mixed>>
     */
    private function buildDocumentCharges(
        array $orderData,
        array $paymentMethod,
        array $shippingMethod,
        array $taxRateDataById,
        array &$violations,
    ): array {
        $charges = [];

        $paymentCharge = $this->buildDocumentCharge(
            'Zahlungsgebühr',
            $orderData['paymentMethod_amount'] ?? null,
            $orderData['paymentMethod_amountTaxedWith'] ?? null,
            $paymentMethod,
            $taxRateDataById,
            $violations
        );

        if ($paymentCharge !== null) {
            $charges[] = $paymentCharge;
        }

        $shippingCharge = $this->buildDocumentCharge(
            'Versandkosten',
            $orderData['shippingMethod_amount'] ?? null,
            $orderData['shippingMethod_amountTaxedWith'] ?? null,
            $shippingMethod,
            $taxRateDataById,
            $violations
        );

        if ($shippingCharge !== null) {
            $charges[] = $shippingCharge;
        }

        return $charges;
    }

    /**
     * @param list<array<string, mixed>> $lineItems
     * @param list<array<string, mixed>> $documentCharges
     *
     * @return list<array<string, mixed>>
     */
    private function buildTaxSummaries(array $lineItems, array $documentCharges): array
    {
        $groups = [];

        foreach ($lineItems as $lineItem) {
            $groupKey = $this->taxGroupKey((string) $lineItem['taxCategoryCode'], (float) $lineItem['taxRate']);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = $this->createEmptyTaxSummary(
                    (string) $lineItem['taxCategoryCode'],
                    (float) $lineItem['taxRate']
                );
            }

            $groups[$groupKey]['lineTotalBasisAmount'] = $this->money(
                $groups[$groupKey]['lineTotalBasisAmount'] + (float) $lineItem['lineNetAmount']
            );
        }

        foreach ($documentCharges as $documentCharge) {
            $groupKey = $this->taxGroupKey((string) $documentCharge['taxCategoryCode'], (float) $documentCharge['taxRate']);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = $this->createEmptyTaxSummary(
                    (string) $documentCharge['taxCategoryCode'],
                    (float) $documentCharge['taxRate']
                );
            }

            $amount = (float) $documentCharge['amount'];

            if ((bool) $documentCharge['isCharge']) {
                $groups[$groupKey]['allowanceChargeBasisAmount'] = $this->money(
                    $groups[$groupKey]['allowanceChargeBasisAmount'] + $amount
                );
            } else {
                $groups[$groupKey]['allowanceChargeBasisAmount'] = $this->money(
                    $groups[$groupKey]['allowanceChargeBasisAmount'] - $amount
                );
            }
        }

        foreach ($groups as &$group) {
            $group['taxableAmount'] = $this->money($group['lineTotalBasisAmount'] + $group['allowanceChargeBasisAmount']);
            $group['taxAmount'] = $this->money($group['taxableAmount'] * $group['taxRate'] / 100);
        }
        unset($group);

        return array_values($groups);
    }

    /**
     * @param array<string, mixed>    $orderData
     * @param list<array<string, mixed>> $lineItems
     * @param list<array<string, mixed>> $documentCharges
     * @param list<array<string, mixed>> $taxSummaries
     *
     * @return array<string, float>
     */
    private function buildTotals(array $orderData, array $lineItems, array $documentCharges, array $taxSummaries): array
    {
        $lineTotalAmount = $this->money(array_sum(array_map(
            static fn (array $lineItem): float => (float) $lineItem['lineNetAmount'],
            $lineItems
        )));
        $chargeTotalAmount = $this->money(array_sum(array_map(
            static fn (array $charge): float => (bool) $charge['isCharge'] ? (float) $charge['amount'] : 0.0,
            $documentCharges
        )));
        $allowanceTotalAmount = $this->money(array_sum(array_map(
            static fn (array $charge): float => (bool) $charge['isCharge'] ? 0.0 : (float) $charge['amount'],
            $documentCharges
        )));
        $taxBasisTotalAmount = $this->money(array_sum(array_map(
            static fn (array $taxSummary): float => (float) $taxSummary['taxableAmount'],
            $taxSummaries
        )));
        $taxTotalAmount = $this->money(array_sum(array_map(
            static fn (array $taxSummary): float => (float) $taxSummary['taxAmount'],
            $taxSummaries
        )));

        $grossAmount = $this->floatValue($orderData['invoicedAmount'] ?? null);
        $expectedGrandTotalAmount = $this->money($taxBasisTotalAmount + $taxTotalAmount);
        $roundingAmount = $grossAmount === null ? 0.0 : $this->money($grossAmount - $expectedGrandTotalAmount);
        $grandTotalAmount = $this->money($expectedGrandTotalAmount + $roundingAmount);

        return [
            'lineTotalAmount' => $lineTotalAmount,
            'chargeTotalAmount' => $chargeTotalAmount,
            'allowanceTotalAmount' => $allowanceTotalAmount,
            'taxBasisTotalAmount' => $taxBasisTotalAmount,
            'taxTotalAmount' => $taxTotalAmount,
            'grandTotalAmount' => $grandTotalAmount,
            'duePayableAmount' => $grandTotalAmount,
            'roundingAmount' => $roundingAmount,
            'totalPrepaidAmount' => 0.0,
        ];
    }

    /**
     * @param array<string, mixed>                    $methodData
     * @param array<int|string, array<string, mixed>> $taxRateDataById
     * @param list<string>                            $violations
     *
     * @return array<string, mixed>|null
     */
    private function buildDocumentCharge(
        string $reason,
        mixed $amountValue,
        mixed $taxedWithValue,
        array $methodData,
        array $taxRateDataById,
        array &$violations,
    ): ?array {
        $feeType = $this->optionalString($methodData['feeType'] ?? null);
        $amount = $this->floatValue($amountValue);

        if ($feeType === 'none' || $amount === null || $amount <= 0.0) {
            return null;
        }

        $taxedWith = is_array($taxedWithValue) ? $taxedWithValue : [];
        $taxEntry = $this->firstTaxedAmountEntry($taxedWith);

        if ($taxEntry === null) {
            $violations[] = 'Für den Dokumentzuschlag "' . $reason . '" fehlt die Steuerzuordnung.';
            return null;
        }

        $taxRate = $this->floatValue($taxEntry['taxRate'] ?? null);

        if ($taxRate === null) {
            $violations[] = 'Für den Dokumentzuschlag "' . $reason . '" fehlt der Steuersatz.';
            return null;
        }

        $taxClassId = $taxEntry['_taxClassId'] ?? null;
        $taxConfiguration = is_scalar($taxClassId) && isset($taxRateDataById[(string) $taxClassId]) && is_array($taxRateDataById[(string) $taxClassId])
            ? $taxRateDataById[(string) $taxClassId]
            : [];
        $taxCategoryCode = $this->resolveTaxCategoryCode($taxConfiguration, $taxRate, $reason, $violations);

        return [
            'reason' => $reason,
            'amount' => $this->money($amount),
            'taxCategoryCode' => $taxCategoryCode,
            'taxRate' => $taxRate,
            'isCharge' => true,
        ];
    }

    /**
     * @param array<string, mixed> $taxConfiguration
     * @param list<string>         $violations
     */
    private function resolveTaxCategoryCode(array $taxConfiguration, float $taxRate, string $context, array &$violations): string
    {
        $configuredCategory = strtoupper($this->optionalString($taxConfiguration['ls_shop_einvoicingTaxCategory'] ?? null) ?? 'AUTO');

        if ($configuredCategory === 'AUTO') {
            if ($taxRate > 0.0) {
                return 'S';
            }

            $violations[] = 'Die Steuerkategorie kann für "' . $context . '" bei 0 % nicht automatisch abgeleitet werden.';
            return 'S';
        }

        return $configuredCategory;
    }

    /**
     * @param array<string, mixed> $item
     * @param list<string>         $violations
     */
    private function resolveLineItemTaxCategoryCode(array $item, string $lineId, array &$violations): string
    {
        $extendedInfo = is_array($item['extendedInfo'] ?? null) ? $item['extendedInfo'] : [];
        $taxCategoryCode = $this->optionalString($extendedInfo[EInvoicingSnapshotFields::ITEM_TAX_CATEGORY_CODE] ?? null);

        if ($taxCategoryCode === null) {
            $violations[] = 'Für Position ' . $lineId . ' fehlt der Checkout-Snapshot der Steuerkategorie (BT-118).';

            return 'S';
        }

        return strtoupper($taxCategoryCode);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveItemName(array $item): ?string
    {
        $extendedInfo = is_array($item['extendedInfo'] ?? null) ? $item['extendedInfo'] : [];

        return $this->optionalString($extendedInfo['_productTitle_customerLanguage'] ?? null)
            ?? $this->optionalString($extendedInfo['_productTitle'] ?? null)
            ?? $this->optionalString($item['productTitle'] ?? null)
            ?? $this->optionalString($item['artNr'] ?? null);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveUnitCode(array $item): string
    {
        $extendedInfo = is_array($item['extendedInfo'] ?? null) ? $item['extendedInfo'] : [];
        $quantityUnit = $this->optionalString($item['quantityUnit'] ?? null)
            ?? $this->optionalString($extendedInfo['_quantityUnit'] ?? null);

        return $quantityUnit !== null ? strtoupper($quantityUnit) : self::DEFAULT_BILLED_QUANTITY_UNIT_CODE;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveLineNetAmount(array $item, float $unitPrice, float $quantity, float $taxRate, bool $isGrossMode): float
    {
        if ($isGrossMode) {
            $unitNetAmount = $this->money($unitPrice / (1 + $taxRate / 100));

            return $this->money($unitNetAmount * $quantity);
        }

        $priceCumulative = $this->floatValue($item['priceCumulative'] ?? null);

        if ($priceCumulative !== null) {
            return $this->money($priceCumulative);
        }

        return $this->money($unitPrice * $quantity);
    }

    /**
     * @param array<string, mixed> $shopSettings
     * @param array<string, mixed> $orderData
     */
    private function isGrossMode(array $shopSettings, array $orderData): bool
    {
        $priceType = strtolower($this->optionalString($shopSettings['ls_shop_priceType'] ?? null) ?? '');
        $inputPriceType = strtolower($this->optionalString($orderData['inputPriceType'] ?? null) ?? '');
        $userOutputPriceType = strtolower($this->optionalString($orderData['userOutputPriceType'] ?? null) ?? '');

        return in_array($priceType, ['brutto', 'gross'], true)
            || $inputPriceType === 'gross'
            || $userOutputPriceType === 'gross';
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string>         $violations
     */
    private function requireValue(array $values, string $key, string $message, array &$violations): string
    {
        return $this->requireString($values[$key] ?? null, $message, $violations);
    }

    /**
     * @param list<string> $violations
     */
    private function requireString(mixed $value, string $message, array &$violations): string
    {
        $value = $this->optionalString($value);

        if ($value === null) {
            $violations[] = $message;
            return '';
        }

        return $value;
    }

    private function optionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function floatValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function arrayValue(array $values, string $key): array
    {
        return is_array($values[$key] ?? null) ? $values[$key] : [];
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }

    private function money4(float $amount): float
    {
        return round($amount, 4);
    }

    /**
     * @param string $taxCategoryCode
     *
     * @return array<string, mixed>
     */
    private function createEmptyTaxSummary(string $taxCategoryCode, float $taxRate): array
    {
        return [
            'taxCategoryCode' => $taxCategoryCode,
            'taxTypeCode' => self::TAX_TYPE_CODE,
            'taxRate' => $taxRate,
            'taxableAmount' => 0.0,
            'taxAmount' => 0.0,
            'lineTotalBasisAmount' => 0.0,
            'allowanceChargeBasisAmount' => 0.0,
            'exemptionReason' => null,
            'exemptionReasonCode' => null,
        ];
    }

    private function taxGroupKey(string $taxCategoryCode, float $taxRate): string
    {
        return $taxCategoryCode . '|' . number_format($taxRate, 4, '.', '');
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
