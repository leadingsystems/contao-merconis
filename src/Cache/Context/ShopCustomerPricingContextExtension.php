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
    private ?string $outputPriceType = null;
    private ?bool $checkVATID = null;
    private ?string $customerCountry = null;
    private ?int $lastBackendDataChange = null;
    private bool $customerGroupIdInitialized = false;
    private ?int $customerGroupId = null;

    public function getOutputPriceType(): string
    {
        if ($this->outputPriceType !== null) {
            return $this->outputPriceType;
        }

        try {
            $this->outputPriceType = (string) ls_shop_generalHelper::getOutputPriceType();
        } catch (\Throwable $e) {
            $this->outputPriceType = '';
        }

        return $this->outputPriceType;
    }

    public function getCheckVATID(): bool
    {
        if ($this->checkVATID !== null) {
            return $this->checkVATID;
        }

        try {
            $this->checkVATID = (bool) ls_shop_generalHelper::checkVATID();
        } catch (\Throwable $e) {
            $this->checkVATID = false;
        }

        return $this->checkVATID;
    }

    public function getCustomerCountry(): string
    {
        if ($this->customerCountry !== null) {
            return $this->customerCountry;
        }

        try {
            $this->customerCountry = (string) ls_shop_generalHelper::getCustomerCountry();
        } catch (\Throwable $e) {
            $this->customerCountry = '';
        }

        return $this->customerCountry;
    }

    public function getLastBackendDataChange(): int
    {
        if ($this->lastBackendDataChange !== null) {
            return $this->lastBackendDataChange;
        }

        try {
            /** @var array<string, mixed> $TL_CONFIG */
            $TL_CONFIG = $GLOBALS['TL_CONFIG'] ?? [];
            $value = $TL_CONFIG['ls_shop_lastBackendDataChange'] ?? 0;
            $this->lastBackendDataChange = (int) $value;
        } catch (\Throwable $e) {
            $this->lastBackendDataChange = 0;
        }

        return $this->lastBackendDataChange;
    }

    public function getCustomerGroupId(): ?int
    {
        if ($this->customerGroupIdInitialized) {
            return $this->customerGroupId;
        }

        try {
            $group = ls_shop_generalHelper::getGroupSettings4User();
            if (is_array($group) && array_key_exists('id', $group)) {
                $id = (int) $group['id'];
                $this->customerGroupId = $id > 0 ? $id : null;
            } else {
                $this->customerGroupId = null;
            }
        } catch (\Throwable $e) {
            $this->customerGroupId = null;
        }

        $this->customerGroupIdInitialized = true;
        return $this->customerGroupId;
    }
}


