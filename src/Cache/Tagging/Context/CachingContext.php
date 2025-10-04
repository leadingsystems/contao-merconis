<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Context;

use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;

final class CachingContext implements CachingContextInterface
{
    private $userLoggedIn;
    private $userId;
    private $userGroupIds;
    private $countryCode;
    private $shippingCountryCode;
    private $currency;
    private $language;
    private $priceDisplayMode;
    private $salesChannelId;
    private $taxZoneId;
    private $customerType;
    private $previewMode;
    private $deviceType;

    public function __construct()
    {
    }

    public function isUserLoggedIn(): bool
    {
        if ($this->userLoggedIn === null) {
            $this->userLoggedIn = false;
        }
        return $this->userLoggedIn;
    }

    public function getUserId(): ?int
    {
        if ($this->userId === null) {
            $this->userId = $this->isUserLoggedIn() ? 0 : null;
        }
        return $this->userId;
    }

    public function getUserGroupIds(): array
    {
        if ($this->userGroupIds === null) {
            $groups = array();
            $groups = array_values(array_unique(array_map('intval', $groups)));
            sort($groups, SORT_NUMERIC);
            $this->userGroupIds = $groups;
        }
        return $this->userGroupIds;
    }

    public function getCountryCode(): ?string
    {
        if ($this->countryCode === null) {
            $this->countryCode = null;
        }
        return $this->countryCode;
    }

    public function getShippingCountryCode(): ?string
    {
        if ($this->shippingCountryCode === null) {
            $this->shippingCountryCode = null;
        }
        return $this->shippingCountryCode;
    }

    public function getCurrency(): ?string
    {
        if ($this->currency === null) {
            $this->currency = null;
        }
        return $this->currency;
    }

    public function getLanguage(): ?string
    {
        if ($this->language === null) {
            $this->language = null;
        }
        return $this->language;
    }

    public function getPriceDisplayMode(): ?string
    {
        if ($this->priceDisplayMode === null) {
            $this->priceDisplayMode = null;
        }
        return $this->priceDisplayMode;
    }

    public function getSalesChannelId(): ?string
    {
        if ($this->salesChannelId === null) {
            $this->salesChannelId = null;
        }
        return $this->salesChannelId;
    }

    public function getTaxZoneId(): ?string
    {
        if ($this->taxZoneId === null) {
            $this->taxZoneId = null;
        }
        return $this->taxZoneId;
    }

    public function getCustomerType(): ?string
    {
        if ($this->customerType === null) {
            $this->customerType = null;
        }
        return $this->customerType;
    }

    public function isPreviewMode(): bool
    {
        if ($this->previewMode === null) {
            $this->previewMode = false;
        }
        return (bool) $this->previewMode;
    }

    public function getDeviceType(): ?string
    {
        if ($this->deviceType === null) {
            $this->deviceType = null;
        }
        return $this->deviceType;
    }

    public function buildVariantHash(array $dimensions): string
    {
        $normalized = $this->collectDimensionMap($dimensions);
        return substr(hash('sha256', json_encode($normalized)), 0, 16);
    }

    public function buildContextTags(array $dimensions): array
    {
        $map = $this->collectDimensionMap($dimensions);
        $tags = new TagSet();
        foreach ($map as $name => $value) {
            if ($value === null || $value === '' || $value === array()) {
                continue;
            }
            switch ($name) {
                case 'login_state':
                    $tags->add('ctx:login:' . ($value ? 'user' : 'guest'));
                    break;
                case 'user_id':
                    $tags->add('ctx:user:' . (int) $value);
                    break;
                case 'user_group_ids':
                    foreach ($value as $gid) {
                        $tags->add('ctx:user_group:' . (int) $gid);
                    }
                    break;
                case 'country':
                    $tags->add('ctx:country:' . strtoupper((string) $value));
                    break;
                case 'shipping_country':
                    $tags->add('ctx:ship_country:' . strtoupper((string) $value));
                    break;
                case 'currency':
                    $tags->add('ctx:currency:' . strtoupper((string) $value));
                    break;
                case 'language':
                    $tags->add('ctx:language:' . strtolower((string) $value));
                    break;
                case 'price_display_mode':
                    $tags->add('ctx:price_mode:' . strtolower((string) $value));
                    break;
                case 'sales_channel':
                    $tags->add('ctx:channel:' . (string) $value);
                    break;
                case 'tax_zone':
                    $tags->add('ctx:tax_zone:' . (string) $value);
                    break;
                case 'customer_type':
                    $tags->add('ctx:customer_type:' . (string) $value);
                    break;
                case 'device_type':
                    $tags->add('ctx:device:' . (string) $value);
                    break;
            }
        }
        return $tags->toArray();
    }

    private function collectDimensionMap(array $dimensions): array
    {
        $normalizedNames = array_map(static function ($d) { return strtolower(trim((string) $d)); }, $dimensions);
        $normalizedNames = array_values(array_unique($normalizedNames));
        sort($normalizedNames, SORT_STRING);
        $map = array();
        foreach ($normalizedNames as $name) {
            switch ($name) {
                case 'login_state':
                    $map[$name] = $this->isUserLoggedIn();
                    break;
                case 'user_id':
                    $map[$name] = $this->getUserId();
                    break;
                case 'user_group_ids':
                    $map[$name] = $this->getUserGroupIds();
                    break;
                case 'country':
                    $map[$name] = $this->getCountryCode();
                    break;
                case 'shipping_country':
                    $map[$name] = $this->getShippingCountryCode();
                    break;
                case 'currency':
                    $map[$name] = $this->getCurrency();
                    break;
                case 'language':
                    $map[$name] = $this->getLanguage();
                    break;
                case 'price_display_mode':
                    $map[$name] = $this->getPriceDisplayMode();
                    break;
                case 'sales_channel':
                    $map[$name] = $this->getSalesChannelId();
                    break;
                case 'tax_zone':
                    $map[$name] = $this->getTaxZoneId();
                    break;
                case 'customer_type':
                    $map[$name] = $this->getCustomerType();
                    break;
                case 'device_type':
                    $map[$name] = $this->getDeviceType();
                    break;
            }
        }
        return $map;
    }
}


