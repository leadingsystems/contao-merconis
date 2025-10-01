<?php

namespace LeadingSystems\MerconisBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;
use LeadingSystems\MerconisBundle\Cache\CacheElementNoHitException;

class MerconisCacheElement
{
    /** @var CacheItemPoolInterface */
    private $cachePool;

    /** @var string */
    private $namespacePrefix;

    /** @var string */
    private $elementKey;

    /** @var int */
    private $ttlSeconds;

    /** @var array */
    private $tags;

    public function __construct(CacheItemPoolInterface $cachePool, string $namespacePrefix, string $elementKey, int $ttlSeconds, array $tags)
    {
        $this->cachePool = $cachePool;
        $this->namespacePrefix = $namespacePrefix;
        $this->elementKey = $elementKey;
        $this->ttlSeconds = $ttlSeconds;
        $this->tags = $tags;
    }

    public function getContent()
    {
        $item = $this->cachePool->getItem($this->elementKey);
        if (!$item->isHit()) {
            throw new CacheElementNoHitException('No cache hit for element');
        }
        return $item->get();
    }

    public function storeContent($content): void
    {
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
            $list = $idxItem->isHit() ? (array) $idxItem->get() : array();
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

    private function tagIndexKey(string $key, $value): string
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
        $token = $key . '=' . $v;
        return $this->namespacePrefix . 'tagindex.' . sha1($token);
    }
}


