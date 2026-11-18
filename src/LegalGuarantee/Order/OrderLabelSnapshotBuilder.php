<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupLgnModeProviderInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;

final class OrderLabelSnapshotBuilder
{
    public const REQUIRES_LGN_ORDER_SNAPSHOT_KEY = '_requiresLgnOrderSnapshot';

    public function __construct(
        private readonly ProductGuaranteeDisplayResolver $displayResolver,
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
        private readonly MemberGroupLgnModeProviderInterface $memberGroupLgnModeProvider,
    ) {
    }

    /**
     * Hält das Ergebnis für die maßgebliche Gruppe des Käufers zum Bestellzeitpunkt fest.
     * Der Gruppenwert selbst wird nicht gespeichert.
     *
     * @param iterable<string, mixed> $productData
     * @param iterable<string, mixed>|null $variantData
     *
     * @return array<string, string|null>
     */
    public function buildItemSnapshot(iterable $productData, ?iterable $variantData = null): array
    {
        $displayData = $this->displayResolver->resolve(
            $this->memberGroupLgnModeProvider->getCurrentMemberGroupLgnMode(),
            $productData,
            $variantData
        );
        $garanData = $displayData['garan'];

        return [
            self::REQUIRES_LGN_ORDER_SNAPSHOT_KEY => $displayData['showLgn'] ? '1' : '',
            'garanVersion' => $displayData['showGaran']
                ? OfficialGuaranteeAssetLocator::GARAN_VERSION
                : '',
            'garanBrand' => $displayData['showGaran'] && null !== $garanData
                ? $garanData['brand']
                : '',
            'garanModelIdentifier' => $displayData['showGaran'] && null !== $garanData
                ? $garanData['modelIdentifier']
                : '',
            'garanDurationMonths' => $displayData['showGaran'] && null !== $garanData
                ? $garanData['durationMonths']
                : null,
        ];
    }

    /**
     * @param array<string, mixed> $orderItem
     * @param iterable<string, mixed> $productData
     * @param iterable<string, mixed>|null $variantData
     *
     * @return array<string, mixed>
     */
    public function enrichOrderItem(array $orderItem, iterable $productData, ?iterable $variantData = null): array
    {
        return array_merge($orderItem, $this->buildItemSnapshot($productData, $variantData));
    }

    /**
     * @param array<string, mixed> $order
     *
     * @return array<string, string>
     */
    public function buildOrderSnapshot(array $order): array
    {
        $customerLanguage = trim((string) ($order['customerLanguage'] ?? ''));

        if ('' === $customerLanguage || !$this->requiresLgnOrderSnapshot($order['items'] ?? [])) {
            return [
                'lgnVersion' => '',
                'lgnLanguage' => '',
            ];
        }

        return [
            'lgnVersion' => OfficialGuaranteeAssetLocator::LGN_VERSION,
            'lgnLanguage' => $this->assetLocator->resolveLgnSvgLanguage($customerLanguage),
        ];
    }

    /**
     * @param array<string, mixed> $order
     *
     * @return array<string, mixed>
     */
    public function enrichOrder(array $order): array
    {
        return array_merge($order, $this->buildOrderSnapshot($order));
    }

    /**
     * @param mixed $items
     */
    private function requiresLgnOrderSnapshot(mixed $items): bool
    {
        if (!is_iterable($items)) {
            return false;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!empty($item[self::REQUIRES_LGN_ORDER_SNAPSHOT_KEY])) {
                return true;
            }
        }

        return false;
    }
}
