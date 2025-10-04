<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Context;

use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;
use Symfony\Component\HttpFoundation\RequestStack;

final class CachingContext implements CachingContextInterface
{
    private $language;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getLanguage(): ?string
    {
        if ($this->language === null) {
            // Prefer Contao's resolved frontend language when available
            if (isset($GLOBALS['TL_LANGUAGE']) && is_string($GLOBALS['TL_LANGUAGE']) && $GLOBALS['TL_LANGUAGE'] !== '') {
                $this->language = strtolower($GLOBALS['TL_LANGUAGE']);
            } else {
                // Fallback: use current request locale and normalize to primary subtag
                $req = $this->requestStack->getCurrentRequest();
                $locale = $req ? $req->getLocale() : null;
                if (is_string($locale) && $locale !== '') {
                    $primary = strtolower((string) preg_replace('~[_-].*$~', '', $locale));
                    $this->language = $primary !== '' ? $primary : null;
                } else {
                    $this->language = null;
                }
            }
        }
        return $this->language;
    }

    public function buildContextKey(array $dimensions): string
    {
        $normalized = $this->collectDimensionMap($dimensions);
        return substr(hash('sha256', json_encode($normalized)), 0, 16);
    }

    public function buildContextTags(array $dimensions): array
    {
        $map = $this->collectDimensionMap($dimensions);
        $tags = new TagSet();
        foreach ($map as $name => $value) {
            if ($value === null || $value === '' || $value === array()) {
                continue;
            }
            switch ($name) {
                case 'language':
                    $tags->add('language', strtolower((string) $value));
                    break;
            }
        }
        return $tags->toArray();
    }

    private function collectDimensionMap(array $dimensions): array
    {
        $normalizedNames = array_map(static function ($d) { return strtolower(trim((string) $d)); }, $dimensions);
        $normalizedNames = array_values(array_unique($normalizedNames));
        sort($normalizedNames, SORT_STRING);
        $map = array();
        foreach ($normalizedNames as $name) {
            switch ($name) {
                case 'language':
                    $map[$name] = $this->getLanguage();
                    break;
            }
        }
        return $map;
    }
}


