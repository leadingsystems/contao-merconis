<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Helpers;

use Merconis\Core\ls_shop_generalHelper;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_mul;

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
        $salesUnitSize = $this->getSalesUnitSize($orderItem);
        $productName = $this->resolveOrderItemCustomerLanguageValue(
            $orderItem,
            ['_productTitle_customerLanguage'],
            'productTitle'
        );
        $productNameFallback = $this->resolveScalarValueWithFallback($orderItem, [], 'productTitle');
        $variantTitleKeys = !empty($orderItem['isVariant'])
            ? ['_variantTitle_customerLanguage', '_title_customerLanguage']
            : ['_variantTitle_customerLanguage'];
        $variantTitle = $this->resolveOrderItemCustomerLanguageValue(
            $orderItem,
            $variantTitleKeys,
            'variantTitle'
        );
        $variantTitleFallback = $this->resolveScalarValueWithFallback($orderItem, [], 'variantTitle');
        $quantityUnit = $salesUnitSize > 0
            ? $this->resolveOrderItemCustomerLanguageValue(
                $orderItem,
                ['_salesUnit_customerLanguage'],
                'salesUnit'
            )
            : $this->resolveOrderItemCustomerLanguageValue(
                $orderItem,
                ['_quantityUnit_customerLanguage'],
                'quantityUnit'
            );
        $quantityUnitFallback = $salesUnitSize > 0
            ? $this->resolveScalarValueWithFallback($orderItem, [], 'salesUnit')
            : $this->resolveScalarValueWithFallback($orderItem, [], 'quantityUnit');
        $unitPriceQuantityUnit = $salesUnitSize > 0
            ? $this->resolveOrderItemCustomerLanguageValue(
                $orderItem,
                ['_displayQuantityUnit_customerLanguage'],
                'displayQuantityUnit'
            )
            : $quantityUnit;
        $unitPriceQuantityUnitFallback = $salesUnitSize > 0
            ? $this->resolveScalarValueWithFallback($orderItem, [], 'displayQuantityUnit')
            : $quantityUnitFallback;

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
                $unitPriceQuantityUnitFallback
            ),
            'snapshotUnitPrice_customerLanguage' => $this->formatUnitPriceDisplay(
                $orderItem['price'] ?? 0,
                $unitPriceQuantityUnit
            ),
            'snapshotQuantityUnit' => $quantityUnitFallback,
            'snapshotQuantityUnit_customerLanguage' => $quantityUnit,
            'snapshotOrderedQuantity' => $this->normalizeQuantity($orderItem['quantity'] ?? 0),
            'snapshotQuantityDecimals' => max(0, (int) ($orderItem['quantityDecimals'] ?? 0)),
            'snapshotSalesUnitSize' => $salesUnitSize,
            'snapshotConfiguratorReferenceNumber' => $this->resolveConfiguratorReferenceNumber($orderItem),
            'snapshotCustomizerReferenceNumber' => $this->resolveCustomizerReferenceNumber($orderItem),
            'withdrawnQuantity' => $this->normalizeQuantity($withdrawnQuantity),
        ];
    }

    public function isValidWithdrawnQuantity(
        float $withdrawnQuantity,
        float $orderedQuantity,
        float $minimumQuantity,
        int $quantityDecimals,
        int $salesUnitSize
    ): bool
    {
        if ($orderedQuantity <= 0.0 || $minimumQuantity <= 0.0) {
            return false;
        }

        if ($withdrawnQuantity < $minimumQuantity || $withdrawnQuantity > $orderedQuantity) {
            return false;
        }

        return $this->isValidQuantityMultiple(
            $withdrawnQuantity,
            $quantityDecimals,
            $salesUnitSize
        );
    }

    /**
     * Die Widerrufsmaske arbeitet im VE-Modus mit Stückmengen aus dem Order-Snapshot.
     *
     * @param array<string, mixed> $orderItem
     */
    public function getOrderedDisplayQuantity(array $orderItem): float
    {
        $salesUnitSize = $this->getSalesUnitSize($orderItem);
        if ($salesUnitSize > 0) {
            return $this->normalizeQuantity($orderItem['displayQuantity'] ?? 0);
        }

        return $this->normalizeQuantity($orderItem['quantity'] ?? 0);
    }

    public function getDisplayMinimumQuantity(int $salesUnitSize, int $quantityDecimals): float
    {
        return $this->normalizeQuantity($this->getDisplayStepValue($salesUnitSize, $quantityDecimals));
    }

    public function getDisplayStepValue(int $salesUnitSize, int $quantityDecimals): string
    {
        $scaleFactor = max(1, (int) pow(10, max(0, $quantityDecimals)));

        if ($salesUnitSize > 0) {
            return $this->normalizeNumericString(ls_div($salesUnitSize, $scaleFactor));
        }

        return $this->normalizeNumericString(ls_div(1, $scaleFactor));
    }

    public function convertDisplayQuantityToInternalQuantity(float $displayQuantity, int $salesUnitSize): float
    {
        if ($salesUnitSize <= 0) {
            return $this->normalizeQuantity($displayQuantity);
        }

        return $this->normalizeQuantity(ls_div($displayQuantity, $salesUnitSize));
    }

    public function formatDisplayQuantity(float $internalQuantity, int $quantityDecimals, int $salesUnitSize): string
    {
        return ls_shop_generalHelper::outputDisplayQuantity(
            $internalQuantity,
            $quantityDecimals,
            $salesUnitSize,
            '.',
            ''
        );
    }

    /**
     * @param array<string, mixed> $orderItem
     */
    public function getSalesUnitSize(array $orderItem): int
    {
        return max(0, (int) ($orderItem['salesUnitSize'] ?? 0));
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

    /**
     * Ermittelt die Konfigurator-Referenznummer aus dem Order-Item.
     * Nur wenn `configurator_hasValue` gesetzt ist, wird die Referenznummer übernommen.
     *
     * @param array<string, mixed> $orderItem
     */
    public function resolveConfiguratorReferenceNumber(array $orderItem): string
    {
        if (empty($orderItem['configurator_hasValue'])) {
            return '';
        }

        return (string) ($orderItem['configurator_referenceNumber'] ?? '');
    }

    /**
     * Ermittelt die Customizer-Referenznummer aus dem Order-Item.
     * Fallback für Altbestellungen: Wenn `customizer_hasCustomization` gesetzt,
     * aber `customizer_referenceNumber` leer ist, wird der Hash selbst berechnet.
     *
     * @param array<string, mixed> $orderItem
     */
    public function resolveCustomizerReferenceNumber(array $orderItem): string
    {
        if (empty($orderItem['customizer_hasCustomization'])) {
            return '';
        }

        $referenceNumber = (string) ($orderItem['customizer_referenceNumber'] ?? '');

        if ($referenceNumber !== '') {
            return $referenceNumber;
        }

        $summary = (string) ($orderItem['customizer_summary'] ?? '');
        $flexData = (string) ($orderItem['customizer_flexData'] ?? '');

        return strtoupper(substr(md5($summary . $flexData), 0, 8));
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

    private function normalizeNumericString(mixed $value): string
    {
        $formattedValue = number_format($this->normalizeQuantity($value), 4, '.', '');

        return rtrim(rtrim($formattedValue, '0'), '.') ?: '0';
    }

    private function isEffectivelyInteger(float $value): bool
    {
        return abs($value - round($value)) <= self::QUANTITY_EPSILON;
    }

    private function isValidQuantityMultiple(
        float $withdrawnQuantity,
        int $quantityDecimals,
        int $salesUnitSize
    ): bool {
        $scaleFactor = max(1, (int) pow(10, max(0, $quantityDecimals)));
        $scaledQuantity = ls_mul($withdrawnQuantity, $scaleFactor);
        if (!$this->isEffectivelyInteger($this->normalizeQuantity($scaledQuantity))) {
            return false;
        }

        $scaledQuantityInteger = (int) round($this->normalizeQuantity($scaledQuantity));
        if ($salesUnitSize <= 0) {
            return true;
        }

        return $scaledQuantityInteger % $salesUnitSize === 0;
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
