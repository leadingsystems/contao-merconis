<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;

class MerconisCacheHandle
{
    private MerconisCache $service;
    private CacheItemPoolInterface $cachePool;
    private string $namespacePrefix;
    private int $ttlSeconds;
    private array $tags;
    private int $maxWaitMs;
    private int $retryEveryMs;
    private MerconisCacheElement $element;
    private bool $locked = false;

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

    public function storeAndEcho(mixed $content): void
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
        $this->storeAndEcho($content === false ? '' : $content);
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
        $content = $content === false ? '' : $content;
        if (CacheToggle::$enabled) {
            $this->element->storeContent($content);
        }
        if ($this->locked) {
            $this->service->releaseComputeLock($this->tags);
            $this->locked = false;
        }
        return $content;
    }

    /**
     * Returns [true, $value] if cached; otherwise returns [false, null].
     * If another producer is working, waits and returns [true, $value] when ready; escalates to producer if needed.
     * @return array{0: bool, 1: mixed|null}
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

    public function storeValue(mixed $value): void
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


