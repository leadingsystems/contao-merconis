<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch;

use Contao\StringUtil;
use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\ClientInterface;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

/*
 * IMPORTANT NOTE:
 * This SearchEngine works with a self-hosted version of Elasticsearch. Elasticsearch as a cloud service is currently not supported.
 */

class Client implements ClientInterface
{
    use AdapterCommonTrait;

    public ?ElasticsearchClient $elasticsearchClient = null;

    /*
     * Do me! Must not be hard-coded. Instead, make it configurable with a backend module!
     */
    private $host = 'https://localhost:9200';
    private $username = 'elastic';
    private $password = 'p+6FezswTw96zzK5rrlc';
    private $cert;

    public string $productIndexName = 'products';
    private ProductSync $productSync;

    public function __construct(ProductSync $productSync)
    {

        $this->productSync = $productSync;
        $this->productSync->setClient($this);
    }

    public function initialize(): void
    {
        $this->elasticsearchClient = ClientBuilder::create()
            ->setHosts([$this->host])
            ->setBasicAuthentication($this->username, $this->password)

            /*
             * Do me! Do not bypass SSL verification but instead provide a certificate.
             *  At the moment, we set the ssl verification to false only for a quick test.
             */
            // ->setCABundle($this->cert)
            ->setSSLVerification(false)

            ->build();
    }

    public function createIndex(string $indexName): OperationResult
    {
        $operationResult = new OperationResult();

        if ($this->testIndex($indexName)->getSuccess()) {
            $operationResult->setSuccess(false);
            $operationResult->setMessage('Index "' . $indexName . '" already exists!');
            return $operationResult;
        }

        $requestBody = [];

        switch ($indexName) {
            case $this->productIndexName:
                $requestBody = [
                    'mappings' => [
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'product_code' => ['type' => 'text'],
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                            ],
                            'description' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                            ],
                            'pages'        => ['type' => 'integer'],

                            'content_hash' => [
                                'type' => 'keyword',
                                'index' => false
                            ]
                        ],

                        'dynamic' => 'strict'
                    ],
                ];
                break;
        }

        if (empty($requestBody)) {
            $operationResult->setSuccess(false);
            $operationResult->setMessage('No mapping definition found for index "' . $indexName . '"');
            return $operationResult;
        }

        try {
            $response = $this->elasticsearchClient->indices()->create([
                'index' => $indexName,
                'body' => $requestBody
            ]);


            if ($response['acknowledged'] ?? false) {
                $operationResult->setSuccess(true);
                $operationResult->setMessage('Index "' . $indexName . '" was created successfully');
            } else {
                $operationResult->setSuccess(false);
                $operationResult->setMessage('Failed to create the "' . $indexName . '" index');
            }
        } catch (\Exception $e) {
            $operationResult->setException($e);
        }

        return $operationResult;
    }

    public function syncProducts(int $batchSize = 10000): OperationResult
    {
        if (!$this->testIndex($this->productIndexName)->getSuccess()) {
            $operationResult = new OperationResult();
            $operationResult->setSuccess(false);
            $operationResult->setMessage('Index "' . $this->productIndexName . '" does not exist. Create it!');
            return $operationResult;
        }

        $operationResult = $this->productSync->performSync($batchSize);
        return $operationResult;
    }

    public function dummySearch(Adapter &$productSearchAdapter): array
    {
        /*
         * Do me! Rename the dummySearch method.
         */
        $pageIds = $productSearchAdapter->getSearchCriteria()['pages'];
        $size = 1000; // Batch size per scroll request
        $productResultIds = [];

        try {
            // Initial search with scroll context
            $params = [
                'index' => $this->productIndexName,
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
            $response = $this->elasticsearchClient->search($params);

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
                    $response = $this->elasticsearchClient->scroll([
                        'scroll_id' => $scrollId,
                        'scroll' => '2m'
                    ]);
                } else {
                    break; // No more results
                }
            } while (true);

            // Optionally clear the scroll context (good practice!)
            if (isset($scrollId)) {
                $this->elasticsearchClient->clearScroll(['scroll_id' => $scrollId]);
            }

            return $productResultIds;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}