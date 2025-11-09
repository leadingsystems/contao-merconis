<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use Contao\Database;
use LeadingSystems\MerconisBundle\ProductSearch\Enum\MappingMode;

class SearchTermMappingService
{
    // Cache structure per mode:
    // cacheByMode[mode] = [
    //   'exact' => array<string, array{targets: string[], removeAny: bool}>,
    //   'patterns' => array<int, array{compiled: string, targetTemplate: string, removeSource: bool, removeImmediate: bool}>
    // ]
    private static ?array $cacheByMode = null;

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
        self::$cacheByMode = null;
    }

    private function ensureLoaded(MappingMode $mode): void
    {
        if (self::$cacheByMode !== null && array_key_exists($mode->value, self::$cacheByMode)) {
            return;
        }
        if (self::$cacheByMode === null) {
            self::$cacheByMode = [];
        }
        self::$cacheByMode[$mode->value] = [
            'exact' => [],
            'patterns' => []
        ];
        // Load all nodes for current mode
        $rows = Database::getInstance()->prepare("
            SELECT id, pid, type, active, sorting,
                   matchType, sourceNormalized, targetTerm, removeSource, removeSourceTiming, pattern, caseInsensitive
            FROM tl_ls_shop_search_term_mapping
            WHERE active = '1' AND (mode='' OR mode='both' OR mode=?)
            ORDER BY sorting, id
        ")->execute($mode->value);

        $byId = [];
        $childrenByPid = [];
        while ($rows->next()) {
            $row = $rows->row();
            $byId[(int)$row['id']] = $row;
            $pid = (int) ($row['pid'] ?? 0);
            if (!isset($childrenByPid[$pid])) { $childrenByPid[$pid] = []; }
            $childrenByPid[$pid][] = $row;
        }

        // Helper to process a mapping row into cache
        $processMapping = function(array $r) use ($mode) {
            $matchType = (string) ($r['matchType'] ?? 'exact');
            $target = (string) ($r['targetTerm'] ?? '');
            $remove = ((string) ($r['removeSource'] ?? '') === '1');
            if ($matchType === 'regex') {
                $rawPattern = trim((string) ($r['pattern'] ?? ''));
                if ($rawPattern === '' || $target === '') { return; }
                $delimiter = '#';
                $escaped = str_replace($delimiter, '\\' . $delimiter, $rawPattern);
                $flags = 'u' . (((string)($r['caseInsensitive'] ?? '') === '1') ? 'i' : '');
                $compiled = $delimiter . $escaped . $delimiter . $flags;
                set_error_handler(function() {});
                try { $ok = @preg_match($compiled, '') !== false; } finally { restore_error_handler(); }
                if (!$ok) { return; }
                $timing = (string) ($r['removeSourceTiming'] ?? '');
                $removeImmediate = $remove && ($timing === 'immediate');
                self::$cacheByMode[$mode->value]['patterns'][] = [
                    'compiled' => $compiled,
                    'targetTemplate' => $target,
                    'removeSource' => $remove,
                    'removeImmediate' => $removeImmediate,
                ];
                return;
            }
            $normalized = (string) ($r['sourceNormalized'] ?? '');
            if ($normalized !== '' && $target !== '') {
                if (!isset(self::$cacheByMode[$mode->value]['exact'][$normalized])) {
                    self::$cacheByMode[$mode->value]['exact'][$normalized] = ['targets' => [], 'removeAny' => false];
                }
                self::$cacheByMode[$mode->value]['exact'][$normalized]['targets'][] = $target;
                if ($remove) {
                    self::$cacheByMode[$mode->value]['exact'][$normalized]['removeAny'] = true;
                }
            }
        };

        // Recursive traversal honoring sorting at each level; groups control order, mappings produce rules
        $walk = function(int $pid) use (&$walk, $childrenByPid, $processMapping) {
            $nodes = $childrenByPid[$pid] ?? [];
            usort($nodes, function ($a, $b) {
                $sa = (int) ($a['sorting'] ?? 0);
                $sb = (int) ($b['sorting'] ?? 0);
                if ($sa === $sb) { return ((int)$a['id']) <=> ((int)$b['id']); }
                return $sa <=> $sb;
            });
            foreach ($nodes as $n) {
                $t = (string) ($n['type'] ?? 'mapping');
                if ($t === 'mapping') {
                    $processMapping($n);
                } else {
                    $walk((int)$n['id']);
                }
            }
        };
        $walk(0);
    }

    public function augment(string $rawQuery, MappingMode $mode = MappingMode::Full): string
    {
        if (!$this->enabled) {
            return $rawQuery;
        }
        $tokens = preg_split('/\s+/', trim($rawQuery)) ?: [];
        $augmented = $this->augmentTokens($tokens, $mode);
        return trim(implode(' ', $augmented));
    }

    public function augmentTokens(array $tokens, MappingMode $mode = MappingMode::Full): array
    {
        if (!$this->enabled) {
            return $tokens;
        }
        $this->ensureLoaded($mode);

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
            if (isset(self::$cacheByMode[$mode->value]['exact'][$tNorm])) {
                $exactEntry = self::$cacheByMode[$mode->value]['exact'][$tNorm];
                $removeOriginal = $removeOriginal || (bool) ($exactEntry['removeAny'] ?? false);
                foreach ((array) ($exactEntry['targets'] ?? []) as $target) {
                    $targetStr = (string) $target;
                    if ($targetStr === '') { continue; }
                    $targetsToAppend[] = $targetStr;
                }
            }

            // Pattern rules (apply all that match, in configured order)
            foreach ((array) self::$cacheByMode[$mode->value]['patterns'] as $rule) {
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
						if (!empty($rule['removeImmediate'])) {
							// Stop evaluating further pattern rules for this token when immediate removal is requested
							break;
						}
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


