<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Context;

/**
 * CachingContextInterface
 *
 * Provides lazy, normalized access to dimensions that are commonly relevant
 * for cache variation in Merconis/Contao storefronts. Implementations should
 * compute each value at most once per request and memoize results.
 *
 * The returned values are primitives or small arrays only. Heavy objects are
 * intentionally avoided to keep the context cheap and serializable.
 */
interface CachingContextInterface
{
    public function isUserLoggedIn(): bool;
    public function getUserId(): ?int;
    public function getUserGroupIds(): array;
    public function getCountryCode(): ?string;
    public function getShippingCountryCode(): ?string;
    public function getCurrency(): ?string;
    public function getLanguage(): ?string;
    public function getPriceDisplayMode(): ?string;
    public function getSalesChannelId(): ?string;
    public function getTaxZoneId(): ?string;
    public function getCustomerType(): ?string;
    public function isPreviewMode(): bool;
    public function getDeviceType(): ?string;

    /** Build a deterministic variant hash for the given dimensions. */
    public function buildVariantHash(array $dimensions): string;

    /** Build standardized `ctx:*` tags for the given dimensions. */
    public function buildContextTags(array $dimensions): array;
}


