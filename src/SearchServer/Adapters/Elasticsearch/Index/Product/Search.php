<?php
namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Index\Product;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\ProductSearch\Facets;
use LeadingSystems\MerconisBundle\ProductSearch\SearchResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSearchInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Search implements CommonInterface, IndexSearchInterface
{
    use AdapterCommonTrait;

    private Client $client;
    private string $indexName = 'products';
    private const DEFAULT_SIZE = 1000;
    private const SCROLL_TIMEOUT = '2m';

    protected string $language = 'de';

    private array $criteriaMap = [
        'id' => [
            'esField' => 'id',
            'queryType' => 'terms',
        ],
        'pages' => [
            'esField' => 'pages',
            'queryType' => 'terms',
        ],
        'lsShopProductCode' => [
            'esField' => 'product_code',
            'queryType' => 'term',
        ],
        'lsShopProductProducer' => [
            'esField' => 'producer',
            'queryType' => 'term',
        ],
        'shortDescription' => [
            'esField' => 'short_description',
            'queryType' => 'match',
        ],
        'title' => [
            'esField' => 'title',
            'queryType' => 'match',
        ],
        'keywords' => [
            'esField' => 'keywords',
            'queryType' => 'match',
        ],
        'description' => [
            'esField' => 'description',
            'queryType' => 'match',
        ],
        'published' => [
            'esField' => 'is_published',
            'queryType' => 'boolean',
        ],
        'lsShopProductIsNew' => [
            'esField' => 'is_new',
            'queryType' => 'boolean',
        ],
        'lsShopProductIsOnSale' => [
            'esField' => 'is_sale',
            'queryType' => 'boolean',
        ],
        'fulltext' => [
            'queryType' => 'multi_match',
            'esFields' => [
                'title^3',
                'keywords^2',
                'short_description^2',
                'description',
                'product_code^2',
                'producer^2',
            ],
        ],
        'attributes' => [
            'queryType' => 'product_and_variant_attributes',
        ],
    ];

    private array $multiLangFields = [
        'title',
        'keywords',
        'short_description',
        'description'
    ];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function search(Adapter &$productSearchAdapter, string $language, bool $activateFacets = true, bool $activateMatchEstimates = true, bool $removeImpossibleOptions = true): SearchResult
    {
        $this->language = $language;

        $criteria = $this->prepareCriteria($productSearchAdapter->getSearchCriteria());

        // Get possibly reduced criteria and dismissed filters
        [$criteria, $dismissedFilters] = $this->dismissInvalidAttributeFilters($criteria);

        $runAggsOnMainQuery = $activateFacets && $activateMatchEstimates;

        $searchResultData = $this->getSearchResultsWithFilteredFacets($criteria, $productSearchAdapter, $runAggsOnMainQuery);

        $hasUnmatchedProducts = false;
        $numUnmatchedProducts = 0;
        $countWithAttributes = 0;
        $countWithoutAttributes = 0;

        if (!empty($criteria['attributes'])) {
            $baseCriteria = $this->prepareBaseCriteria($criteria);
            $countWithAttributes = $searchResultData['total_hits'];
            $countWithoutAttributes = $this->getProductCountForCriteria($baseCriteria);
            if ($countWithoutAttributes > $countWithAttributes) {
                $hasUnmatchedProducts = true;
                $numUnmatchedProducts = $countWithoutAttributes - $countWithAttributes;
            }
        }

        if (!$activateFacets) {
            $facetData = new Facets([], [], []);
        } else {
            $baseCriteria = $this->prepareBaseCriteria($criteria);
            if ($activateMatchEstimates) {
                $unfilteredFacets = $this->getFacets($baseCriteria, '', false);
                $filteredFacets = $searchResultData['filtered_facets'];
                $combined = $this->combineFacetData($unfilteredFacets, $filteredFacets, $dismissedFilters);
                $facetData = new Facets($unfilteredFacets, $filteredFacets, $combined);
            } else {
                // Keys-only mode: do not compute counts in Elasticsearch when match estimates are disabled
                $unfilteredFacets = $this->getFacets($baseCriteria, '', true);
                $filteredFacets = $unfilteredFacets;
                $combined = $this->combineFacetDataKeysOnly($unfilteredFacets, $dismissedFilters);
                $facetData = new Facets($unfilteredFacets, $filteredFacets, $combined);
            }
        }

        if ($removeImpossibleOptions && $activateMatchEstimates) {
            // Only remove options based on counts when match estimates are active
            $facetData = $this->removeImpossibleOptions($facetData);
        }

        return new SearchResult(
            $searchResultData['product_ids'],
            $facetData,
            $hasUnmatchedProducts,
            $numUnmatchedProducts,
            $countWithoutAttributes,
            $countWithAttributes
        );
    }

    /**
     * Remove facet options that are "impossible" for the current result set.
     *
     * Definition of "impossible":
     * - combined facet entry is marked as invalid (e.g., dismissed filters)
     * - OR the filtered_product_count is 0 (selecting it cannot yield any result)
     *
     * We compute the set of allowed attribute/value pairs based on combined facet information
     * and then filter unfiltered/filtered/combined representations consistently so the UI and
     * downstream logic do not see impossible choices.
     */
    private function removeImpossibleOptions(Facets $facetData): Facets
    {
        // Read the merged/combined view which contains availability flags and counts
        $combinedFacets = $facetData->getCombinedFacets();

        // Build a lookup of attribute_id:value_id pairs that are actually selectable
        // (not invalid and with a filtered count > 0)
        $allowedKeys = [];
        foreach ($combinedFacets as $entry) {
            $attributeId = $entry['attribute_id'] ?? null;
            $valueId = $entry['value_id'] ?? null;
            $isInvalid = $entry['is_invalid'] ?? false;
            $filteredCount = $entry['filtered_product_count'] ?? 0;
            if ($attributeId === null || $valueId === null) {
                continue;
            }
            if ($isInvalid) {
                continue;
            }
            if ($filteredCount <= 0) {
                continue;
            }
            $allowedKeys[$attributeId . ':' . $valueId] = true;
        }

        // Filter both raw facet maps to only keep allowed options.
        // These arrays are keyed as attribute_id:value_id (see processFacetResponse())
        $unfiltered = $facetData->getUnfilteredFacets();
        $filtered = $facetData->getFilteredFacets();

        $unfiltered = array_filter(
            $unfiltered,
            function ($_, $key) use ($allowedKeys) {
                return isset($allowedKeys[$key]);
            },
            ARRAY_FILTER_USE_BOTH
        );

        $filtered = array_filter(
            $filtered,
            function ($_, $key) use ($allowedKeys) {
                return isset($allowedKeys[$key]);
            },
            ARRAY_FILTER_USE_BOTH
        );

        // Also remove impossible entries from the combined list itself
        $combinedFiltered = array_values(array_filter($combinedFacets, function ($entry) use ($allowedKeys) {
            $key = ($entry['attribute_id'] ?? '') . ':' . ($entry['value_id'] ?? '');
            return isset($allowedKeys[$key]);
        }));

        // Return a brand new Facets instance with the pruned data sets
        return new Facets($unfiltered, $filtered, $combinedFiltered);
    }

    private function prepareCriteria(array $criteria): array
    {
        return $criteria;
    }

    private function prepareBaseCriteria(array $criteria): array
    {
        $baseCriteria = $criteria;
        unset($baseCriteria['attributes']);
        return $baseCriteria;
    }

    private function dismissInvalidAttributeFilters(array $criteria): array
    {
        $dismissed = [];
        if (empty($criteria['attributes'])) {
            return [$criteria, $dismissed];
        }

        // Get base query without attribute filters
        $baseCriteria = $this->prepareBaseCriteria($criteria);

        // Get available facets for base query
        $availableFacets = $this->getFacets($baseCriteria);

        // Build lookup table of "attribute_id:value_id"
        $availableKeys = [];
        foreach ($availableFacets as $facet) {
            $availableKeys[$facet['attribute_id'] . ':' . $facet['value_id']] = true;
        }

        // Filter attribute filters to only keep possible ones
        $criteria['attributes'] = array_filter($criteria['attributes'], function ($filter) use ($availableKeys, &$dismissed) {
            if (!isset($filter['attribute_id'], $filter['value_id'])) {
                return false;
            }
            $key = $filter['attribute_id'] . ':' . $filter['value_id'];
            if (!isset($availableKeys[$key])) {
                $dismissed[] = $filter;
                return false;
            }
            return true;
        });
        return [$criteria, $dismissed];
    }
    private function combineFacetData(array $unfilteredFacets, array $filteredFacets, array $dismissedFilters = []): array
    {
        $combined = [];
        $filteredLookup = [];
        foreach ($filteredFacets as $facet) {
            $key = $facet['attribute_id'] . '_' . $facet['value_id'];
            $filteredLookup[$key] = $facet['product_count'];
        }
        foreach ($unfilteredFacets as $facet) {
            $key = $facet['attribute_id'] . '_' . $facet['value_id'];
            $filteredCount = $filteredLookup[$key] ?? 0;
            $combined[] = [
                'attribute_id' => $facet['attribute_id'],
                'value_id' => $facet['value_id'],
                'total_product_count' => $facet['product_count'],
                'filtered_product_count' => $filteredCount,
                'is_available' => $filteredCount > 0,
                'is_filtered_out' => $filteredCount === 0 && $facet['product_count'] > 0,
                'is_invalid' => false
            ];
        }
        // Add dismissed filters as invalid entries
        foreach ($dismissedFilters as $filter) {
            $combined[] = [
                'attribute_id' => $filter['attribute_id'],
                'value_id' => $filter['value_id'],
                'total_product_count' => 0,
                'filtered_product_count' => 0,
                'is_available' => false,
                'is_filtered_out' => false,
                'is_invalid' => true
            ];
        }
        return $combined;
    }

    private function getSearchResultsWithFilteredFacets(array $criteria, Adapter $productSearchAdapter, bool $activateFacets): array
    {
        $mainQuery = $this->buildQueryForCriteria($criteria);
        $sort = $this->buildSortCriteria($productSearchAdapter->getSortingCriteria());
        $aggs = $activateFacets ? $this->buildAggregations($criteria, false) : [];
        try {
            $params = [
                'index' => $this->indexName,
                'scroll' => self::SCROLL_TIMEOUT,
                'body' => [
                    'size' => self::DEFAULT_SIZE,
                    'query' => $mainQuery,
                    '_source' => ['id'],
                    'track_total_hits' => true,
                ]
            ];
            if ($activateFacets) {
                $params['body']['aggs'] = $aggs;
            }
            if (!empty($sort)) {
                $params['body']['sort'] = $sort;
            }
            return $this->executeScrollSearchWithFacets($params, $criteria, $activateFacets);
        } catch (\Exception $e) {
            return [
                'product_ids' => ['error' => $e->getMessage()],
                'filtered_facets' => [],
                'total_hits' => 0,
            ];
        }
    }

    private function executeScrollSearchWithFacets(array $params, array $criteria, bool $activateFacets): array
    {
        $productResultIds = [];
        $filteredFacets = [];
        $totalHits = 0;
        $response = $this->client->elasticsearchClient->search($params);
        $scrollId = null;
        $isFirstResponse = true;
        do {
            if ($isFirstResponse) {
                if ($activateFacets && isset($response['aggregations'])) {
                    $filteredFacets = $this->processFacetResponse($response, $criteria, false);
                }
                $totalHits = $response['hits']['total']['value'] ?? 0;
            }
            $isFirstResponse = false;
            if (!empty($response['hits']['hits'])) {
                foreach ($response['hits']['hits'] as $hit) {
                    $productResultIds[] = $this->processSearchHit($hit);
                }
            }
            $scrollId = $response['_scroll_id'] ?? null;
            if ($scrollId && !empty($response['hits']['hits'])) {
                $response = $this->client->elasticsearchClient->scroll([
                    'scroll_id' => $scrollId,
                    'scroll' => self::SCROLL_TIMEOUT
                ]);
            } else {
                break;
            }
        } while (true);
        if ($scrollId) {
            $this->client->elasticsearchClient->clearScroll(['scroll_id' => $scrollId]);
        }
        return [
            'product_ids' => array_column($productResultIds, 'id'),
            'filtered_facets' => $filteredFacets,
            'total_hits' => $totalHits,
        ];
    }

    private function getFacets(array $criteria, string $context = '', bool $keysOnly = false): array
    {
        $query = $this->buildQueryForCriteria($criteria);
        $aggs = $this->buildAggregations($criteria, $keysOnly);
        try {
            $facetParams = [
                'index' => $this->indexName,
                'body' => [
                    'size' => 0,
                    'query' => $query,
                    'aggs' => $aggs,
                ]
            ];
            $facetResponse = $this->client->elasticsearchClient->search($facetParams);
            return $this->processFacetResponse($facetResponse, $criteria, $keysOnly);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Executes a lightweight search to get only the total number of matching documents.
     */
    private function getProductCountForCriteria(array $criteria): int
    {
        try {
            $query = $this->buildQueryForCriteria($criteria);
            $params = [
                'index' => $this->indexName,
                'body' => [
                    'size' => 0,
                    'query' => $query,
                    'track_total_hits' => true
                ]
            ];
            $response = $this->client->elasticsearchClient->search($params);
            return $response['hits']['total']['value'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function buildAggregations(array $criteria = [], bool $keysOnly = false): array
    {
        $variantAttrFilters = [];
        if (!empty($criteria['attributes'])) {
            foreach ($criteria['attributes'] as $filter) {
                if (!isset($filter['attribute_id'], $filter['value_id'])) continue;
                $variantAttrFilters[] = [
                    'nested' => [
                        'path' => 'variants.attributes',
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['term' => ['variants.attributes.attribute_id' => $filter['attribute_id']]],
                                    ['term' => ['variants.attributes.value_id' => $filter['value_id']]]
                                ]
                            ]
                        ]
                    ]
                ];
            }
        }
        $variantCompositeAggregation = [
            'attrs' => array_merge(
                [
                    'composite' => [
                        'sources' => [
                            ['attribute_id' => ['terms' => ['field' => 'variants.attributes.attribute_id', 'missing_bucket' => true]]],
                            ['value_id' => ['terms' => ['field' => 'variants.attributes.value_id', 'missing_bucket' => true]]]
                        ],
                        'size' => self::DEFAULT_SIZE
                    ]
                ],
                $keysOnly ? [] : [
                    'aggs' => [
                        'back_to_product' => [
                            'reverse_nested' => new \stdClass(),
                            'aggs' => [
                                'product_ids' => ['cardinality' => ['field' => 'id']]
                            ]
                        ]
                    ]
                ]
            )
        ];
        $variantAggregation = empty($variantAttrFilters)
            ? ['nested' => ['path' => 'variants.attributes'], 'aggs' => $variantCompositeAggregation]
            : [
                'nested' => ['path' => 'variants'],
                'aggs' => [
                    'matching_variants_only' => [
                        'filter' => ['bool' => ['must' => $variantAttrFilters]],
                        'aggs' => [
                            'attributes_of_matching_variants' => [
                                'nested' => ['path' => 'variants.attributes'],
                                'aggs' => $variantCompositeAggregation
                            ]
                        ]
                    ]
                ]
            ];
        return [
            'product_attribute_pairs' => [
                'nested' => ['path' => 'attributes'],
                'aggs' => [
                    'attrs' => array_merge(
                        [
                            'composite' => [
                                'sources' => [
                                    ['attribute_id' => ['terms' => ['field' => 'attributes.attribute_id', 'missing_bucket' => true]]],
                                    ['value_id' => ['terms' => ['field' => 'attributes.value_id', 'missing_bucket' => true]]]
                                ],
                                'size' => self::DEFAULT_SIZE
                            ]
                        ],
                        $keysOnly ? [] : [
                            'aggs' => [
                                'to_product' => ['reverse_nested' => new \stdClass()],
                                'unique_products' => [
                                    'reverse_nested' => new \stdClass(),
                                    'aggs' => ['product_ids' => ['cardinality' => ['field' => 'id']]]
                                ]
                            ]
                        ]
                    )
                ]
            ],
            'variant_attribute_pairs' => $variantAggregation
        ];
    }


    private function combineFacetDataKeysOnly(array $unfilteredFacets, array $dismissedFilters = []): array
    {
        $combined = [];
        foreach ($unfilteredFacets as $facet) {
            $combined[] = [
                'attribute_id' => $facet['attribute_id'],
                'value_id' => $facet['value_id'],
                'total_product_count' => 0,
                'filtered_product_count' => 0,
                'is_available' => true,
                'is_filtered_out' => false,
                'is_invalid' => false
            ];
        }
        foreach ($dismissedFilters as $filter) {
            $combined[] = [
                'attribute_id' => $filter['attribute_id'],
                'value_id' => $filter['value_id'],
                'total_product_count' => 0,
                'filtered_product_count' => 0,
                'is_available' => false,
                'is_filtered_out' => false,
                'is_invalid' => true
            ];
        }
        return $combined;
    }

    private function processFacetResponse($facetResponse, array $criteria = [], bool $keysOnly = false): array
    {
        $productAttrBuckets = [];
        $after = null;
        do {
            $agg = $facetResponse['aggregations']['product_attribute_pairs']['attrs'];
            $productAttrBuckets = array_merge($productAttrBuckets, $agg['buckets']);
            $after = $agg['after_key'] ?? null;
            if ($after !== null) {
                $mainQuery = $this->buildQueryForCriteria($criteria);
                $aggs = $this->buildAggregations($criteria, $keysOnly);
                $aggs['product_attribute_pairs']['aggs']['attrs']['composite']['after'] = $after;
                $facetResponse = $this->client->elasticsearchClient->search([
                    'index' => $this->indexName,
                    'body' => ['size' => 0, 'query' => $mainQuery, 'aggs' => $aggs]
                ]);
            }
        } while ($after !== null);

        $facetResponse = is_array($facetResponse) ? $facetResponse : $facetResponse->asArray();
        $variantAttrBuckets = [];
        $after = null;
        $facetResponseVar = $facetResponse;
        do {
            $aggPath = $facetResponseVar['aggregations']['variant_attribute_pairs'];
            $agg = isset($aggPath['matching_variants_only'])
                ? $aggPath['matching_variants_only']['attributes_of_matching_variants']['attrs']
                : $aggPath['attrs'];
            $variantAttrBuckets = array_merge($variantAttrBuckets, $agg['buckets']);
            $after = $agg['after_key'] ?? null;
            if ($after !== null) {
                $mainQuery = $this->buildQueryForCriteria($criteria);
                $aggs = $this->buildAggregations($criteria, $keysOnly);
                if (isset($aggs['variant_attribute_pairs']['aggs']['matching_variants_only'])) {
                    $aggs['variant_attribute_pairs']['aggs']['matching_variants_only']['aggs']['attributes_of_matching_variants']['aggs']['attrs']['composite']['after'] = $after;
                } else {
                    $aggs['variant_attribute_pairs']['aggs']['attrs']['composite']['after'] = $after;
                }
                $facetResponseVar = $this->client->elasticsearchClient->search([
                    'index' => $this->indexName,
                    'body' => ['size' => 0, 'query' => $mainQuery, 'aggs' => $aggs]
                ]);
            }
        } while ($after !== null);

        $facetAttributePairs = [];
        foreach ($productAttrBuckets as $bucket) {
            $attributeId = $bucket['key']['attribute_id'];
            $valueId = $bucket['key']['value_id'];
            $product_count = $keysOnly ? 0 : ($bucket['unique_products']['product_ids']['value'] ?? 0);
            $facetAttributePairs[$attributeId . ':' . $valueId] = [
                'attribute_id' => $attributeId,
                'value_id' => $valueId,
                'product_count' => $product_count,
            ];
        }
        foreach ($variantAttrBuckets as $bucket) {
            $attributeId = $bucket['key']['attribute_id'];
            $valueId = $bucket['key']['value_id'];
            if ($keysOnly) {
                if (!isset($facetAttributePairs[$attributeId . ':' . $valueId])) {
                    $facetAttributePairs[$attributeId . ':' . $valueId] = [
                        'attribute_id' => $attributeId,
                        'value_id' => $valueId,
                        'product_count' => 0,
                    ];
                }
            } else {
                $product_count = $bucket['back_to_product']['product_ids']['value'] ?? 0;
                if (isset($facetAttributePairs[$attributeId . ':' . $valueId])) {
                    $facetAttributePairs[$attributeId . ':' . $valueId]['product_count'] += $product_count;
                } else {
                    $facetAttributePairs[$attributeId . ':' . $valueId] = [
                        'attribute_id' => $attributeId,
                        'value_id' => $valueId,
                        'product_count' => $product_count,
                    ];
                }
            }
        }
        return $facetAttributePairs;
    }

    private function buildSortCriteria(array $sortingCriteria): array
    {
        $sort = [];
        foreach ($sortingCriteria as $sortingRule) {
            $field = $sortingRule['field'] ?? null;
            $direction = strtolower($sortingRule['direction'] ?? 'ASC');
            if ($field === 'priority') {
                $sort[] = ['_score' => ['order' => $direction]];
                continue;
            }
            if (isset($this->criteriaMap[$field])) {
                $esField = $this->criteriaMap[$field]['esField'];
                $queryType = $this->criteriaMap[$field]['queryType'];
                if ($queryType === 'match') {
                    $esField .= '.' . $this->language . '.raw';
                }
                $sort[] = [$esField => ['order' => $direction]];
            }
        }
        return $sort;
    }

    private function processSearchHit(array $hit): array
    {
        $productId = $hit['_source']['id'];
        $isProductMatch = true;
        $matchingVariantIds = [];
        if (!empty($hit['inner_hits']['matching_variants']['hits']['hits'])) {
            $isProductMatch = false;
            foreach ($hit['inner_hits']['matching_variants']['hits']['hits'] as $variantHit) {
                $variantSource = $variantHit['_source'] ?? [];
                if (isset($variantSource['id'])) {
                    $matchingVariantIds[] = $variantSource['id'];
                }
            }
        }
        return [
            'id' => $productId,
            'match_type' => $isProductMatch ? 'product' : 'variant',
            'matching_variant_ids' => $matchingVariantIds,
            'matching_variant_count' => count($matchingVariantIds),
        ];
    }

    private function buildQueryForCriteria(array $criteria): array
    {
        $must = [];
        $productAttrFilters = [];
        $variantAttrFilters = [];
        foreach ($criteria as $criterion => $value) {
            if (!isset($this->criteriaMap[$criterion]) || $value === '' || $value === null) {
                continue;
            }
            $map = $this->criteriaMap[$criterion];
            $this->addQueryClause($map, $value, $must, $productAttrFilters, $variantAttrFilters);
        }
        return $this->buildFinalQuery($must, $productAttrFilters, $variantAttrFilters);
    }

    private function addQueryClause(array $map, $value, array &$must, array &$productAttrFilters, array &$variantAttrFilters): void
    {
        switch ($map['queryType']) {
            case 'multi_match':
                $fields = [];
                foreach ($map['esFields'] as $fieldSpec) {
                    preg_match('/^([a-z_]+)(\^(\d+))?$/i', $fieldSpec, $matches);
                    $fieldBase = $matches[1] ?? $fieldSpec;
                    $boost = isset($matches[3]) ? '^' . $matches[3] : '';
                    if (in_array($fieldBase, $this->multiLangFields, true)) {
                        $fields[] = $fieldBase . '.' . $this->language . $boost;
                    } else {
                        $fields[] = $fieldSpec;
                    }
                }
                $must[] = ['multi_match' => ['query' => $value, 'fields' => $fields, 'type' => 'best_fields']];
                break;
            case 'terms':
                $esField = $map['esField'];
                if (in_array($esField, $this->multiLangFields, true)) {
                    $esField .= '.' . $this->language;
                }
                $values = is_array($value) ? $value : [$value];
                $must[] = ['terms' => [$esField => $values]];
                break;
            case 'term':
                $esField = $map['esField'];
                if (in_array($esField, $this->multiLangFields, true)) {
                    $esField .= '.' . $this->language;
                }
                $must[] = ['term' => [$esField => $value]];
                break;
            case 'boolean':
                $must[] = ['term' => [$map['esField'] => ($value === '1' || $value === 1 || $value === true)]];
                break;
            case 'match':
                $esField = $map['esField'];
                if (in_array($esField, $this->multiLangFields, true)) {
                    $esField .= '.' . $this->language;
                }
                $this->addMatchQuery($esField, $value, $must);
                break;
            case 'product_and_variant_attributes':
                $this->addAttributeFilters($value, $productAttrFilters, $variantAttrFilters);
                break;
        }
    }

    private function addMatchQuery(string $esField, $value, array &$must): void
    {
        if (is_string($value) && preg_replace('/[%*]/', '', $value) === '') {
            return;
        }
        if (is_string($value) && (strpos($value, '%') !== false || strpos($value, '*') !== false)) {
            $pattern = str_replace(['%', '*'], '*', $value);
            $must[] = ['wildcard' => [$esField . '.raw' => ['value' => $pattern, 'case_insensitive' => true]]];
        } else {
            $must[] = ['match' => [$esField => $value]];
        }
    }

    private function addAttributeFilters($value, array &$productAttrFilters, array &$variantAttrFilters): void
    {
        foreach ($value as $filter) {
            if (!isset($filter['attribute_id'], $filter['value_id'])) continue;
            $productAttrFilters[] = [
                'nested' => [
                    'path' => 'attributes',
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['term' => ['attributes.attribute_id' => $filter['attribute_id']]],
                                ['term' => ['attributes.value_id' => $filter['value_id']]]
                            ]
                        ]
                    ]
                ]
            ];
            $variantAttrFilters[] = [
                'nested' => [
                    'path' => 'variants.attributes',
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['term' => ['variants.attributes.attribute_id' => $filter['attribute_id']]],
                                ['term' => ['variants.attributes.value_id' => $filter['value_id']]]
                            ]
                        ]
                    ]
                ]
            ];
        }
    }

    private function buildFinalQuery(array $must, array $productAttrFilters, array $variantAttrFilters): array
    {
        $attributesMatchShould = [];
        if (!empty($productAttrFilters)) {
            $attributesMatchShould[] = ['bool' => ['must' => $productAttrFilters]];
        }
        if (!empty($variantAttrFilters)) {
            $attributesMatchShould[] = [
                'nested' => [
                    'path' => 'variants',
                    'query' => ['bool' => ['must' => $variantAttrFilters]],
                    'inner_hits' => [
                        'name' => 'matching_variants',
                        'size' => 100,
                        '_source' => ['id', 'attributes']
                    ]
                ]
            ];
        }
        if (!empty($attributesMatchShould)) {
            return [
                'bool' => [
                    'must' => $must,
                    'should' => $attributesMatchShould,
                    'minimum_should_match' => 1
                ]
            ];
        }
        return ['bool' => ['must' => $must]];
    }
}