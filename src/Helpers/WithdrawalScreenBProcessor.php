<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Helpers;

use Merconis\Core\ls_shop_generalHelper;

final class WithdrawalScreenBProcessor
{
    private const QUANTITY_EPSILON = 0.00001;

    /**
     * @param array<string, mixed> $arrOrder
     * @return array<string, mixed>
     */
    public function buildParentSnapshot(
        array $arrOrder,
        string $withdrawalId,
        string $name,
        string $email,
        int $withdrawalTimestamp
    ): array {
        $paymentMethod = $this->resolveScalarValueWithFallback(
            $arrOrder,
            ['paymentMethod_title_customerLanguage'],
            'paymentMethod_title'
        );
        $paymentMethodFallback = $this->resolveScalarValueWithFallback(
            $arrOrder,
            [],
            'paymentMethod_title'
        );
        $shippingMethod = $this->resolveScalarValueWithFallback(
            $arrOrder,
            ['shippingMethod_title_customerLanguage'],
            'shippingMethod_title'
        );
        $shippingMethodFallback = $this->resolveScalarValueWithFallback(
            $arrOrder,
            [],
            'shippingMethod_title'
        );
        $customerData = is_array($arrOrder['customerData'] ?? null) ? $arrOrder['customerData'] : [];
        $personalData = is_array($customerData['personalData'] ?? null) ? $customerData['personalData'] : [];
        $billingAddressData = $this->extractBillingAddressData($personalData);
        $shippingAddressData = $this->extractShippingAddressData($personalData);

        return [
            'tstamp' => $withdrawalTimestamp,
            'withdrawalId' => $withdrawalId,
            'withdrawalTimestamp' => $withdrawalTimestamp,
            'name' => $name,
            'email' => $email,
            'orderReference' => (int) ($arrOrder['id'] ?? 0),
            'snapshotOrderNr' => (string) ($arrOrder['orderNr'] ?? ''),
            'snapshotOrderDate' => (string) ($arrOrder['orderDate'] ?? ''),
            'snapshotBillingAddress' => serialize($billingAddressData),
            'snapshotShippingAddress' => serialize($shippingAddressData),
            'snapshotPaymentMethod' => $paymentMethodFallback,
            'snapshotPaymentMethod_customerLanguage' => $paymentMethod,
            'snapshotShippingMethod' => $shippingMethodFallback,
            'snapshotShippingMethod_customerLanguage' => $shippingMethod,
            'freetext' => '',
            'scenario' => '1',
        ];
    }

    /**
     * @param array<string, mixed> $orderItem
     * @return array<string, mixed>
     */
    public function buildChildSnapshot(array $orderItem, float $withdrawnQuantity, int $withdrawalTimestamp): array
    {
        $productName = $this->resolveOrderItemCustomerLanguageValue(
            $orderItem,
            ['_productTitle_customerLanguage'],
            'productTitle'
        );
        $productNameFallback = $this->resolveScalarValueWithFallback($orderItem, [], 'productTitle');
        $variantTitle = $this->resolveOrderItemCustomerLanguageValue(
            $orderItem,
            ['_variantTitle_customerLanguage', '_title_customerLanguage'],
            'variantTitle'
        );
        $variantTitleFallback = $this->resolveScalarValueWithFallback($orderItem, [], 'variantTitle');
        $quantityUnit = $this->resolveOrderItemCustomerLanguageValue(
            $orderItem,
            ['_quantityUnit_customerLanguage'],
            'quantityUnit'
        );
        $quantityUnitFallback = $this->resolveScalarValueWithFallback($orderItem, [], 'quantityUnit');

        return [
            'tstamp' => $withdrawalTimestamp,
            'orderItemReference' => (int) ($orderItem['id'] ?? 0),
            'snapshotProductName' => $productNameFallback,
            'snapshotProductName_customerLanguage' => $productName,
            'snapshotVariantTitle' => $variantTitleFallback,
            'snapshotVariantTitle_customerLanguage' => $variantTitle,
            'snapshotProductNumber' => (string) ($orderItem['artNr'] ?? ''),
            'snapshotUnitPrice' => $this->formatUnitPriceDisplay(
                $orderItem['price'] ?? 0,
                $quantityUnitFallback
            ),
            'snapshotUnitPrice_customerLanguage' => $this->formatUnitPriceDisplay(
                $orderItem['price'] ?? 0,
                $quantityUnit
            ),
            'snapshotQuantityUnit' => $quantityUnitFallback,
            'snapshotQuantityUnit_customerLanguage' => $quantityUnit,
            'snapshotOrderedQuantity' => $this->normalizeQuantity($orderItem['quantity'] ?? 0),
            'snapshotQuantityDecimals' => max(0, (int) ($orderItem['quantityDecimals'] ?? 0)),
            'withdrawnQuantity' => $this->normalizeQuantity($withdrawnQuantity),
        ];
    }

    public function isValidWithdrawnQuantity(
        float $withdrawnQuantity,
        float $orderedQuantity,
        float $minimumQuantity,
        int $quantityDecimals
    ): bool
    {
        if ($orderedQuantity <= 0.0 || $minimumQuantity <= 0.0) {
            return false;
        }

        if ($withdrawnQuantity < $minimumQuantity || $withdrawnQuantity > $orderedQuantity) {
            return false;
        }

        if ($quantityDecimals <= 0) {
            return $this->isEffectivelyInteger($withdrawnQuantity);
        }

        $decimalFactor = pow(10, $quantityDecimals);

        return $this->isEffectivelyInteger($withdrawnQuantity * $decimalFactor);
    }

    /**
     * @param array<string, mixed> $parentSnapshot
     */
    public function hasCompleteParentSnapshot(array $parentSnapshot): bool
    {
        $requiredFields = [
            'withdrawalId',
            'withdrawalTimestamp',
            'name',
            'email',
            'orderReference',
            'snapshotOrderNr',
            'snapshotOrderDate',
            'snapshotBillingAddress',
            'snapshotShippingAddress',
            'snapshotPaymentMethod',
            'snapshotPaymentMethod_customerLanguage',
            'snapshotShippingMethod',
            'snapshotShippingMethod_customerLanguage',
            'scenario',
        ];

        foreach ($requiredFields as $requiredField) {
            if (!array_key_exists($requiredField, $parentSnapshot)) {
                return false;
            }

            $fieldValue = $parentSnapshot[$requiredField];

            if (is_string($fieldValue) && trim($fieldValue) === '') {
                return false;
            }

            if ($requiredField === 'orderReference' && (int) $fieldValue <= 0) {
                return false;
            }

            if ($requiredField === 'withdrawalTimestamp' && (int) $fieldValue <= 0) {
                return false;
            }
        }

        return true;
    }

    private function normalizeQuantity(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        $normalizedValue = str_replace(',', '.', trim($value));
        if ($normalizedValue === '' || !is_numeric($normalizedValue)) {
            return 0.0;
        }

        return (float) $normalizedValue;
    }

    private function isEffectivelyInteger(float $value): bool
    {
        return abs($value - round($value)) <= self::QUANTITY_EPSILON;
    }

    /**
     * @param array<string, mixed> $personalData
     * @return array<string, mixed>
     */
    private function extractBillingAddressData(array $personalData): array
    {
        $billingAddressData = [];

        foreach ($personalData as $fieldName => $fieldValue) {
            if (!is_string($fieldName)) {
                continue;
            }

            if (str_ends_with($fieldName, '_alternative')) {
                continue;
            }

            if (in_array($fieldName, ['useDeviantShippingAddress', 'order-note'], true)) {
                continue;
            }

            $billingAddressData[$fieldName] = $fieldValue;
        }

        return $billingAddressData;
    }

    /**
     * @param array<string, mixed> $personalData
     * @return array<string, mixed>
     */
    private function extractShippingAddressData(array $personalData): array
    {
        if (!$this->hasDeviantShippingAddress($personalData)) {
            return [];
        }

        $shippingAddressData = [];

        foreach ($personalData as $fieldName => $fieldValue) {
            if (!is_string($fieldName) || !str_ends_with($fieldName, '_alternative')) {
                continue;
            }

            $shippingAddressData[$fieldName] = $fieldValue;
        }

        return $shippingAddressData;
    }

    /**
     * @param array<string, mixed> $personalData
     */
    private function hasDeviantShippingAddress(array $personalData): bool
    {
        return !empty($personalData['useDeviantShippingAddress']);
    }

    private function formatUnitPriceDisplay(mixed $priceValue, string $quantityUnit): string
    {
        $formattedPrice = ls_shop_generalHelper::outputPrice($this->normalizeQuantity($priceValue));
        if ($quantityUnit === '') {
            return $formattedPrice;
        }

        return $formattedPrice . '/' . $quantityUnit;
    }

    /**
     * @param array<string, mixed> $orderItem
     * @param array<int, string> $customerLanguageKeys
     */
    private function resolveOrderItemCustomerLanguageValue(
        array $orderItem,
        array $customerLanguageKeys,
        string $fallbackKey
    ): string {
        $extendedInfo = is_array($orderItem['extendedInfo'] ?? null) ? $orderItem['extendedInfo'] : [];

        return $this->resolveScalarValueWithFallback($extendedInfo, $customerLanguageKeys, $fallbackKey, $orderItem);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<int, string> $preferredKeys
     * @param array<string, mixed>|null $fallbackValues
     */
    private function resolveScalarValueWithFallback(
        array $values,
        array $preferredKeys,
        string $fallbackKey,
        ?array $fallbackValues = null
    ): string {
        foreach ($preferredKeys as $preferredKey) {
            if (!array_key_exists($preferredKey, $values) || $values[$preferredKey] === null) {
                continue;
            }

            return (string) $values[$preferredKey];
        }

        $fallbackValues ??= $values;
        if (!array_key_exists($fallbackKey, $fallbackValues) || $fallbackValues[$fallbackKey] === null) {
            return '';
        }

        return (string) $fallbackValues[$fallbackKey];
    }
}
