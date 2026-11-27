<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Tagging;

final class TagSet
{
    /** @var array<string, scalar|array> */
    private array $tags = [];

    /** Add a key/value tag (value should be scalar for best compatibility). */
    public function add(string $key, mixed $value): self
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


