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
    /** Current language code (e.g., 'de', 'en'). */
    public function getLanguage(): ?string;

    /** Build a deterministic, stable key from the given dimensions (order-insensitive). */
    public function buildContextKey(array $dimensions): string;

    /** Build standardized context tags for the given dimensions (currently supports 'language'). */
    public function buildContextTags(array $dimensions): array;
}


