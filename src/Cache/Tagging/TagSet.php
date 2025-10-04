<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging;

final class TagSet
{
    /** @var array<string, scalar|array> */
    private $tags = array();

    /** Add a key/value tag (value should be scalar for best compatibility). */
    public function add(string $key, $value): self
    {
        $key = trim($key);
        if ($key === '') {
            return $this;
        }
        $this->tags[$key] = $value;
        return $this;
    }

    /** Merge an associative array of tags. */
    public function addMany(array $assoc): self
    {
        foreach ($assoc as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            $this->add($k, $v);
        }
        return $this;
    }

    /** Return a stable, key-sorted associative array of tags. */
    public function toArray(): array
    {
        ksort($this->tags, SORT_STRING);
        return $this->tags;
    }
}


