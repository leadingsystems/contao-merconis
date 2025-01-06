<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters\Elasticsearch;

use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder;
use LeadingSystems\MerconisBundle\SearchEngine\Adapters\ClientInterface;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;

class Client implements ClientInterface
{
    private ?ElasticsearchClient $client = null;

    /*
     * Do me! Must not be hard-coded. Instead, make it configurable with a backend module!
     */
    private $host = 'https://localhost:9200';
    private $username = 'elastic';
    private $password = '7+3JVkR_XqSRohMRb-*s';
    private $cert;

    public function initialize(): void
    {
        $this->client = ClientBuilder::create()
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

    public function testConnection(): OperationResult
    {
        $operationResult = new OperationResult();

        try {
            $response = $this->client->ping();
            if ($response) {
                $operationResult->setSuccess(true);
                $operationResult->setMessage('Elasticsearch is reachable');
            } else {
                $operationResult->setSuccess(false);
                $operationResult->setMessage('Failed to reach Elasticsearch');
            }
        } catch (\Exception $e) {
            $operationResult->setException($e);
        }

        return $operationResult;
    }

    public function testIndex(string $indexName): OperationResult
    {
        $operationResult = new OperationResult();

        try {
            if ($this->client->indices()->exists(['index' => $indexName])) {
                $operationResult->setSuccess(true);

                if ($numDocumentsInIndex = $this->getNumDocumentsInIndex($indexName)) {
                    $numDocumentsMessage = 'Found ' . $numDocumentsInIndex . ' documents in the index.';
                } else {
                    $numDocumentsMessage = 'No documents found in the index.';
                }

                $operationResult->setMessage('The index "' . $indexName . '" exists. ' . $numDocumentsMessage);
            } else {
                $operationResult->setSuccess(false);
                $operationResult->setMessage('The index "' . $indexName . '" does not exist.');
            }
        } catch (\Exception $e) {
            $operationResult->setException($e);
        }

        return $operationResult;
    }

    public function getNumDocumentsInIndex(string $indexName): int
    {
        $params = [
            'index' => $indexName,
            'body'  => [
                'query' => [
                    'match_all' => new \stdClass(),
                ]
            ]
        ];

        $response = $this->client->search($params);
        return $response['hits']['total']['value'] ?? 0;
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
            case 'products':
                $requestBody = [
                    'mappings' => [
                        'properties' => [
                            'id' => ['type' => 'keyword'],
                            'product_code' => ['type' => 'text'],
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                            ],
                            'description' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                            ],
                            'pages' => ['type' => 'keyword'],
                        ],

                        'dynamic' => 'strict'
                    ],
                ];
                break;
        }

        try {
            $response = $this->client->indices()->create([
                'index' => $indexName,
                'body' => $requestBody
            ]);


            if ($response['acknowledged'] ?? false) {
                $operationResult->setSuccess(true);
                $operationResult->setMessage('Index "' . $indexName . '" was created successfully');
            } else {
                $operationResult->setSuccess(true);
                $operationResult->setMessage('Failed to create the "' . $indexName . '" index');
            }
        } catch (\Exception $e) {
            $operationResult->setException($e);
        }

        return $operationResult;
    }

    public function addAllProductsToIndex(): OperationResult
    {
        $operationResult = new OperationResult();

        $indexName = 'products';

        if (!$this->testIndex($indexName)->getSuccess()) {
            $operationResult->setSuccess(false);
            $operationResult->setMessage('Index "' . $indexName . '" does not exist. Create it!');
            return $operationResult;
        }

        try {
            $dbres_productsToIndex = \Database::getInstance()
                ->prepare("SELECT id, lsShopProductCode, title_de, description_de, pages FROM `tl_ls_shop_product` LIMIT 1000")
                ->execute();

            $bulkData = [];

            while ($dbres_productsToIndex->next()) {
                $bulkData[] = [
                    'index' => [
                        '_index' => $indexName,
                        '_id'    => $dbres_productsToIndex->id, // Using product ID as the document ID
                    ]
                ];

                $bulkData[] = [
                    'id' => (string)$dbres_productsToIndex->id,
                    'product_code' => $dbres_productsToIndex->lsShopProductCode,
                    'title' => $dbres_productsToIndex->title_de ?: '',
                    'description' => $dbres_productsToIndex->description_de ?: '',

                    /*
                     * Do me! Make sure to add multiple pages as an array!
                     */
                    'pages' => $dbres_productsToIndex->pages
                ];
            }

            if (!empty($bulkData)) {
                $chunks = array_chunk($bulkData, 100); // Process 100 actions per batch
                $allFailedItems = [];

                foreach ($chunks as $chunk) {
                    $response = $this->client->bulk(['body' => $chunk]);

                    if (isset($response['errors']) && $response['errors']) {
                        $failedItems = array_filter($response['items'], function($item) {
                            return isset($item['index']['error']);
                        });
                        $allFailedItems = array_merge($allFailedItems, $failedItems);
                    }
                }

                if (!empty($allFailedItems)) {
                    $failedItemsText = json_encode($allFailedItems, JSON_PRETTY_PRINT);
                    $operationResult->setSuccess(false);
                    $operationResult->setMessage("Some products failed to be indexed: \n" . $failedItemsText);
                } else {
                    $operationResult->setSuccess(true);
                    $operationResult->setMessage('All products were indexed successfully.');
                }
            } else {
                $operationResult->setSuccess(false);
                $operationResult->setMessage('No products found to index.');
            }
        } catch (\Exception $e) {
            $operationResult->setSuccess(false);
            $operationResult->setException($e);
        }

        return $operationResult;
    }

    public function getAdapterName(): string
    {
        return basename(__DIR__);
    }

    public function getAdapterDescription(): string
    {
        return 'This SearchEngine works with a self-hosted version of Elasticsearch. Elasticsearch as a cloud service is currently not supported.';
    }
}