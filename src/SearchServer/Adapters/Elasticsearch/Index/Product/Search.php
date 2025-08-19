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

    public function search(Adapter &$productSearchAdapter, string $language, bool $activateFacets = true, bool $activateMatchEstimates = true): SearchResult
    {
        $this->language = $language;
        $criteria = $this->prepareCriteria($productSearchAdapter->getSearchCriteria());
        
        // Use single request approach with post_filter for attribute filters
        $searchResultData = $this->executeUnifiedSearch($criteria, $productSearchAdapter, $activateFacets);

        // Calculate unmatched products from single response
        $hasUnmatchedProducts = false;
        $numUnmatchedProducts = 0;
        $countWithAttributes = $searchResultData['total_hits_filtered'];
        $countWithoutAttributes = $searchResultData['total_hits_unfiltered'];
        
        if ($countWithoutAttributes > $countWithAttributes) {
            $hasUnmatchedProducts = true;
            $numUnmatchedProducts = $countWithoutAttributes - $countWithAttributes;
        }

        if (!$activateFacets) {
            $facetData = new Facets([], [], []);
        } else {
            // Process facets from single response - no need to combine multiple sources
            $facetData = $this->processFacetsFromUnifiedResponse($searchResultData);
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

    private function prepareCriteria(array $criteria): array
    {
        return $criteria;
    }

    /**
     * Execute a single unified search that combines query, filters, and aggregations
     */
    private function executeUnifiedSearch(array $criteria, Adapter $productSearchAdapter, bool $activateFacets): array
    {
        // Separate attribute filters from other criteria
        $attributeFilters = $criteria['attributes'] ?? [];
        $baseCriteria = $criteria;
        unset($baseCriteria['attributes']);
        
        // Build base query (without attribute filters)
        $baseQuery = $this->buildQueryForCriteria($baseCriteria);
        
        // Build post_filter for attribute filters
        $postFilter = $this->buildAttributePostFilter($attributeFilters);
        
        // Build sorting
        $sort = $this->buildSortCriteria($productSearchAdapter->getSortingCriteria());
        
        // Build aggregations that will show available facets based on base query
        $aggs = $activateFacets ? $this->buildUnifiedAggregations() : [];
        
        try {
            $params = [
                'index' => $this->indexName,
                'scroll' => self::SCROLL_TIMEOUT,
                'body' => [
                    'size' => self::DEFAULT_SIZE,
                    'query' => $baseQuery,
                    '_source' => ['id'],
                    'track_total_hits' => true,
                ]
            ];
            
            if (!empty($postFilter)) {
                $params['body']['post_filter'] = $postFilter;
            }
            
            if ($activateFacets) {
                $params['body']['aggs'] = $aggs;
            }
            
            if (!empty($sort)) {
                $params['body']['sort'] = $sort;
            }
            
            return $this->executeScrollSearchUnified($params);
            
        } catch (\Exception $e) {
            return [
                'product_ids' => ['error' => $e->getMessage()],
                'facets' => [],
                'total_hits_filtered' => 0,
                'total_hits_unfiltered' => 0,
            ];
        }
    }
    
    /**
     * Build post_filter for attribute filters to be applied after aggregations
     */
    private function buildAttributePostFilter(array $attributeFilters): array
    {
        if (empty($attributeFilters)) {
            return [];
        }
        
        $productAttrFilters = [];
        $variantAttrFilters = [];
        
        foreach ($attributeFilters as $filter) {
            if (!isset($filter['attribute_id'], $filter['value_id'])) {
                continue;
            }
            
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
        
        $should = [];
        if (!empty($productAttrFilters)) {
            $should[] = ['bool' => ['must' => $productAttrFilters]];
        }
        if (!empty($variantAttrFilters)) {
            $should[] = [
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
        
        return [
            'bool' => [
                'should' => $should,
                'minimum_should_match' => 1
            ]
        ];
    }
    
    /**
     * Build simplified aggregations that return available facets
     */
    private function buildUnifiedAggregations(): array
    {
        return [
            'product_attributes' => [
                'nested' => ['path' => 'attributes'],
                'aggs' => [
                    'attribute_values' => [
                        'composite' => [
                            'sources' => [
                                ['attribute_id' => ['terms' => ['field' => 'attributes.attribute_id']]],
                                ['value_id' => ['terms' => ['field' => 'attributes.value_id']]]
                            ],
                            'size' => self::DEFAULT_SIZE
                        ],
                        'aggs' => [
                            'product_count' => [
                                'reverse_nested' => new \stdClass(),
                                'aggs' => [
                                    'unique_products' => ['cardinality' => ['field' => 'id']]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'variant_attributes' => [
                'nested' => ['path' => 'variants.attributes'],
                'aggs' => [
                    'attribute_values' => [
                        'composite' => [
                            'sources' => [
                                ['attribute_id' => ['terms' => ['field' => 'variants.attributes.attribute_id']]],
                                ['value_id' => ['terms' => ['field' => 'variants.attributes.value_id']]]
                            ],
                            'size' => self::DEFAULT_SIZE
                        ],
                        'aggs' => [
                            'product_count' => [
                                'reverse_nested' => new \stdClass(),
                                'aggs' => [
                                    'unique_products' => ['cardinality' => ['field' => 'id']]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Execute scroll search with unified approach
     */
    private function executeScrollSearchUnified(array $params): array
    {
        $productResultIds = [];
        $facets = [];
        $totalHitsFiltered = 0;
        $totalHitsUnfiltered = 0;
        
        $response = $this->client->elasticsearchClient->search($params);
        $scrollId = null;
        $isFirstResponse = true;
        
        do {
            if ($isFirstResponse) {
                // Get total hits (before post_filter applied = unfiltered)
                $totalHitsUnfiltered = $response['hits']['total']['value'] ?? 0;
                
                // Get filtered hits by executing a count query with post_filter
                if (isset($params['body']['post_filter'])) {
                    $countParams = [
                        'index' => $this->indexName,
                        'body' => [
                            'size' => 0,
                            'query' => $params['body']['query'],
                            'post_filter' => $params['body']['post_filter'],
                            'track_total_hits' => true
                        ]
                    ];
                    $countResponse = $this->client->elasticsearchClient->search($countParams);
                    $totalHitsFiltered = $countResponse['hits']['total']['value'] ?? 0;
                } else {
                    $totalHitsFiltered = $totalHitsUnfiltered;
                }
                
                // Process aggregations if present
                if (isset($response['aggregations'])) {
                    $facets = $this->processUnifiedFacetResponse($response['aggregations']);
                }
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
            'facets' => $facets,
            'total_hits_filtered' => $totalHitsFiltered,
            'total_hits_unfiltered' => $totalHitsUnfiltered,
        ];
    }
    
    /**
     * Process facet response from unified aggregations
     */
    private function processUnifiedFacetResponse(array $aggregations): array
    {
        $facets = [];
        
        // Process product attributes
        if (isset($aggregations['product_attributes']['attribute_values']['buckets'])) {
            foreach ($aggregations['product_attributes']['attribute_values']['buckets'] as $bucket) {
                $attributeId = $bucket['key']['attribute_id'];
                $valueId = $bucket['key']['value_id'];
                $productCount = $bucket['product_count']['unique_products']['value'] ?? 0;
                
                $key = $attributeId . ':' . $valueId;
                $facets[$key] = [
                    'attribute_id' => $attributeId,
                    'value_id' => $valueId,
                    'product_count' => $productCount,
                ];
            }
        }
        
        // Process variant attributes
        if (isset($aggregations['variant_attributes']['attribute_values']['buckets'])) {
            foreach ($aggregations['variant_attributes']['attribute_values']['buckets'] as $bucket) {
                $attributeId = $bucket['key']['attribute_id'];
                $valueId = $bucket['key']['value_id'];
                $productCount = $bucket['product_count']['unique_products']['value'] ?? 0;
                
                $key = $attributeId . ':' . $valueId;
                if (isset($facets[$key])) {
                    $facets[$key]['product_count'] += $productCount;
                } else {
                    $facets[$key] = [
                        'attribute_id' => $attributeId,
                        'value_id' => $valueId,
                        'product_count' => $productCount,
                    ];
                }
            }
        }
        
        return array_values($facets);
    }
    
    /**
     * Process facets from unified response - replaces the complex combineFacetData method
     */
    private function processFacetsFromUnifiedResponse(array $searchResultData): Facets
    {
        $availableFacets = $searchResultData['facets'];
        
        // Transform facets to the expected format
        $combinedFacets = [];
        foreach ($availableFacets as $facet) {
            $combinedFacets[] = [
                'attribute_id' => $facet['attribute_id'],
                'value_id' => $facet['value_id'],
                'total_product_count' => $facet['product_count'],
                'filtered_product_count' => $facet['product_count'], // Same as total since these are already filtered
                'is_available' => $facet['product_count'] > 0,
                'is_filtered_out' => false, // No items are filtered out in this approach
                'is_invalid' => false
            ];
        }
        
        return new Facets(
            $availableFacets,  // unfiltered facets (same as available in this approach)
            $availableFacets,  // filtered facets (same as available)
            $combinedFacets    // combined data
        );
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
        foreach ($criteria as $criterion => $value) {
            if (!isset($this->criteriaMap[$criterion]) || $value === '' || $value === null) {
                continue;
            }
            // Skip attributes as they are now handled via post_filter
            if ($criterion === 'attributes') {
                continue;
            }
            $map = $this->criteriaMap[$criterion];
            $this->addSimpleQueryClause($map, $value, $must);
        }
        return empty($must) ? ['match_all' => new \stdClass()] : ['bool' => ['must' => $must]];
    }

    /**
     * Simplified query clause building without attribute handling
     */
    private function addSimpleQueryClause(array $map, $value, array &$must): void
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
            // Skip product_and_variant_attributes - handled via post_filter
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


}