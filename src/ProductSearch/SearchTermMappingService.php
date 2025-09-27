<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use Contao\Database;

class SearchTermMappingService
{
    private static ?array $cache = null; // normalized source => ['target' => string, 'remove' => bool]

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
        $result = Database::getInstance()->prepare("SELECT sourceNormalized, targetTerm, removeSource FROM tl_ls_shop_search_term_mapping WHERE active = '1'")
            ->execute();
        while ($result->next()) {
            $normalized = (string) $result->sourceNormalized;
            $target = (string) $result->targetTerm;
            $remove = (string) $result->removeSource === '1';
            if ($normalized !== '' && $target !== '') {
                self::$cache[$normalized] = ['target' => $target, 'remove' => $remove];
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

        $resultTokens = [];
        foreach ($tokens as $t) {
            $raw = (string) $t;
            $tNorm = mb_strtolower(trim($raw));
            if ($tNorm === '') {
                continue;
            }
            if (isset(self::$cache[$tNorm])) {
                $target = (string) self::$cache[$tNorm]['target'];
                $remove = (bool) self::$cache[$tNorm]['remove'];
                $targetLower = mb_strtolower($target);
                if ($remove) {
                    // Replace source with target
                    if ($targetLower !== '' && !isset($existingLower[$targetLower])) {
                        $resultTokens[] = $target;
                        $existingLower[$targetLower] = true;
                    }
                    // Do not add original token
                    continue;
                } else {
                    // Keep original and append target if not present
                    $resultTokens[] = $raw;
                    if ($targetLower !== '' && !isset($existingLower[$targetLower])) {
                        $resultTokens[] = $target;
                        $existingLower[$targetLower] = true;
                    }
                    continue;
                }
            }
            // No mapping: keep original
            $resultTokens[] = $raw;
        }

        return array_values(array_filter($resultTokens, static fn($v) => $v !== null && $v !== ''));
    }
}


