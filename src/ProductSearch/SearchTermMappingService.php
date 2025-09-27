<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use Contao\Database;

class SearchTermMappingService
{
    private static ?array $cache = null; // normalized source => target term

    private bool $enabled;
    private bool $applyInElasticsearch;

    public function __construct(bool $enabled = true, bool $applyInElasticsearch = false)
    {
        $this->enabled = $enabled;
        $this->applyInElasticsearch = $applyInElasticsearch;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isApplyInElasticsearch(): bool
    {
        return $this->applyInElasticsearch;
    }

    public function clearCache(): void
    {
        self::$cache = null;
    }

    private function ensureLoaded(): void
    {
        if (self::$cache !== null) {
            return;
        }
        self::$cache = [];
        $result = Database::getInstance()->prepare("SELECT sourceNormalized, targetTerm FROM tl_ls_shop_search_term_mapping WHERE active = '1'")
            ->execute();
        while ($result->next()) {
            $normalized = (string) $result->sourceNormalized;
            $target = (string) $result->targetTerm;
            if ($normalized !== '' && $target !== '') {
                self::$cache[$normalized] = $target;
            }
        }
    }

    public function augment(string $rawQuery): string
    {
        if (!$this->enabled) {
            return $rawQuery;
        }
        $tokens = preg_split('/\s+/', trim($rawQuery)) ?: [];
        $augmented = $this->augmentTokens($tokens);
        return trim(implode(' ', $augmented));
    }

    public function augmentTokens(array $tokens): array
    {
        if (!$this->enabled) {
            return $tokens;
        }
        $this->ensureLoaded();

        $existingLower = [];
        foreach ($tokens as $t) {
            $tStr = (string) $t;
            if ($tStr === '') {
                continue;
            }
            $existingLower[mb_strtolower($tStr)] = true;
        }

        $toAppend = [];
        foreach ($tokens as $t) {
            $tNorm = mb_strtolower(trim((string) $t));
            if ($tNorm === '') {
                continue;
            }
            if (isset(self::$cache[$tNorm])) {
                $target = (string) self::$cache[$tNorm];
                $targetLower = mb_strtolower($target);
                if ($targetLower !== '' && !isset($existingLower[$targetLower])) {
                    $toAppend[] = $target;
                    $existingLower[$targetLower] = true;
                }
            }
        }

        if (!empty($toAppend)) {
            return array_values(array_filter(array_merge($tokens, $toAppend), static fn($v) => $v !== null && $v !== ''));
        }

        return $tokens;
    }
}


