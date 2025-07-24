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
        // Get ALL search criteria (works with same input as the standard MySQL based productSearcher)
        $criteria = $productSearchAdapter->getSearchCriteria();
        $size = 1000; // Batch size per scroll request
        $productResultIds = [];

        // === 1. Build Elasticsearch Query ===
        $must = [];

        // ID filter (exact match or list)
        if (!empty($criteria['id'])) {
            $ids = is_array($criteria['id']) ? $criteria['id'] : [$criteria['id']];
            $must[] = ['terms' => ['id' => $ids]];
        }

        // Pages filter
        if (!empty($criteria['pages'])) {
            $pages = is_array($criteria['pages']) ? $criteria['pages'] : [$criteria['pages']];
            $must[] = ['terms' => ['pages' => $pages]];
        }

        // Fulltext search (multi-field, with boosting factors)
        if (!empty($criteria['fulltext'])) {
            $fulltext = $criteria['fulltext'];
            $must[] = [
                'multi_match' => [
                    'query' => $fulltext,
                    'fields' => [
                        'title^3',
                        'keywords^2',
                        'short_description^2',
                        'description',
                        'product_code^2',
                        'producer^2'
                    ],
                    'type' => 'best_fields'
                ]
            ];
        }

        // Handle any other fields generically (LIKE/term/wildcard, based on input style)
        foreach ($criteria as $field => $value) {
            if (\in_array($field, ['id', 'pages', 'fulltext'])) continue;
            if ($value === '' || $value === null) continue;

            // Map field names to ES names if different
            // $fieldMap = ['lsShopProductCode' => 'productCode', ...];
            // $esField = $fieldMap[$field] ?? $field;
            $esField = $field;

            if (is_array($value)) {
                $must[] = ['terms' => [$esField => $value]];
                continue;
            }
            // Wildcard pattern support
            if (strpos($value, '%') !== false || strpos($value, '*') !== false) {
                $pattern = str_replace(['%', '*'], '*', $value);
                $must[] = [
                    'wildcard' => [
                        $esField => [
                            'value' => $pattern,
                            'case_insensitive' => true
                        ]
                    ]
                ];
            } else {
                // Use ".raw" if the field is defined as keyword (for exact)
                $must[] = ['term' => [$esField . '.raw' => $value]];
            }
        }

        $esQuery = [
            'bool' => [
                'must' => $must
            ]
        ];

        try {
            // === 2. Scroll search for all matching IDs ===
            $params = [
                'index' => $this->indexName,
                'scroll' => '2m',
                'body' => [
                    'size' => $size,
                    'query' => $esQuery
                ]
            ];
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

            // Optional: unique IDs (if necessary)
            $productResultIds = array_values(array_unique($productResultIds));
            return $productResultIds;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}