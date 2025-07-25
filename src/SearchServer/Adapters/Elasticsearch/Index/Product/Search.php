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
        ];

        $criteria = $productSearchAdapter->getSearchCriteria();
        $size = 1000;
        $productResultIds = [];
        $must = [];

        // 2. Build ES Query
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
                    // For product_code, producer (type keyword)
                    $must[] = [
                        'term' => [
                            $map['esField'] => $value
                        ]
                    ];
                    break;
                default:
                    // Unknown or unsupported query type (shouldn't happen in strict mapping)
                    break;
            }
        }

        $esQuery = ['bool' => ['must' => $must]];

        // --- Sorting support ---
        $sort = [];
        $sortingCriteria = $productSearchAdapter->getSortingCriteria();
        foreach ($sortingCriteria as $sortingRule) {
            $field = $sortingRule['field'] ?? null;
            $direction = strtolower($sortingRule['direction'] ?? 'ASC');

            // Map adapter field to ES field (reuse the search map if possible, else fallback)
            // For text fields, sort on .raw subfield; for keyword, boolean, integer, use field as-is
            if (isset($criteriaMap[$field])) {
                $esField = $criteriaMap[$field]['esField'];
                $queryType = $criteriaMap[$field]['queryType'];
                // If match type (i.e. ES field is text) sort on .raw, otherwise use ES field directly
                if ($queryType === 'match') {
                    $esField = $esField . '.raw';
                }
                // ES boolean/int/keyword are sortable as-is
                $sort[] = [$esField => ['order' => $direction]];
            }
            // else: ignore unknown sort fields for safety
        }

        // 3. Query/scroll extraction
        try {
            $params = [
                'index' => $this->indexName,
                'scroll' => '2m',
                'body' => [
                    'size' => $size,
                    'query' => $esQuery
                ]
            ];
            if (!empty($sort)) {
                $params['body']['sort'] = $sort;
            }
            $response = $this->client->elasticsearchClient->search($params);

            do {
                // Extract products from this batch
                if (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
                    $batchIds = array_column(array_column($response['hits']['hits'], '_source'), 'id');
                    $productResultIds = array_merge($productResultIds, $batchIds);
                }

                // Get the next batch if there are more results
                $scrollId = $response['_scroll_id'] ?? null;
                $numHits = count($response['hits']['hits']);

                if ($scrollId && $numHits > 0) {
                    $response = $this->client->elasticsearchClient->scroll([
                        'scroll_id' => $scrollId,
                        'scroll' => '2m'
                    ]);
                } else {
                    break; // No more results
                }
            } while (true);

            if (isset($scrollId)) {
                $this->client->elasticsearchClient->clearScroll(['scroll_id' => $scrollId]);
            }
            $productResultIds = array_values(array_unique($productResultIds));
            return $productResultIds;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}