<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Helpers;

final class WithdrawalScreenBProcessor
{
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
        $paymentMethod = (string) ($arrOrder['paymentMethod_title_customerLanguage']
            ?? $arrOrder['paymentMethod_title']
            ?? '');
        $shippingMethod = (string) ($arrOrder['shippingMethod_title_customerLanguage']
            ?? $arrOrder['shippingMethod_title']
            ?? '');

        return [
            'tstamp' => $withdrawalTimestamp,
            'withdrawalId' => $withdrawalId,
            'withdrawalTimestamp' => $withdrawalTimestamp,
            'name' => $name,
            'email' => $email,
            'orderReference' => (int) ($arrOrder['id'] ?? 0),
            'snapshotOrderNr' => (string) ($arrOrder['orderNr'] ?? ''),
            'snapshotOrderDate' => (string) ($arrOrder['orderDate'] ?? ''),
            'snapshotBillingAddress' => serialize($arrOrder['customerData']['personalData'] ?? []),
            'snapshotShippingAddress' => serialize($arrOrder['customerData']['shippingData'] ?? []),
            'snapshotPaymentMethod' => $paymentMethod,
            'snapshotShippingMethod' => $shippingMethod,
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
        return [
            'tstamp' => $withdrawalTimestamp,
            'orderItemReference' => (int) ($orderItem['id'] ?? 0),
            'snapshotProductName' => (string) ($orderItem['productTitle'] ?? ''),
            'snapshotVariantTitle' => (string) ($orderItem['variantTitle'] ?? ''),
            'snapshotProductNumber' => (string) ($orderItem['artNr'] ?? ''),
            'snapshotUnitPrice' => (string) ($orderItem['price'] ?? ''),
            'snapshotQuantityUnit' => (string) ($orderItem['quantityUnit'] ?? ''),
            'snapshotOrderedQuantity' => $this->normalizeQuantity($orderItem['quantity'] ?? 0),
            'snapshotQuantityDecimals' => max(0, (int) ($orderItem['quantityDecimals'] ?? 0)),
            'withdrawnQuantity' => $this->normalizeQuantity($withdrawnQuantity),
        ];
    }

    public function isValidWithdrawnQuantity(float $withdrawnQuantity, float $orderedQuantity, float $minimumQuantity): bool
    {
        if ($orderedQuantity <= 0.0 || $minimumQuantity <= 0.0) {
            return false;
        }

        return $withdrawnQuantity >= $minimumQuantity && $withdrawnQuantity <= $orderedQuantity;
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
            'snapshotShippingMethod',
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
}
