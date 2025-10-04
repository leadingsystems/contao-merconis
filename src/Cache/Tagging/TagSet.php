<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging;

final class TagSet
{
    private $tags = array();

    public function add(string $tag): self
    {
        $tag = trim($tag);
        if ($tag !== '') {
            $this->tags[] = $tag;
        }
        return $this;
    }

    public function addMany(array $tags): self
    {
        foreach ($tags as $tag) {
            if (is_string($tag)) {
                $this->add($tag);
            }
        }
        return $this;
    }

    public function toArray(): array
    {
        $unique = array_values(array_unique($this->tags));
        sort($unique, SORT_STRING);
        return $unique;
    }
}


