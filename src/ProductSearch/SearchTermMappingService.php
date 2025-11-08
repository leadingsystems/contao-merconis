<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use Contao\Database;

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

    private function ensureLoaded(string $mode): void
    {
        if (self::$cacheByMode !== null && array_key_exists($mode, self::$cacheByMode)) {
            return;
        }
        if (self::$cacheByMode === null) {
            self::$cacheByMode = [];
        }
        self::$cacheByMode[$mode] = [
            'exact' => [],
            'patterns' => []
        ];
        // Treat empty mode/both as 'both' and filter to current mode
        $modeParam = in_array($mode, ['quick','full'], true) ? $mode : 'full';
		$result = Database::getInstance()->prepare("SELECT matchType, sourceNormalized, targetTerm, removeSource, removeSourceTiming, pattern, caseInsensitive FROM tl_ls_shop_search_term_mapping WHERE active = '1' AND (mode='' OR mode='both' OR mode=?) ORDER BY sorting, id")
            ->execute($modeParam);
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
				$timing = (string) ($result->removeSourceTiming ?? '');
				$removeImmediate = $remove && ($timing === 'immediate');
                self::$cacheByMode[$mode]['patterns'][] = [
                    'compiled' => $compiled,
                    'targetTemplate' => $target,
					'removeSource' => $remove,
					'removeImmediate' => $removeImmediate,
                ];
                continue;
            }

            // exact
            $normalized = (string) $result->sourceNormalized;
            if ($normalized !== '' && $target !== '') {
                if (!isset(self::$cacheByMode[$mode]['exact'][$normalized])) {
                    self::$cacheByMode[$mode]['exact'][$normalized] = ['targets' => [], 'removeAny' => false];
                }
                self::$cacheByMode[$mode]['exact'][$normalized]['targets'][] = $target;
                if ($remove) {
                    self::$cacheByMode[$mode]['exact'][$normalized]['removeAny'] = true;
                }
            }
        }
    }

    public function augment(string $rawQuery, string $mode = 'full'): string
    {
        if (!$this->enabled) {
            return $rawQuery;
        }
        $tokens = preg_split('/\s+/', trim($rawQuery)) ?: [];
        $augmented = $this->augmentTokens($tokens, $mode);
        return trim(implode(' ', $augmented));
    }

    public function augmentTokens(array $tokens, string $mode = 'full'): array
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
            if (isset(self::$cacheByMode[$mode]['exact'][$tNorm])) {
                $exactEntry = self::$cacheByMode[$mode]['exact'][$tNorm];
                $removeOriginal = $removeOriginal || (bool) ($exactEntry['removeAny'] ?? false);
                foreach ((array) ($exactEntry['targets'] ?? []) as $target) {
                    $targetStr = (string) $target;
                    if ($targetStr === '') { continue; }
                    $targetsToAppend[] = $targetStr;
                }
            }

            // Pattern rules (apply all that match, in configured order)
            foreach ((array) self::$cacheByMode[$mode]['patterns'] as $rule) {
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


