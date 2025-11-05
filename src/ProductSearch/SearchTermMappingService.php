<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use Contao\Database;

class SearchTermMappingService
{
    // Cache structure:
    // [
    //   'exact' => array<string, array{targets: string[], removeAny: bool}>,
    //   'patterns' => array<int, array{compiled: string, targetTemplate: string, removeSource: bool}>
    // ]
    private static ?array $cache = null;

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
        self::$cache = [
            'exact' => [],
            'patterns' => []
        ];
        $result = Database::getInstance()->prepare("SELECT matchType, sourceNormalized, targetTerm, removeSource, pattern, caseInsensitive FROM tl_ls_shop_search_term_mapping WHERE active = '1' ORDER BY sorting, id")
            ->execute();
        while ($result->next()) {
            $matchType = (string) ($result->matchType ?? 'exact');
            $target = (string) $result->targetTerm;
            $remove = (string) $result->removeSource === '1';
            if ($matchType === 'regex') {
                $rawPattern = trim((string) ($result->pattern ?? ''));
                if ($rawPattern === '' || $target === '') {
                    continue;
                }
                $delimiter = '#';
                $escaped = str_replace($delimiter, '\\' . $delimiter, $rawPattern);
                $flags = 'u' . (((string)$result->caseInsensitive === '1') ? 'i' : '');
                $compiled = $delimiter . $escaped . $delimiter . $flags;
                // Sanity check: skip invalid patterns silently
                set_error_handler(function() {});
                try {
                    $ok = @preg_match($compiled, '') !== false;
                } finally {
                    restore_error_handler();
                }
                if (!$ok) {
                    continue;
                }
                self::$cache['patterns'][] = [
                    'compiled' => $compiled,
                    'targetTemplate' => $target,
                    'removeSource' => $remove,
                ];
                continue;
            }

            // exact
            $normalized = (string) $result->sourceNormalized;
            if ($normalized !== '' && $target !== '') {
                if (!isset(self::$cache['exact'][$normalized])) {
                    self::$cache['exact'][$normalized] = ['targets' => [], 'removeAny' => false];
                }
                self::$cache['exact'][$normalized]['targets'][] = $target;
                if ($remove) {
                    self::$cache['exact'][$normalized]['removeAny'] = true;
                }
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

            $targetsToAppend = [];
            $removeOriginal = false;

            // Exact mappings
            if (isset(self::$cache['exact'][$tNorm])) {
                $exactEntry = self::$cache['exact'][$tNorm];
                $removeOriginal = $removeOriginal || (bool) ($exactEntry['removeAny'] ?? false);
                foreach ((array) ($exactEntry['targets'] ?? []) as $target) {
                    $targetStr = (string) $target;
                    if ($targetStr === '') { continue; }
                    $targetsToAppend[] = $targetStr;
                }
            }

            // Pattern rules (apply all that match, in configured order)
            foreach ((array) self::$cache['patterns'] as $rule) {
                $compiled = (string) ($rule['compiled'] ?? '');
                if ($compiled === '') { continue; }
                set_error_handler(function() {});
                try {
                    $isMatch = @preg_match($compiled, $raw) === 1;
                } finally {
                    restore_error_handler();
                }
                if ($isMatch) {
                    $tpl = (string) ($rule['targetTemplate'] ?? '');
                    if ($tpl !== '') {
                        $rendered = str_replace('{token}', $raw, $tpl);
                        $targetsToAppend[] = $rendered;
                    }
                    if (!empty($rule['removeSource'])) {
                        $removeOriginal = true;
                    }
                }
            }

            // Include original token only if no rule requested removal
            if (!$removeOriginal) {
                // Keep original token (do not dedupe originals, mimic legacy behavior)
                $resultTokens[] = $raw;
            }

            // Append targets, dedup case-insensitively
            foreach ($targetsToAppend as $targetStr) {
                $targetLower = mb_strtolower($targetStr);
                if (!isset($existingLower[$targetLower])) {
                    $resultTokens[] = $targetStr;
                    $existingLower[$targetLower] = true;
                }
            }
        }

        return array_values(array_filter($resultTokens, static fn($v) => $v !== null && $v !== ''));
    }
}


