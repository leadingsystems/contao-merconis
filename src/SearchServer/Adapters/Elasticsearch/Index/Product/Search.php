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
                'producer^2'
            ]
        ],
        'attributes' => [
            'queryType' => 'product_and_variant_attributes'
        ],
    ];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function search(Adapter &$productSearchAdapter): SearchResult
    {
        $criteria = $this->prepareCriteria($productSearchAdapter->getSearchCriteria());
        $baseCriteria = $this->prepareBaseCriteria($criteria);

        // Get both search results and filtered facets in a single query
        $searchAndFacetsResult = $this->getSearchResultsWithFilteredFacets($criteria, $productSearchAdapter);

        // Get unfiltered facets separately (only if needed)
        $unfilteredFacets = $this->getFacets($baseCriteria, 'unfiltered');

        $facetData = new Facets(
            $unfilteredFacets,
            $searchAndFacetsResult['filtered_facets'],
            $this->combineFacetData($unfilteredFacets, $searchAndFacetsResult['filtered_facets'])
        );

        $result = new SearchResult($searchAndFacetsResult['product_ids'], $facetData);

        return $result;
    }

    private function prepareCriteria(array $criteria): array
    {
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
            $key = $facet['location'] . '_' . $facet['attribute_id'] . '_' . $facet['value_id'];
            $filteredLookup[$key] = $facet['doc_count'];
        }

        // Process unfiltered facets and mark availability
        foreach ($unfilteredFacets as $facet) {
            $key = $facet['location'] . '_' . $facet['attribute_id'] . '_' . $facet['value_id'];
            $filteredCount = $filteredLookup[$key] ?? 0;

            $combined[] = [
                'location' => $facet['location'],
                'attribute_id' => $facet['attribute_id'],
                'value_id' => $facet['value_id'],
                'total_doc_count' => $facet['doc_count'], // Count without filters
                'filtered_doc_count' => $filteredCount,   // Count with current filters
                'is_available' => $filteredCount > 0,     // Can be used for greying out
                'is_filtered_out' => $filteredCount === 0 && $facet['doc_count'] > 0
            ];
        }

        return $combined;
    }

    /**
     * OPTIMIZED: Get search results AND filtered facets in a single query
     */
    private function getSearchResultsWithFilteredFacets(array $criteria, Adapter $productSearchAdapter): array
    {
        $mainQuery = $this->buildQueryForCriteria($criteria);
        $sort = $this->buildSortCriteria($productSearchAdapter->getSortingCriteria());
        $aggs = $this->buildAggregations();

        try {
            $params = [
                'index' => $this->indexName,
                'scroll' => self::SCROLL_TIMEOUT,
                'body' => [
                    'size' => self::DEFAULT_SIZE,
                    'query' => $mainQuery,
                    '_source' => ['id'],
                    'aggs' => $aggs, // Add aggregations to the search query
                ]
            ];

            if (!empty($sort)) {
                $params['body']['sort'] = $sort;
            }

            return $this->executeScrollSearchWithFacets($params);
        } catch (\Exception $e) {
            return [
                'product_ids' => ['error' => $e->getMessage()],
                'filtered_facets' => []
            ];
        }
    }

    /**
     * OPTIMIZED: Execute scroll search and extract both results and facets
     */
    private function executeScrollSearchWithFacets(array $params): array
    {
        $productResultIds = [];
        $filteredFacets = [];

        $response = $this->client->elasticsearchClient->search($params);
        $scrollId = null;
        $isFirstResponse = true;

        do {
            // Extract facets only from the first response (they're the same across all scroll pages)
            if ($isFirstResponse && isset($response['aggregations'])) {
                $filteredFacets = $this->processFacetResponse($response);
                $isFirstResponse = false;
            }

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
            'filtered_facets' => $filteredFacets
        ];
    }

    private function getFacets(array $criteria, string $context = ''): array
    {
        $query = $this->buildQueryForCriteria($criteria);
        $aggs = $this->buildAggregations();

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
            return $this->processFacetResponse($facetResponse);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function buildAggregations(): array
    {
        return [
            'attributes' => [
                'nested' => [
                    'path' => 'attributes'
                ],
                'aggs' => [
                    'attribute_ids' => [
                        'terms' => ['field' => 'attributes.attribute_id', 'size' => self::DEFAULT_SIZE],
                        'aggs' => [
                            'value_ids' => [
                                'terms' => ['field' => 'attributes.value_id', 'size' => self::DEFAULT_SIZE]
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
                        'terms' => ['field' => 'variants.attributes.attribute_id', 'size' => self::DEFAULT_SIZE],
                        'aggs' => [
                            'value_ids' => [
                                'terms' => ['field' => 'variants.attributes.value_id', 'size' => self::DEFAULT_SIZE]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private function processFacetResponse(ElasticsearchResponse $facetResponse): array
    {
        $facetAttributePairs = [];

        // Product attributes
        if (isset($facetResponse['aggregations']['attributes']['attribute_ids']['buckets'])) {
            $facetAttributePairs = array_merge(
                $facetAttributePairs,
                $this->processFacetBuckets($facetResponse['aggregations']['attributes']['attribute_ids']['buckets'], 'product')
            );
        }

        // Variant attributes
        if (isset($facetResponse['aggregations']['variant_attributes']['attribute_ids']['buckets'])) {
            $facetAttributePairs = array_merge(
                $facetAttributePairs,
                $this->processFacetBuckets($facetResponse['aggregations']['variant_attributes']['attribute_ids']['buckets'], 'variant')
            );
        }

        return $facetAttributePairs;
    }

    private function processFacetBuckets(array $buckets, string $location): array
    {
        $pairs = [];
        foreach ($buckets as $attrBucket) {
            $attributeId = $attrBucket['key'];
            foreach ($attrBucket['value_ids']['buckets'] as $valBucket) {
                $valueId = $valBucket['key'];
                $pairs[] = [
                    'location' => $location,
                    'attribute_id' => $attributeId,
                    'value_id' => $valueId,
                    'doc_count' => $valBucket['doc_count']
                ];
            }
        }
        return $pairs;
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

    /**
     * Given search criteria, builds the full ES query (bool/must/should etc.)
     */
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