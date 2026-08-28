<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\CacheTags;

class MerconisCacheHandler
{
    private MerconisCache $service;

    private CacheItemPoolInterface $cachePool;

    private string $namespacePrefix;

    private int $defaultMaxWaitMs;

    private int $defaultRetryEveryMs;

    private ?CacheTags $cacheTags;

    public function __construct(MerconisCache $service, CacheItemPoolInterface $cachePool, string $namespacePrefix, int $defaultMaxWaitMs = 2000, int $defaultRetryEveryMs = 50, ?CacheTags $cacheTags = null)
    {
        $this->service = $service;
        $this->cachePool = $cachePool;
        $this->namespacePrefix = $namespacePrefix;
        $this->defaultMaxWaitMs = $defaultMaxWaitMs;
        $this->defaultRetryEveryMs = $defaultRetryEveryMs;
        $this->cacheTags = $cacheTags;
    }

    public function create(int $ttlSeconds, array $tags, ?int $maxWaitMs = null, ?int $retryEveryMs = null): MerconisCacheHandle
    {
        return new MerconisCacheHandle(
            $this->service,
            $this->cachePool,
            $this->namespacePrefix,
            $ttlSeconds,
            $tags,
            $maxWaitMs ?? $this->defaultMaxWaitMs,
            $retryEveryMs ?? $this->defaultRetryEveryMs
        );
    }

    /**
     * Convenience: build tags via recipe and create a cache handle. Falls back to empty tags if CacheTags not available.
     */
    public function createForRecipe(string $recipeName, array $entityParams, int $ttlSeconds, ?array $varyOnOverride = null, ?int $maxWaitMs = null, ?int $retryEveryMs = null): MerconisCacheHandle
    {
        $tags = [];
        if ($this->cacheTags instanceof CacheTags) {
            $tags = $this->cacheTags->getTagsFor($recipeName, $entityParams, $varyOnOverride);
        }
        return $this->create($ttlSeconds, $tags, $maxWaitMs, $retryEveryMs);
    }
}
