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

    private array $criteriaMap = [
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
                'producer^2',
            ],
        ],
        'attributes' => [
            'queryType' => 'product_and_variant_attributes',
        ],
    ];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
    public function search(Adapter &$productSearchAdapter, bool $activateFacets = true, bool $activateMatchEstimates = false): SearchResult
    {
        $criteria = $this->prepareCriteria($productSearchAdapter->getSearchCriteria());
        // Determine if we need to run aggregations on the main, filtered query.
        // This is only necessary if both facets and match estimates are active.
        $runAggsOnMainQuery = $activateFacets && $activateMatchEstimates;
        // Get primary search results (product IDs, total hit count, and optionally filtered facets)
        $searchResultData = $this->getSearchResultsWithFilteredFacets($criteria, $productSearchAdapter, $runAggsOnMainQuery);
        // Initialize unmatched product data
        $hasUnmatchedProducts = false;
        $numUnmatchedProducts = 0;
        $countWithAttributes = 0;
        $countWithoutAttributes = 0;
        // If attribute filters were used, we must check for a mismatch between
        // the count with and without the attribute filters.
        if (!empty($criteria['attributes'])) {
            $baseCriteria = $this->prepareBaseCriteria($criteria);
            $countWithAttributes = $searchResultData['total_hits'];
            $countWithoutAttributes = $this->getProductCountForCriteria($baseCriteria);
            if ($countWithoutAttributes > $countWithAttributes) {
                $hasUnmatchedProducts = true;
                $numUnmatchedProducts = $countWithoutAttributes - $countWithAttributes;
            }
        }
        // Prepare facet data if required
        if (!$activateFacets) {
            $facetData = new Facets([], [], []);
        } else {
            $baseCriteria = $this->prepareBaseCriteria($criteria);
        $unfilteredFacets = $this->getFacets($baseCriteria);
        // If match estimates are off, the "filtered" view is the same as the "unfiltered" view.
            $filteredFacets = $activateMatchEstimates ? $searchResultData['filtered_facets'] : $unfilteredFacets;
        $facetData = new Facets(
            $unfilteredFacets,
            $filteredFacets,
            $this->combineFacetData($unfilteredFacets, $filteredFacets)
        );
    }
        // Instantiate the final result object with all required data
        return new SearchResult(
            $searchResultData['product_ids'],
            $facetData,
            $hasUnmatchedProducts,
            $numUnmatchedProducts,
            $countWithoutAttributes,
            $countWithAttributes
        );
    }
    private function prepareCriteria(array $criteria): array
    {
        /* -->
         * Do me! This is only for tests. Remove afterwards!
         *
        $criteria['attributes'] = [
            ['attribute_id' => 1000000, 'value_id' => 1000012],
            ['attribute_id' => 2000000, 'value_id' => 2000012],
        ];
        /*
         * <--
         */
        return $criteria;
    }

    private function prepareBaseCriteria(array $criteria): array
    {
        $baseCriteria = $criteria;
        if (isset($baseCriteria['attributes'])) {
            unset($baseCriteria['attributes']);
        }
        return $baseCriteria;
    }

    /**
     * Combine unfiltered and filtered facet data to determine which options should be greyed out
     */
    private function combineFacetData(array $unfilteredFacets, array $filteredFacets): array
    {
        $combined = [];

        // Create a lookup array for filtered facets
        $filteredLookup = [];
        foreach ($filteredFacets as $facet) {
            $key = $facet['attribute_id'] . '_' . $facet['value_id'];
            $filteredLookup[$key] = $facet['product_count'];
        }

        // Process unfiltered facets and mark availability
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
            ];
        }

        return $combined;
    }
    private function getSearchResultsWithFilteredFacets(array $criteria, Adapter $productSearchAdapter, bool $activateFacets): array
    {
        $mainQuery = $this->buildQueryForCriteria($criteria);
        $sort = $this->buildSortCriteria($productSearchAdapter->getSortingCriteria());
        $aggs = $activateFacets ? $this->buildAggregations($criteria) : [];
        try {
            $params = [
                'index' => $this->indexName,
                'scroll' => self::SCROLL_TIMEOUT,
                'body' => [
                    'size' => self::DEFAULT_SIZE,
                    'query' => $mainQuery,
                    '_source' => ['id'],
                    'track_total_hits' => true, // Essential for getting accurate hit counts
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
            // Extract facets and total hit count only from the first response (they're the same across all scroll pages)
            if ($isFirstResponse) {
                if ($activateFacets && isset($response['aggregations'])) {
                $filteredFacets = $this->processFacetResponse($response, $criteria);
            }
                $totalHits = $response['hits']['total']['value'] ?? 0;
            }
            $isFirstResponse = false; // Ensure this is only checked once
            if (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
                foreach ($response['hits']['hits'] as $hit) {
                    $productResultIds[] = $this->processSearchHit($hit);
                }
            }

            $scrollId = $response['_scroll_id'] ?? null;
            $numHits = count($response['hits']['hits']);

            if ($scrollId && $numHits > 0) {
                $response = $this->client->elasticsearchClient->scroll([
                    'scroll_id' => $scrollId,
                    'scroll' => self::SCROLL_TIMEOUT
                ]);
            } else {
                break;
            }
        } while (true);

        if (isset($scrollId)) {
            $this->client->elasticsearchClient->clearScroll(['scroll_id' => $scrollId]);
        }

        return [
            'product_ids' => array_column($productResultIds, 'id'),
            'filtered_facets' => $filteredFacets,
            'total_hits' => $totalHits,
        ];
    }

    private function getFacets(array $criteria, string $context = ''): array
    {
        $query = $this->buildQueryForCriteria($criteria);
        $aggs = $this->buildAggregations($criteria);
        try {
            $facetParams = [
                'index' => $this->indexName,
                'body' => [
                    'size' => 0, // Only aggregations!
                    'query' => $query,
                    'aggs' => $aggs,
                ]
            ];
            $facetResponse = $this->client->elasticsearchClient->search($facetParams);
            return $this->processFacetResponse($facetResponse, $criteria);
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
                    'size' => 0, // We only need the count, not the hits
                    'query' => $query,
                    'track_total_hits' => true // Ensure we get an accurate total count
                ]
            ];
            $response = $this->client->elasticsearchClient->search($params);
            return $response['hits']['total']['value'] ?? 0;
        } catch (\Exception $e) {
            // In case of an error, return 0 as a safe fallback.
            // Consider logging the exception $e->getMessage() for debugging.
            return 0;
        }
    }
    /**
     * AGGREGATIONS FOR DISTINCT PRODUCT-LEVEL ATTRIBUTE/VALUE PAIRS (both nested product and variant attrs).
     * Uses composite aggregation to support pagination for >DEFAULT_SIZE buckets.
     */
    private function buildAggregations(array $criteria = []): array
    {
        $variantAttrFilters = [];
        if (!empty($criteria['attributes']) && is_array($criteria['attributes'])) {
            foreach ($criteria['attributes'] as $filter) {
                if (!isset($filter['attribute_id']) || !isset($filter['value_id'])) continue;
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
                    'attrs' => [
                        'composite' => [
                            'sources' => [
                                [
                            'attribute_id' => ['terms' => ['field' => 'variants.attributes.attribute_id', 'missing_bucket' => true]]
                                ],
                                [
                            'value_id' => ['terms' => ['field' => 'variants.attributes.value_id', 'missing_bucket' => true]]
                                ]
                            ],
                            'size' => self::DEFAULT_SIZE
                        ],
                        'aggs' => [
                    'back_to_product' => [
                                'reverse_nested' => new \stdClass(),
                                'aggs' => [
                                    'product_ids' => ['cardinality' => ['field' => 'id']]
                                ]
                            ]
                        ]
                    ]
        ];
        if (empty($variantAttrFilters)) {
            $variantAggregation = [
                'nested' => ['path' => 'variants.attributes'],
                'aggs' => $variantCompositeAggregation
            ];
        } else {
            $variantAggregation = [
                'nested' => ['path' => 'variants'],
                'aggs' => [
                    'matching_variants_only' => [
                        'filter' => [
                            'bool' => [
                                'must' => $variantAttrFilters
                ]
            ],
                        'aggs' => [
                            'attributes_of_matching_variants' => [
                'nested' => [ 'path' => 'variants.attributes' ],
                                'aggs' => $variantCompositeAggregation
                            ]
                        ]
                    ]
                ]
            ];
        }
        return [
            'product_attribute_pairs' => [
                'nested' => [
                    'path' => 'attributes'
                ],
                'aggs' => [
                    'attrs' => [
                        'composite' => [
                            'sources' => [
                                [
                                    'attribute_id' => [ 'terms' => [ 'field' => 'attributes.attribute_id', 'missing_bucket' => true ] ]
                                ],
                                [
                                    'value_id' => [ 'terms' => [ 'field' => 'attributes.value_id', 'missing_bucket' => true ] ]
                                ]
                            ],
                            'size' => self::DEFAULT_SIZE
                ],
                'aggs' => [
                            'to_product' => [
                                'reverse_nested' => new \stdClass(),
                            ],
                            'unique_products' => [
                                'reverse_nested' => new \stdClass(),
                        'aggs' => [
                                    'product_ids' => ['cardinality' => ['field' => 'id']]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'variant_attribute_pairs' => $variantAggregation
        ];
    }

    /**
     * Get ALL composite buckets for both product and variant attrs by paginating using the after_key logic.
     */
    private function processFacetResponse($facetResponse, array $criteria = []): array
    {
        // --- Composite pagination for product attributes
        $productAttrBuckets = [];
        $after = null;
        do {
            $agg = $facetResponse['aggregations']['product_attribute_pairs']['attrs'];
            $productAttrBuckets = array_merge($productAttrBuckets, $agg['buckets']);
            $after = $agg['after_key'] ?? null;

            if ($after !== null) {
                // fetch next page
                $aggs = $this->buildAggregations($criteria);
                $aggs['product_attribute_pairs']['aggs']['attrs']['composite']['after'] = $after;

                $params = [
                    'index' => $this->indexName,
                    'body' => [
                        'size' => 0,
                        'aggs' => $aggs,
                    ],
                ];
                $facetResponse = $this->client->elasticsearchClient->search($params);
            }
        } while ($after !== null);

        // --- Composite pagination for variant attributes
        $facetResponse = is_array($facetResponse) ? $facetResponse : $facetResponse->asArray();
        $variantAttrBuckets = [];
        $after = null;
        $facetResponseVar = $facetResponse;
        do {
            $aggPath = $facetResponseVar['aggregations']['variant_attribute_pairs'];
            if (isset($aggPath['matching_variants_only'])) {
                // This is the new, filtered aggregation structure
                $agg = $aggPath['matching_variants_only']['attributes_of_matching_variants']['attrs'];
            } else {
                // This is the old structure (used for unfiltered facets)
                $agg = $aggPath['attrs'];
            }
            $variantAttrBuckets = array_merge($variantAttrBuckets, $agg['buckets']);
            $after = $agg['after_key'] ?? null;

            if ($after !== null) {
                $aggs = $this->buildAggregations($criteria);
                // We need to set the 'after' key in the correct place, depending on the structure
                if (isset($aggs['variant_attribute_pairs']['aggs']['matching_variants_only'])) {
                    $aggs['variant_attribute_pairs']['aggs']['matching_variants_only']['aggs']['attributes_of_matching_variants']['aggs']['attrs']['composite']['after'] = $after;
                } else {
                $aggs['variant_attribute_pairs']['aggs']['attrs']['composite']['after'] = $after;
                }
                $params = [
                    'index' => $this->indexName,
                    'body' => [
                        'size' => 0,
                        'aggs' => $aggs,
                    ],
                ];
                $facetResponseVar = $this->client->elasticsearchClient->search($params);
            }
        } while ($after !== null);

        // --- Merge buckets, and for each (attribute_id, value_id, location) count unique products
        $facetAttributePairs = [];

        foreach ($productAttrBuckets as $bucket) {
            $attributeId = $bucket['key']['attribute_id'];
            $valueId = $bucket['key']['value_id'];
            $product_count = isset($bucket['unique_products']['product_ids']['value'])
                ? $bucket['unique_products']['product_ids']['value']
                : 0;
            $facetAttributePairs[$attributeId . ':' . $valueId] = [
                'attribute_id' => $attributeId,
                'value_id' => $valueId,
                'product_count' => $product_count,
            ];
        }
        foreach ($variantAttrBuckets as $bucket) {
            $attributeId = $bucket['key']['attribute_id'];
            $valueId = $bucket['key']['value_id'];
            $product_count = isset($bucket['back_to_product']['product_ids']['value'])
                ? $bucket['back_to_product']['product_ids']['value']
                : 0;

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
                    $esField = $esField . '.raw';
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

        if (isset($hit['inner_hits']['matching_variants']['hits']['hits']) && count($hit['inner_hits']['matching_variants']['hits']['hits']) > 0) {
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
                continue; // Ignore unmapped or empty criteria
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
                $must[] = [
                    'multi_match' => [
                        'query' => $value,
                        'fields' => $map['esFields'],
                        'type' => 'best_fields'
                    ]
                ];
                break;
            case 'terms':
                $values = is_array($value) ? $value : [$value];
                $must[] = [
                    'terms' => [
                        $map['esField'] => $values
                    ]
                ];
                break;
            case 'boolean':
                $must[] = [
                    'term' => [
                        $map['esField'] => ($value === '1' || $value === 1 || $value === true)
                    ]
                ];
                break;
            case 'match':
                $this->addMatchQuery($map['esField'], $value, $must);
                break;
            case 'term':
                $must[] = [
                    'term' => [
                        $map['esField'] => $value
                    ]
                ];
                break;
            case 'product_and_variant_attributes':
                $this->addAttributeFilters($value, $productAttrFilters, $variantAttrFilters);
                break;
        }
    }

    private function addMatchQuery(string $esField, $value, array &$must): void
    {
        // If value is only wildcard(s), skip (would match everything)
        if (is_string($value) && preg_replace('/[%*]/', '', $value) === '') {
            return;
        }

        // If value contains wildcards, use a wildcard query instead of match
        if (is_string($value) && (strpos($value, '%') !== false || strpos($value, '*') !== false)) {
            $pattern = str_replace(['%', '*'], '*', $value);
            $must[] = [
                'wildcard' => [
                    $esField . '.raw' => [
                        'value' => $pattern,
                        'case_insensitive' => true
                    ]
                ]
            ];
        } else {
            // Natural language search on analyzed field
            $must[] = [
                'match' => [$esField => $value]
            ];
        }
    }

    private function addAttributeFilters($value, array &$productAttrFilters, array &$variantAttrFilters): void
    {
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
    }

    private function buildFinalQuery(array $must, array $productAttrFilters, array $variantAttrFilters): array
    {
        $attributesMatchShould = [];

        if (!empty($productAttrFilters)) {
            $attributesMatchShould[] = [
                'bool' => ['must' => $productAttrFilters]
            ];
        }

        if (!empty($variantAttrFilters)) {
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

        if (!empty($attributesMatchShould)) {
            return [
                'bool' => [
                    'must' => $must,
                    'should' => $attributesMatchShould,
                    'minimum_should_match' => 1
                ]
            ];
        }

        return [
            'bool' => [
                'must' => $must
            ]
        ];
    }
}