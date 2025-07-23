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
        $pageIds = $productSearchAdapter->getSearchCriteria()['pages'];
        $size = 1000; // Batch size per scroll request
        $productResultIds = [];

        try {
            // Initial search with scroll context
            $params = [
                'index' => $this->indexName,
                'scroll' => '2m', // Scroll context valid for 2 minutes
                'body' => [
                    'size' => $size,
                    'query' => [
                        'terms' => [
                            'pages' => $pageIds
                        ]
                    ]
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

            // Optionally clear the scroll context (good practice!)
            if (isset($scrollId)) {
                $this->client->elasticsearchClient->clearScroll(['scroll_id' => $scrollId]);
            }

            return $productResultIds;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}