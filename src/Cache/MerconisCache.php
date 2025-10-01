<?php

namespace LeadingSystems\MerconisBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;
use LeadingSystems\MerconisBundle\Cache\CacheElementNoHitException;

class MerconisCache
{
    /** @var CacheItemPoolInterface */
    private $cachePool;

    /** @var string */
    private $namespacePrefix;

    public function __construct(CacheItemPoolInterface $cachePool, string $namespacePrefix = 'merconis.custom.cache.')
    {
        $this->cachePool = $cachePool;
        $this->namespacePrefix = rtrim($namespacePrefix, '.') . '.';
    }

    public function getCacheElement(int $ttlSeconds, array $tags): MerconisCacheElement
    {
        $normalizedTags = $this->normalizeTags($tags);
        $tagHash = sha1(json_encode($normalizedTags));
        $elementKey = $this->namespacePrefix . 'element.' . $tagHash;
        return new MerconisCacheElement($this->cachePool, $this->namespacePrefix, $elementKey, $ttlSeconds, $normalizedTags);
    }

    public function invalidateCacheElementsByTags(array $tags): void
    {
        $normalizedTags = $this->normalizeTags($tags);
        if (!$normalizedTags) {
            return;
        }

        $tokenKeys = array();
        foreach ($normalizedTags as $key => $value) {
            $tokenKeys[] = $this->tagIndexKey($key, $value);
        }

        $lists = array();
        foreach ($tokenKeys as $tokenKey) {
            $item = $this->cachePool->getItem($tokenKey);
            $lists[] = $item->isHit() ? (array) $item->get() : array();
        }

        if (!$lists) {
            return;
        }

        $keysToDelete = array_shift($lists);
        foreach ($lists as $l) {
            $keysToDelete = array_values(array_intersect($keysToDelete, $l));
            if (!$keysToDelete) {
                break;
            }
        }

        if ($keysToDelete) {
            foreach ($keysToDelete as $k) {
                $this->cachePool->deleteItem($k);
            }
        }
    }

    /**
     * Compute with stampede protection.
     */
    public function compute(int $ttlSeconds, array $tags, callable $producer, int $maxWaitMs = 2000, int $retryEveryMs = 50)
    {
        $element = $this->getCacheElement($ttlSeconds, $tags);
        try {
            return $element->getContent();
        } catch (CacheElementNoHitException $e) {
            // continue
        }

        $lockKey = $this->computeLockKey($tags);

        $t0 = (int) (microtime(true) * 1000);
        $haveLock = false;
        while (true) {
            // try to acquire lock
            $lockItem = $this->cachePool->getItem($lockKey);
            if (!$lockItem->isHit()) {
                $lockItem->set('1');
                $lockItem->expiresAfter(5);
                $this->cachePool->save($lockItem);
                $haveLock = true;
            }

            if ($haveLock) {
                try {
                    $result = $producer();
                    $element->storeContent($result);
                    return $result;
                } finally {
                    $this->cachePool->deleteItem($lockKey);
                }
            } else {
                usleep(max(1, $retryEveryMs) * 1000);
                try {
                    return $element->getContent();
                } catch (CacheElementNoHitException $e) {
                }
                if (((int) (microtime(true) * 1000) - $t0) >= max(0, $maxWaitMs)) {
                    $result = $producer();
                    $element->storeContent($result);
                    return $result;
                }
            }
        }
    }

    /**
     * Acquire a short-lived compute lock for the given tag set.
     * Returns true if the caller now holds the lock.
     */
    public function acquireComputeLock(array $tags, int $lockTtlSeconds = 5): bool
    {
        $lockKey = $this->computeLockKey($tags);
        $lockItem = $this->cachePool->getItem($lockKey);
        if ($lockItem->isHit()) {
            return false;
        }
        $lockItem->set('1');
        $lockItem->expiresAfter(max(1, $lockTtlSeconds));
        $this->cachePool->save($lockItem);
        return true;
    }

    /**
     * Release a compute lock acquired via acquireComputeLock.
     */
    public function releaseComputeLock(array $tags): void
    {
        $lockKey = $this->computeLockKey($tags);
        $this->cachePool->deleteItem($lockKey);
    }

    /**
     * Wait briefly for content to appear (another process is computing) and return it if available; null otherwise.
     */
    public function waitForContent(int $ttlSeconds, array $tags, int $maxWaitMs = 2000, int $retryEveryMs = 50)
    {
        $element = $this->getCacheElement($ttlSeconds, $tags);
        $t0 = (int) (microtime(true) * 1000);
        while (true) {
            try {
                return $element->getContent();
            } catch (CacheElementNoHitException $e) {
                // keep waiting
            }
            if (((int) (microtime(true) * 1000) - $t0) >= max(0, $maxWaitMs)) {
                return null;
            }
            usleep(max(1, $retryEveryMs) * 1000);
        }
    }

    private function computeLockKey(array $tags): string
    {
        $normalizedTags = $this->normalizeTags($tags);
        $tagHash = sha1(json_encode($normalizedTags));
        return $this->namespacePrefix . 'lock.' . $tagHash;
    }

    private function tagIndexKey(string $key, $value): string
    {
        $token = $this->encodeScalarToken($key, $value);
        return $this->namespacePrefix . 'tagindex.' . sha1($token);
    }

    private function encodeScalarToken(string $key, $value): string
    {
        if (is_bool($value)) {
            $v = $value ? '1' : '0';
        } else if (is_int($value) || is_float($value)) {
            $v = (string) $value;
        } else if (is_null($value)) {
            $v = 'null';
        } else {
            $v = (string) $value;
        }
        return $key . '=' . $v;
    }

    private function normalizeTags(array $tags): array
    {
        $normalized = array();
        foreach ($tags as $k => $v) {
            if (is_array($v)) {
                $v = $this->normalizeArray($v);
            }
            $normalized[(string) $k] = $v;
        }
        ksort($normalized);
        return $normalized;
    }

    private function normalizeArray(array $arr): array
    {
        $out = array();
        foreach ($arr as $k => $v) {
            $key = (string) $k;
            if (is_array($v)) {
                $out[$key] = $this->normalizeArray($v);
            } else {
                $out[$key] = $v;
            }
        }
        ksort($out);
        return $out;
    }
}


