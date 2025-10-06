<?php

namespace LeadingSystems\MerconisBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\CacheTags;

class MerconisCacheHandler
{
    /** @var MerconisCache */
    private $service;

    /** @var CacheItemPoolInterface */
    private $cachePool;

    /** @var string */
    private $namespacePrefix;

    /** @var int */
    private $defaultMaxWaitMs;

    /** @var int */
    private $defaultRetryEveryMs;

    /** @var CacheTags|null */
    private $cacheTags;

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
        $tags = array();
        if ($this->cacheTags instanceof CacheTags) {
            $tags = $this->cacheTags->getTagsFor($recipeName, $entityParams, $varyOnOverride);
        }
        return $this->create($ttlSeconds, $tags, $maxWaitMs, $retryEveryMs);
    }
}

class MerconisCacheHandle
{
    /** @var MerconisCache */
    private $service;
    /** @var CacheItemPoolInterface */
    private $cachePool;
    /** @var string */
    private $namespacePrefix;
    /** @var int */
    private $ttlSeconds;
    /** @var array */
    private $tags;
    /** @var int */
    private $maxWaitMs;
    /** @var int */
    private $retryEveryMs;
    /** @var MerconisCacheElement */
    private $element;
    /** @var bool */
    private $locked = false;

    public function __construct(MerconisCache $service, CacheItemPoolInterface $cachePool, string $namespacePrefix, int $ttlSeconds, array $tags, int $maxWaitMs, int $retryEveryMs)
    {
        $this->service = $service;
        $this->cachePool = $cachePool;
        $this->namespacePrefix = $namespacePrefix;
        $this->ttlSeconds = $ttlSeconds;
        $this->tags = $tags;
        $this->maxWaitMs = $maxWaitMs;
        $this->retryEveryMs = $retryEveryMs;
        $this->element = $service->getCacheElement($ttlSeconds, $tags);
    }

    // Echo-mode helpers
    public function tryEcho(): bool
    {
        if (!CacheToggle::$enabled) {
            return false;
        }
        try {
            echo $this->element->getContent();
            return true;
        } catch (CacheElementNoHitException $e) {
            if ($this->service->acquireComputeLock($this->tags)) {
                $this->locked = true;
                return false;
            }
            $content = $this->service->waitForContent($this->ttlSeconds, $this->tags, $this->maxWaitMs, $this->retryEveryMs);
            if ($content !== null) {
                echo $content;
                return true;
            }
            if ($this->service->acquireComputeLock($this->tags)) {
                $this->locked = true;
            }
            return false;
        }
    }

    public function start(): bool
    {
        if (!CacheToggle::$enabled) {
            ob_start();
            return false;
        }
        if ($this->tryEcho()) {
            return true;
        }
        ob_start();
        return false;
    }

    public function storeAndEcho($content): void
    {
        if (CacheToggle::$enabled) {
            $this->element->storeContent($content);
        }
        if ($this->locked) {
            $this->service->releaseComputeLock($this->tags);
            $this->locked = false;
        }
        echo $content;
    }

    public function finish(): void
    {
        $content = ob_get_clean();
        $this->storeAndEcho($content);
    }

    // Capture-mode helpers (no echo)
    public function startCapture(): ?string
    {
        if (!CacheToggle::$enabled) {
            ob_start();
            return null;
        }
        try {
            return (string) $this->element->getContent();
        } catch (CacheElementNoHitException $e) {
            if ($this->service->acquireComputeLock($this->tags)) {
                $this->locked = true;
                ob_start();
                return null;
            }
            $content = $this->service->waitForContent($this->ttlSeconds, $this->tags, $this->maxWaitMs, $this->retryEveryMs);
            if ($content !== null) {
                return (string) $content;
            }
            if ($this->service->acquireComputeLock($this->tags)) {
                $this->locked = true;
            }
            ob_start();
            return null;
        }
    }

    public function finishCapture(): string
    {
        $content = ob_get_clean();
        if (CacheToggle::$enabled) {
            $this->element->storeContent($content);
        }
        if ($this->locked) {
            $this->service->releaseComputeLock($this->tags);
            $this->locked = false;
        }
        return $content;
    }

    // Value-mode helpers (arbitrary data)
    /**
     * Returns [true, $value] if cached; otherwise returns [false, null] and makes the caller the producer.
     * If another producer is working, waits and returns [true, $value] when ready; escalates to producer if needed.
     */
    public function getValueOrStart(): array
    {
        if (!CacheToggle::$enabled) {
            return [false, null];
        }
        try {
            return [true, $this->element->getContent()];
        } catch (CacheElementNoHitException $e) {
            if ($this->service->acquireComputeLock($this->tags)) {
                $this->locked = true;
                return [false, null];
            }
            $content = $this->service->waitForContent($this->ttlSeconds, $this->tags, $this->maxWaitMs, $this->retryEveryMs);
            if ($content !== null) {
                return [true, $content];
            }
            if ($this->service->acquireComputeLock($this->tags)) {
                $this->locked = true;
            }
            return [false, null];
        }
    }

    public function storeValue($value): void
    {
        if (CacheToggle::$enabled) {
            $this->element->storeContent($value);
        }
        if ($this->locked) {
            $this->service->releaseComputeLock($this->tags);
            $this->locked = false;
        }
    }
}


