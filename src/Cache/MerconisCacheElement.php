<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache;

use LeadingSystems\Helpers\PerfGuard;
use Psr\Cache\CacheItemPoolInterface;
use LeadingSystems\MerconisBundle\Cache\CacheElementNoHitException;

class MerconisCacheElement
{
    private CacheItemPoolInterface $cachePool;

    private string $namespacePrefix;

    private string $elementKey;

    private int $ttlSeconds;

    private array $tags;

    public function __construct(CacheItemPoolInterface $cachePool, string $namespacePrefix, string $elementKey, int $ttlSeconds, array $tags)
    {
        $this->cachePool = $cachePool;
        $this->namespacePrefix = $namespacePrefix;
        $this->elementKey = $elementKey;
        $this->ttlSeconds = $ttlSeconds;
        $this->tags = $tags;
    }

    public function getContent(): mixed
    {
        $item = $this->cachePool->getItem($this->elementKey);
        if (!$item->isHit()) {
            throw new CacheElementNoHitException('No cache hit for element');
        }
        return $item->get();
    }

    public function storeContent(mixed $content): void
    {
        $__pg = new PerfGuard(__METHOD__);
        $item = $this->cachePool->getItem($this->elementKey);
        $item->set($content);
        if ($this->ttlSeconds > 0) {
            $item->expiresAfter($this->ttlSeconds);
        }
        $this->cachePool->save($item);

        // Register this element under all tag indices for later invalidation
        foreach ($this->tags as $key => $value) {
            $tokenKey = $this->tagIndexKey($key, $value);
            $idxItem = $this->cachePool->getItem($tokenKey);
            $list = $idxItem->isHit() ? (array) $idxItem->get() : [];
            if (!in_array($this->elementKey, $list, true)) {
                $list[] = $this->elementKey;
            }
            $idxItem->set($list);
            if ($this->ttlSeconds > 0) {
                $idxItem->expiresAfter($this->ttlSeconds);
            }
            $this->cachePool->save($idxItem);
        }
    }

    private function tagIndexKey(string $key, mixed $value): string
    {
        if (is_bool($value)) {
            $v = $value ? '1' : '0';
        } elseif (is_int($value) || is_float($value)) {
            $v = (string) $value;
        } elseif (is_null($value)) {
            $v = 'null';
        } else {
            $v = (string) $value;
        }
        $token = $key . '=' . $v;
        return $this->namespacePrefix . 'tagindex.' . sha1($token);
    }
}


