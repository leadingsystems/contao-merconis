<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Index\Product;

use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSearchInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Search implements CommonInterface, IndexSearchInterface
{
    use AdapterCommonTrait;

    private Client $client;
    private string $indexName = 'products';

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function search(Adapter &$productSearchAdapter): array
    {
        // 1. Supported search criteria and how they are mapped/queried in ES
        $criteriaMap = [
            'id' => [
                'esField' => 'id',
                'queryType' => 'terms',      // always array condition
            ],
            'pages' => [
                'esField' => 'pages',
                'queryType' => 'terms',      // always array condition
            ],
            'lsShopProductCode' => [
                'esField' => 'product_code',
                'queryType' => 'term',       // exact (keyword)
            ],
            'lsShopProductProducer' => [
                'esField' => 'producer',
                'queryType' => 'term',       // exact (keyword)
            ],
            'shortDescription' => [
                'esField' => 'short_description',
                'queryType' => 'match',      // analyzed (text)
            ],
            'title' => [
                'esField' => 'title',
                'queryType' => 'match',      // analyzed (text)
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
                'queryType' => 'boolean',    // handled as boolean
            ],
            'lsShopProductIsNew' => [
                'esField' => 'is_new',
                'queryType' => 'boolean',
            ],
            'lsShopProductIsOnSale' => [
                'esField' => 'is_sale',
                'queryType' => 'boolean',
            ],
            // Special fulltext search across multiple fields:
            'fulltext' => [
                'queryType' => 'multi_match',
                'esFields' => [
                    'title^3',
                    'keywords^2',
                    'short_description^2',
                    'description',
                    'product_code^2',
                    'producer^2'
                ]
            ],
            'attributes' => [
                'queryType' => 'product_and_variant_attributes'
            ],
        ];

        $criteria = $productSearchAdapter->getSearchCriteria();

        // --- Split out attribute filters for the two queries ---
        // Prepare base criteria for facets query (i.e. no 'attributes')
        $baseCriteria = $criteria;
        if (isset($baseCriteria['attributes'])) {
            unset($baseCriteria['attributes']);
        }

        $size = 1000;

        // ------ 1. Query for FACETS ------
        $baseQuery = $this->buildQueryForCriteria($baseCriteria, $criteriaMap);

        // -- AGGREGATION DEFINITION
        $aggs = [
            'attributes' => [
                'nested' => [
                    'path' => 'attributes'
                ],
                'aggs' => [
                    'attribute_ids' => [
                        'terms' => ['field' => 'attributes.attribute_id', 'size' => 1000],
                        'aggs' => [
                            'value_ids' => [
                                'terms' => ['field' => 'attributes.value_id', 'size' => 1000]
                            ]
                        ]
                    ]
                ]
            ],
            'variant_attributes' => [
                'nested' => [
                    'path' => 'variants.attributes'
                ],
                'aggs' => [
                    'attribute_ids' => [
                        'terms' => ['field' => 'variants.attributes.attribute_id', 'size' => 1000],
                        'aggs' => [
                            'value_ids' => [
                                'terms' => ['field' => 'variants.attributes.value_id', 'size' => 1000]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $facetAttributePairs = [];
        try {
            $facetParams = [
                'index' => $this->indexName,
                'body' => [
                    'size' => 0, // Only aggregations!
                    'query' => $baseQuery,
                    'aggs' => $aggs,
                ]
            ];
            $facetResponse = $this->client->elasticsearchClient->search($facetParams);

            // Collect facets
            // Product attributes
            if (isset($facetResponse['aggregations']['attributes']['attribute_ids']['buckets'])) {
                foreach ($facetResponse['aggregations']['attributes']['attribute_ids']['buckets'] as $attrBucket) {
                    $attributeId = $attrBucket['key'];
                    foreach ($attrBucket['value_ids']['buckets'] as $valBucket) {
                        $valueId = $valBucket['key'];
                        $facetAttributePairs[] = [
                            'location' => 'product',
                            'attribute_id' => $attributeId,
                            'value_id' => $valueId,
                            'doc_count' => $valBucket['doc_count']
                        ];
                    }
                }
            }
            // Variant attributes
            if (isset($facetResponse['aggregations']['variant_attributes']['attribute_ids']['buckets'])) {
                foreach ($facetResponse['aggregations']['variant_attributes']['attribute_ids']['buckets'] as $attrBucket) {
                    $attributeId = $attrBucket['key'];
                    foreach ($attrBucket['value_ids']['buckets'] as $valBucket) {
                        $valueId = $valBucket['key'];
                        $facetAttributePairs[] = [
                            'location' => 'variant',
                            'attribute_id' => $attributeId,
                            'value_id' => $valueId,
                            'doc_count' => $valBucket['doc_count']
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $facetAttributePairs = [];
        }

        // ------ 2. Query for ACTUAL RESULTS ------
        $mainQuery = $this->buildQueryForCriteria($criteria, $criteriaMap);

        // Sorting support (unchanged)
        $sort = [];
        $sortingCriteria = $productSearchAdapter->getSortingCriteria();
        foreach ($sortingCriteria as $sortingRule) {
            $field = $sortingRule['field'] ?? null;
            $direction = strtolower($sortingRule['direction'] ?? 'ASC');
            if ($field === 'priority') {
                $sort[] = ['_score' => ['order' => $direction]];
                continue;
            }
            if (isset($criteriaMap[$field])) {
                $esField = $criteriaMap[$field]['esField'];
                $queryType = $criteriaMap[$field]['queryType'];
                if ($queryType === 'match') {
                    $esField = $esField . '.raw';
                }
                $sort[] = [$esField => ['order' => $direction]];
            }
        }

        $productResultIds = [];
        try {
            $params = [
                'index' => $this->indexName,
                'scroll' => '2m',
                'body' => [
                    'size' => $size,
                    'query' => $mainQuery,
                    '_source' => ['id'],
                ]
            ];
            if (!empty($sort)) {
                $params['body']['sort'] = $sort;
            }

            $response = $this->client->elasticsearchClient->search($params);
            do {
                if (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
                    foreach ($response['hits']['hits'] as $hit) {
                        $productId = $hit['_source']['id'];
                        $isProductMatch = true;
                        $matchingVariantIds = [];
                        $matchingVariantCount = 0;
                        if (isset($hit['inner_hits']['matching_variants']['hits']['hits']) && count($hit['inner_hits']['matching_variants']['hits']['hits']) > 0) {
                            $isProductMatch = false;
                            foreach ($hit['inner_hits']['matching_variants']['hits']['hits'] as $variantHit) {
                                $variantSource = $variantHit['_source'] ?? [];
                                if (isset($variantSource['id'])) {
                                    $matchingVariantIds[] = $variantSource['id'];
                                }
                            }
                            $matchingVariantCount = count($matchingVariantIds);
                        }
                        $productResultIds[] = [
                            'id' => $productId,
                            'match_type' => $isProductMatch ? 'product' : 'variant',
                            'matching_variant_ids' => $matchingVariantIds,
                            'matching_variant_count' => $matchingVariantCount,
                        ];
                    }
                }
                $scrollId = $response['_scroll_id'] ?? null;
                $numHits = count($response['hits']['hits']);
                if ($scrollId && $numHits > 0) {
                    $response = $this->client->elasticsearchClient->scroll([
                        'scroll_id' => $scrollId,
                        'scroll' => '2m'
                    ]);
                } else {
                    break;
                }
            } while (true);
            if (isset($scrollId)) {
                $this->client->elasticsearchClient->clearScroll(['scroll_id' => $scrollId]);
            }

            $fullResultInfo = [
                'ids' => array_column($productResultIds, 'id'),
                'results' => $productResultIds,
                'facets' => $facetAttributePairs
            ];
            return $fullResultInfo['ids'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ---------- Helper function below ----------

    /**
     * Given search criteria, builds the full ES query (bool/must/should etc.)
     */
    private function buildQueryForCriteria(array $criteria, array $criteriaMap): array
    {
        $must = [];
        $productAttrFilters = [];
        $variantAttrFilters = [];
        foreach ($criteria as $criterion => $value) {
            if (!isset($criteriaMap[$criterion]) || $value === '' || $value === null) {
                continue; // Ignore unmapped or empty criteria
            }

            $map = $criteriaMap[$criterion];

            switch ($map['queryType']) {
                case 'multi_match':
                    // Special handling for "fulltext"
                    $must[] = [
                        'multi_match' => [
                            'query' => $value,
                            'fields' => $map['esFields'],
                            'type' => 'best_fields'
                        ]
                    ];
                    break;
                case 'terms':
                    // Accept array or scalar (wrap scalar)
                    $values = is_array($value) ? $value : [$value];
                    $must[] = [
                        'terms' => [
                            $map['esField'] => $values
                        ]
                    ];
                    break;
                case 'boolean':
                    // Normalize MySQL-like booleans ('1', 1, true) to ES booleans
                    $must[] = [
                        'term' => [
                            $map['esField'] => ($value === '1' || $value === 1 || $value === true)
                        ]
                    ];
                    break;
                case 'match':
                    // If value is only wildcard(s), skip (would match everything)
                    if (is_string($value) && preg_replace('/[%*]/', '', $value) === '') {
                        break;
                    }
                    // If value contains wildcards, use a wildcard query instead of match
                    if (is_string($value) && (strpos($value, '%') !== false || strpos($value, '*') !== false)) {
                        $pattern = str_replace(['%', '*'], '*', $value);
                        $must[] = [
                            'wildcard' => [
                                $map['esField'] . '.raw' => [
                                    'value' => $pattern,
                                    'case_insensitive' => true
                                ]
                            ]
                        ];
                    } else {
                        // Natural language search on analyzed field
                        $must[] = [
                            'match' => [$map['esField'] => $value]
                        ];
                    }
                    break;
                case 'term':
                    $must[] = [
                        'term' => [
                            $map['esField'] => $value
                        ]
                    ];
                    break;
                case 'product_and_variant_attributes':
                    // expects array of ['attribute_id'=>..., 'value_id'=>...]
                    foreach ($value as $filter) {
                        if (!isset($filter['attribute_id']) || !isset($filter['value_id'])) continue;
                        // Product-level 'must' (all present)
                        $productAttrFilters[] = [
                            'nested' => [
                                'path' => 'attributes',
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'term' => [ 'attributes.attribute_id' => $filter['attribute_id'] ] ],
                                            [ 'term' => [ 'attributes.value_id' => $filter['value_id'] ] ]
                                        ]
                                    ]
                                ]
                            ]
                        ];
                        // Variant-level ('must' all in same variant)
                        $variantAttrFilters[] = [
                            'nested' => [
                                'path' => 'variants.attributes',
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'term' => [ 'variants.attributes.attribute_id' => $filter['attribute_id'] ] ],
                                            [ 'term' => [ 'variants.attributes.value_id' => $filter['value_id'] ] ]
                                        ]
                                    ]
                                ]
                            ]
                        ];
                    }
                    break;
                default:
                    // Unknown or unsupported query type (shouldn't happen in strict mapping)
                    break;
            }
        }

        // ---- Attribute filter handling: build query to match product if EITHER product attributes OR a variant matches ----
        $attributesMatchShould = [];
        if (!empty($productAttrFilters)) {
            // Product matches if ALL attribute filters match directly (i.e., "must" all in one bool)
            $attributesMatchShould[] = [
                'bool' => ['must' => $productAttrFilters]
            ];
        }
        if (!empty($variantAttrFilters)) {
            // At least one VARIANT must have ALL attribute filters -> do a nested query with inner_hits
            $attributesMatchShould[] = [
                'nested' => [
                    'path' => 'variants',
                    'query' => [
                        'bool' => [
                            'must' => $variantAttrFilters
                        ]
                    ],
                    'inner_hits' => [
                        'name' => 'matching_variants',
                        'size' => 100,
                        '_source' => ['id', 'attributes']
                    ]
                ]
            ];
        }

        $query = [];

        if (!empty($attributesMatchShould)) {
            $query = [
                'bool' => [
                    'must' => $must,
                    'should' => $attributesMatchShould,
                    'minimum_should_match' => 1
                ]
            ];
        } else {
            $query = [
                'bool' => [
                    'must' => $must
                ]
            ];
        }

        return $query;
    }
}