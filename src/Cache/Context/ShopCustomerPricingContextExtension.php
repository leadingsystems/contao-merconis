<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Context;

use Merconis\Core\ls_shop_generalHelper;

/**
 * Context extension that exposes pricing- and customer-related dimensions
 * via getters (dimension names in parentheses):
 * - getOutputPriceType (outputPriceType)
 * - getCheckVATID (checkVATID)
 * - getCustomerCountry (customerCountry)
 * - getLastBackendDataChange (lastBackendDataChange)
 * - getCustomerGroupId (customerGroupId)
 */
final class ShopCustomerPricingContextExtension
{
    public function getOutputPriceType(): string
    {
        try {
            return (string) ls_shop_generalHelper::getOutputPriceType();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function getCheckVATID(): bool
    {
        try {
            return (bool) ls_shop_generalHelper::checkVATID();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getCustomerCountry(): string
    {
        try {
            return (string) ls_shop_generalHelper::getCustomerCountry();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function getLastBackendDataChange(): int
    {
        try {
            /** @var array<string, mixed> $TL_CONFIG */
            $TL_CONFIG = $GLOBALS['TL_CONFIG'] ?? [];
            $value = $TL_CONFIG['ls_shop_lastBackendDataChange'] ?? 0;
            return (int) $value;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function getCustomerGroupId(): ?int
    {
        try {
            $group = ls_shop_generalHelper::getGroupSettings4User();
            if (is_array($group) && array_key_exists('id', $group)) {
                $id = (int) $group['id'];
                return $id > 0 ? $id : null;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }
}


