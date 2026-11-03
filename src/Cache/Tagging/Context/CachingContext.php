<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Context;

use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;
use Symfony\Component\HttpFoundation\RequestStack;

final class CachingContext implements CachingContextInterface
{
    private ?string $language = null;

    private RequestStack $requestStack;

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
        // Values are normalized in the getters and empty values are filtered
        // in collectDimensionMap already, so we can return the map directly.
        return $this->collectDimensionMap($dimensions);
    }

    private function collectDimensionMap(array $dimensions): array
    {
        $normalizedNames = array_map(static function ($d) { return strtolower(trim((string) $d)); }, $dimensions);
        $normalizedNames = array_values(array_unique($normalizedNames));
        sort($normalizedNames, SORT_STRING);
        $map = [];
        foreach ($normalizedNames as $name) {
            // Translate dimension name (e.g., "language", "customer_group") to getter (e.g., getLanguage, getCustomerGroup)
            $method = 'get' . str_replace(' ', '', ucwords(str_replace(array('-', '_', ' '), ' ', $name)));
            if (is_callable([$this, $method])) {
                $value = $this->$method();
                if ($value !== null && $value !== '' && $value !== []) {
                    $map[$name] = $value;
                }
            }
        }
        return $map;
    }
}


