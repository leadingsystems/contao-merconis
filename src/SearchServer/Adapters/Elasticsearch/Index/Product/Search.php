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
        // --- 1. Prepare ---
        // Get ALL search criteria (works with same input as the standard MySQL based productSearcher)
        $criteria = $productSearchAdapter->getSearchCriteria();
        $size = 1000;
        $productResultIds = [];

        // === 1. Build Elasticsearch Query ===
        // Map search criteria fields to ES field names
        $fieldMap = [
            // DB field          => ES field
            'lsShopProductCode'    => 'product_code',
            'lsShopProductProducer'=> 'producer',
            'shortDescription'     => 'short_description',
            // Add other mappings as needed
            // Synced fields for new booleans:
            'is_published'         => 'is_published',
            'is_new'               => 'is_new',
            'is_sale'              => 'is_sale',
            // main fields already match: id, pages, title, keywords, description
            // if not, map them here!
        ];
        $booleanFields = ['is_published', 'is_new', 'is_sale'];

        // --- 2. Build ES Query ---
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
        // Fulltext search with boosting factors
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

        // All other fields
        foreach ($criteria as $field => $value) {
            if (in_array($field, ['id', 'pages', 'fulltext'])) continue;
            if ($value === '' || $value === null) continue;

            $esField = $fieldMap[$field] ?? $field;

            // Boolean criteria
            if (in_array($esField, $booleanFields, true)) {
                // Normalize MySQL '1' or true or 1 into bool true, else false
                $boolValue = ($value === '1' || $value === 1 || $value === true);
                $must[] = ['term' => [$esField => $boolValue]];
                continue;
            }

            // List/array filter
            if (is_array($value)) {
                $must[] = ['terms' => [$esField => $value]];
                continue;
            }

            // Wildcard support: if value is ONLY "*" or "%" then skip clause (would match all)
            if (is_string($value) && preg_replace('/[%*]/', '', $value) === '') {
                // Value is only "*" and/or "%" -- skip, would be must-always match.
                continue;
            }

            // Wildcard (partial) match
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
                continue;
            }

            // Exact match on ".raw" subfield if available, otherwise use term
            // (You may want to check mapping for availability of .raw)
            $must[] = ['term' => [$esField . '.raw' => $value]];
        }

        $esQuery = ['bool' => ['must' => $must]];

        // --- 3. Query/scroll extraction ---
        try {
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
            $productResultIds = array_values(array_unique($productResultIds));
            return $productResultIds;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}