<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use Contao\Database;
use Contao\System;
use Merconis\Core\ls_shop_languageHelper;

class FacetPresenter
{
    /**
     * Build prioritized and capped attribute lists from Facets and current criteria.
     *
     * @param Facets $facets
     * @param array $criteria Expected to possibly contain ['attributes' => [[attribute_id,value_id]...]]
     * @param array $options Supported keys:
     *   - maxVisibleAttributes (int)
     *   - pinnedAliases (string[])
     *   - defaultMaxValuesPerAttribute (int)
     *   - language (string|null)
     * @return array{visibleAttributes: array<int,array>, hiddenAttributes: array<int,array>, visibleProducers?: array<int,array>, hiddenProducers?: array<int,array>, producerTitle?: string}
     */
    public static function present(Facets $facets, array $criteria = [], array $options = []): array
    {
        $maxVisibleAttributes = isset($options['maxVisibleAttributes']) ? (int) $options['maxVisibleAttributes'] : (int) ($GLOBALS['TL_CONFIG']['merconis_filter_maxVisibleAttributes'] ?? 12);
        $defaultMaxValuesPerAttribute = isset($options['defaultMaxValuesPerAttribute']) ? (int) $options['defaultMaxValuesPerAttribute'] : (int) ($GLOBALS['TL_CONFIG']['merconis_filter_maxValuesPerAttribute'] ?? 10);
        $pinnedAliases = isset($options['pinnedAliases']) && is_array($options['pinnedAliases']) ? $options['pinnedAliases'] : (array) ($GLOBALS['TL_CONFIG']['merconis_filter_pinnedAliases'] ?? []);

        $language = $options['language'] ?? null;
        if ($language === null) {
            global $objPage;
            $language = $objPage->language ?? ls_shop_languageHelper::getFallbackLanguage();
        }

        // Read published field definitions
        $fieldMeta = self::fetchPublishedFieldMeta($language);
        $allowedAttributeIds = array_keys(array_filter($fieldMeta, function ($row) {
            return ($row['dataSource'] ?? '') === 'attribute';
        }));

        // Normalize facet maps into per-attribute value counts (unfiltered + filtered)
        $unfiltered = $facets->getUnfilteredFacets();
        $filtered = $facets->getFilteredFacets();
        $perAttr = self::aggregatePerAttribute($unfiltered, $filtered);

        // Keep only attributes that have published fields
        $perAttr = array_intersect_key($perAttr, array_fill_keys($allowedAttributeIds, true));

        // Compute relevance per attribute (sum of total counts)
        $relevance = [];
        foreach ($perAttr as $attrId => $values) {
            $sum = 0;
            foreach ($values as $v) { $sum += (int) ($v['total_product_count'] ?? 0); }
            $relevance[$attrId] = $sum;
        }

        // Determine selected attributes from criteria
        $selected = self::extractSelectedAttributeIds($criteria);

        // Sort attributes
        $sortable = [];
        foreach ($perAttr as $attrId => $values) {
            $meta = $fieldMeta[$attrId] ?? [];
            $sortable[] = [
                'attribute_id' => (int) $attrId,
                'title' => (string) ($meta['title'] ?? ''),
                'alias' => (string) ($meta['alias'] ?? ''),
                'fieldId' => isset($meta['fieldId']) ? (int) $meta['fieldId'] : null,
                'priority' => (int) ($meta['priority'] ?? 0),
                'numItemsInReducedMode' => isset($meta['numItemsInReducedMode']) ? (int) $meta['numItemsInReducedMode'] : 0,
                'relevance' => (int) ($relevance[$attrId] ?? 0),
                'isSelected' => in_array((int) $attrId, $selected, true),
                'isPinned' => in_array((string) ($meta['alias'] ?? ''), $pinnedAliases, true),
                'values' => $values,
            ];
        }

        usort($sortable, function ($a, $b) {
            // Selected first
            if ($a['isSelected'] xor $b['isSelected']) { return $a['isSelected'] ? -1 : 1; }
            // Pinned next
            if ($a['isPinned'] xor $b['isPinned']) { return $a['isPinned'] ? -1 : 1; }
            // Higher priority first
            if ($a['priority'] !== $b['priority']) { return $b['priority'] <=> $a['priority']; }
            // Higher relevance first
            if ($a['relevance'] !== $b['relevance']) { return $b['relevance'] <=> $a['relevance']; }
            // Title A–Z
            return strcmp($a['title'], $b['title']);
        });

        // Build attribute outputs with value sorting and capping
        $visible = [];
        $hidden = [];
        $kept = 0;
        foreach ($sortable as $attr) {
            $maxValues = $attr['numItemsInReducedMode'] > 0 ? $attr['numItemsInReducedMode'] : $defaultMaxValuesPerAttribute;
            $valuesOut = self::sortAndCapValues(
                $attr['attribute_id'],
                $attr['values'],
                $language,
                $maxValues,
                isset($attr['fieldId']) ? (int) $attr['fieldId'] : null,
                (string) ($attr['alias'] ?? '')
            );
            $outItem = [
                'attribute_id' => $attr['attribute_id'],
                'title' => $attr['title'],
                'alias' => $attr['alias'],
                'priority' => $attr['priority'],
                'numItemsInReducedMode' => $maxValues,
                'values' => $valuesOut,
            ];

            if ($attr['isSelected'] || $attr['isPinned'] || $kept < $maxVisibleAttributes) {
                $visible[] = $outItem;
                if (!$attr['isSelected'] && !$attr['isPinned']) { $kept++; }
            } else {
                $hidden[] = $outItem;
            }
        }

        // Producers
        $producerFieldPublished = self::producerFieldPublished();
        $visibleProducers = [];
        $hiddenProducers = [];
        if ($producerFieldPublished) {
            $producerFieldInfo = self::fetchProducerFieldInfo($language);
            $producerTitle = $producerFieldInfo['title'] ?? null;
            $producerAlias = $producerFieldInfo['alias'] ?? '';
            $producerFieldId = $producerFieldInfo['id'] ?? null;
            [$producersUnf, $producersFil] = self::aggregateProducers($facets->getUnfilteredFacets(), $facets->getFilteredFacets());
            $selectedProducers = self::extractSelectedProducers($criteria);
            $curated = self::fetchCuratedProducers();
            $combined = self::mergeCuratedAndDiscoveredProducers($curated, array_keys($producersUnf));
            // attach counts
            $items = [];
            foreach ($combined as $p) {
                $rawCode = $p['name'];
                $translated = self::callTranslationHooks($rawCode, [
                    'dataSource' => 'producer',
                    'filterFieldId' => $producerFieldId,
                    'filterFieldAlias' => $producerAlias,
                    'language' => $language
                ]);
                $label = $translated ?? $rawCode;
                $items[] = [
                    'producer' => $p['name'],
                    'label' => $label,
                    'important' => $p['important'],
                    'total_product_count' => (int) ($producersUnf[strtolower($p['name'])] ?? 0),
                    'filtered_product_count' => (int) ($producersFil[strtolower($p['name'])] ?? 0),
                    'isSelected' => in_array($p['name'], $selectedProducers, true)
                ];
            }
            // sort: selected first, important next, filtered desc, total desc, title
            usort($items, function ($a, $b) {
                if ($a['isSelected'] xor $b['isSelected']) return $a['isSelected'] ? -1 : 1;
                if ($a['important'] xor $b['important']) return $a['important'] ? -1 : 1;
                if ($a['filtered_product_count'] !== $b['filtered_product_count']) return $b['filtered_product_count'] <=> $a['filtered_product_count'];
                if ($a['total_product_count'] !== $b['total_product_count']) return $b['total_product_count'] <=> $a['total_product_count'];
                return strcmp($a['label'] ?? $a['producer'], $b['label'] ?? $b['producer']);
            });
            $cap = $defaultMaxValuesPerAttribute;
            $visibleProducers = $cap > 0 && count($items) > $cap ? array_slice($items, 0, $cap) : $items;
            $hiddenProducers = $cap > 0 && count($items) > $cap ? array_slice($items, $cap) : [];
        } else {
            $producerTitle = null;
        }

        return [
            'visibleAttributes' => $visible,
            'hiddenAttributes' => $hidden,
            'visibleProducers' => $visibleProducers,
            'hiddenProducers' => $hiddenProducers,
            'producerTitle' => $producerTitle,
        ];
    }

    private static function fetchPublishedFieldMeta(string $language): array
    {
        /** @var \PageModel $objPage */
        $meta = [];
        $dbres = Database::getInstance()
            ->prepare("SELECT id, dataSource, sourceAttribute, alias, priority, numItemsInReducedMode FROM tl_ls_shop_filter_fields WHERE published = '1'")
            ->execute();
        while ($dbres->next()) {
            $row = $dbres->row();
            $attrId = (int) $row['sourceAttribute'];
            if ($row['dataSource'] === 'attribute' && $attrId > 0) {
                $title = ls_shop_languageHelper::getMultiLanguage($attrId, 'tl_ls_shop_attributes', array('title'), array($language));
                $meta[$attrId] = [
                    'dataSource' => 'attribute',
                    'alias' => (string) $row['alias'],
                    'fieldId' => (int) $row['id'],
                    'priority' => (int) $row['priority'],
                    'numItemsInReducedMode' => (int) $row['numItemsInReducedMode'],
                    'title' => (string) $title,
                ];
            }
        }
        return $meta;
    }

    private static function producerFieldPublished(): bool
    {
        $dbres = Database::getInstance()
            ->prepare("SELECT COUNT(*) AS cnt FROM tl_ls_shop_filter_fields WHERE published = '1' AND dataSource = 'producer'")
            ->execute();
        return ((int) ($dbres->cnt ?? 0)) > 0;
    }

    private static function fetchProducerFieldInfo(string $language): array
    {
        $dbres = Database::getInstance()
            ->prepare("SELECT id, alias FROM tl_ls_shop_filter_fields WHERE published = '1' AND dataSource = 'producer' ORDER BY priority DESC, id ASC")
            ->limit(1)
            ->execute();
        if (!$dbres->numRows) {
            return [];
        }
        $id = (int) $dbres->first()->id;
        $alias = (string) $dbres->first()->alias;
        $title = ls_shop_languageHelper::getMultiLanguage($id, 'tl_ls_shop_filter_fields', array('title'), array($language));
        return ['id' => $id, 'alias' => $alias, 'title' => (string) $title];
    }

    private static function callTranslationHooks(string $rawValue, array $context): ?string
    {
        if (!isset($GLOBALS['MERCONIS_HOOKS']['productSearchTranslateFilterValue']) || !is_array($GLOBALS['MERCONIS_HOOKS']['productSearchTranslateFilterValue'])) {
            return null;
        }
        foreach ($GLOBALS['MERCONIS_HOOKS']['productSearchTranslateFilterValue'] as $mccb) {
            try {
                $obj = System::importStatic($mccb[0]);
                $res = $obj->{$mccb[1]}($rawValue, $context);
                if (is_string($res) && $res !== '') {
                    return $res;
                }
            } catch (\Throwable $e) {
            }
        }
        return null;
    }

    /**
     * Convert facet maps to per-attribute lists of values with counts.
     * Accepts both keyed ("attr:value" => map) and list forms.
     * @return array<int, array<int, array{value_id:int,total_product_count:int,filtered_product_count:int}>>
     */
    private static function aggregatePerAttribute($unfiltered, $filtered): array
    {
        $uf = self::normalizeFacetMap($unfiltered);
        $ff = self::normalizeFacetMap($filtered);
        $perAttr = [];
        foreach ($uf as $pairKey => $data) {
            [$a, $v] = self::splitPairKey($pairKey);
            if ($a === null || $v === null) { continue; }
            if (!isset($perAttr[$a])) { $perAttr[$a] = []; }
            $perAttr[$a][$v] = [
                'value_id' => $v,
                'total_product_count' => (int) ($data['product_count'] ?? 0),
                'filtered_product_count' => 0,
            ];
        }
        foreach ($ff as $pairKey => $data) {
            [$a, $v] = self::splitPairKey($pairKey);
            if ($a === null || $v === null) { continue; }
            if (!isset($perAttr[$a][$v])) {
                $perAttr[$a][$v] = [
                    'value_id' => $v,
                    'total_product_count' => 0,
                    'filtered_product_count' => 0,
                ];
            }
            $perAttr[$a][$v]['filtered_product_count'] = (int) ($data['product_count'] ?? 0);
        }
        return $perAttr;
    }

    private static function normalizeFacetMap($map): array
    {
        if (!is_array($map)) { return []; }
        // If it is keyed by "attr:value" already, return as-is
        $firstKey = array_key_first($map);
        if ($firstKey !== null && is_string($firstKey) && strpos($firstKey, ':') !== false) {
            return $map;
        }
        // Otherwise, build keys
        $out = [];
        foreach ($map as $entry) {
            if (!is_array($entry)) { continue; }
            $a = isset($entry['attribute_id']) ? (int) $entry['attribute_id'] : null;
            $v = isset($entry['value_id']) ? (int) $entry['value_id'] : null;
            if ($a === null || $v === null) { continue; }
            $out[$a . ':' . $v] = [
                'product_count' => (int) ($entry['product_count'] ?? 0)
            ];
        }
        return $out;
    }

    private static function splitPairKey(string $key): array
    {
        $pos = strpos($key, ':');
        if ($pos === false) { return [null, null]; }
        return [ (int) substr($key, 0, $pos), (int) substr($key, $pos + 1) ];
    }

    private static function aggregateProducers($unfiltered, $filtered): array
    {
        $uf = [];
        if (is_array($unfiltered)) {
            foreach ($unfiltered as $entry) {
                if (is_array($entry) && isset($entry['producer'])) {
                    $uf[strtolower((string)$entry['producer'])] = (int) ($entry['product_count'] ?? 0);
                }
            }
        }
        $ff = [];
        if (is_array($filtered)) {
            foreach ($filtered as $entry) {
                if (is_array($entry) && isset($entry['producer'])) {
                    $ff[strtolower((string)$entry['producer'])] = (int) ($entry['product_count'] ?? 0);
                }
            }
        }
        return [$uf, $ff];
    }

    private static function extractSelectedProducers(array $criteria): array
    {
        $list = [];
        if (isset($criteria['producers']) && is_array($criteria['producers'])) {
            foreach ($criteria['producers'] as $p) {
                $p = trim((string) $p);
                if ($p !== '') { $list[] = $p; }
            }
        }
        return array_values(array_unique($list));
    }

    private static function fetchCuratedProducers(): array
    {
        $rows = Database::getInstance()
            ->prepare("SELECT f.id FROM tl_ls_shop_filter_fields f WHERE f.published = '1' AND f.dataSource = 'producer'")
            ->execute()
            ->fetchAllAssoc();
        if (!is_array($rows) || !count($rows)) { return []; }
        $ids = array_map(static fn($r) => (int) $r['id'], $rows);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $dbres = Database::getInstance()
            ->prepare("SELECT pid, filterValue, importantFieldValue FROM tl_ls_shop_filter_field_values WHERE pid IN ($placeholders) ORDER BY sorting ASC")
            ->execute(...$ids);
        $curated = [];
        while ($dbres->next()) {
            $name = (string) $dbres->filterValue;
            if ($name === '') continue;
            $curated[strtolower($name)] = ['name' => $name, 'important' => (bool) $dbres->importantFieldValue];
        }
        return array_values($curated);
    }

    private static function mergeCuratedAndDiscoveredProducers(array $curated, array $discovered): array
    {
        $seen = [];
        $out = [];
        foreach ($curated as $c) {
            $out[] = ['name' => $c['name'], 'important' => (bool) ($c['important'] ?? false)];
            $seen[strtolower($c['name'])] = true;
        }
        foreach ($discovered as $name) {
            $ln = strtolower((string)$name);
            if (isset($seen[$ln])) continue;
            $out[] = ['name' => (string) $name, 'important' => false];
        }
        return $out;
    }

    private static function extractSelectedAttributeIds(array $criteria): array
    {
        $out = [];
        if (!isset($criteria['attributes'])) { return $out; }
        $attrs = $criteria['attributes'];
        // Form 1: list of {attribute_id, value_id}
        if (isset($attrs[0]) && is_array($attrs[0]) && array_key_exists('attribute_id', $attrs[0])) {
            foreach ($attrs as $f) { $out[] = (int) $f['attribute_id']; }
            return array_values(array_unique($out));
        }
        // Form 2: map attribute_id => [value_ids]
        if (is_array($attrs)) {
            foreach ($attrs as $aid => $_) { $out[] = (int) $aid; }
        }
        return array_values(array_unique($out));
    }

    /**
     * Sort values by filtered desc, total desc, title A–Z and cap to max.
     */
    private static function sortAndCapValues(int $attributeId, array $values, string $language, int $max, ?int $filterFieldId = null, ?string $filterFieldAlias = ''): array
    {
        // Attach titles
        $enriched = [];
        foreach ($values as $vid => $info) {
            $rawTitle = ls_shop_languageHelper::getMultiLanguage($vid, 'tl_ls_shop_attribute_values', array('title'), array($language));
            $translated = self::callTranslationHooks((string) $rawTitle, [
                'dataSource' => 'attribute',
                'filterFieldId' => $filterFieldId,
                'filterFieldAlias' => (string) $filterFieldAlias,
                'language' => $language,
                'attributeId' => (int) $attributeId,
                'valueId' => (int) $vid,
            ]);
            $title = $translated ?? (string) $rawTitle;
            $enriched[] = [
                'value_id' => (int) $vid,
                'title' => (string) $title,
                'total_product_count' => (int) ($info['total_product_count'] ?? 0),
                'filtered_product_count' => (int) ($info['filtered_product_count'] ?? 0),
            ];
        }

        usort($enriched, function ($a, $b) {
            if ($a['filtered_product_count'] !== $b['filtered_product_count']) { return $b['filtered_product_count'] <=> $a['filtered_product_count']; }
            if ($a['total_product_count'] !== $b['total_product_count']) { return $b['total_product_count'] <=> $a['total_product_count']; }
            return strcmp($a['title'], $b['title']);
        });

        if ($max > 0 && count($enriched) > $max) {
            return array_slice($enriched, 0, $max);
        }
        return $enriched;
    }
}


