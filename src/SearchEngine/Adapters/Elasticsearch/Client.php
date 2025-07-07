<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters\Elasticsearch;

use Contao\StringUtil;
use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder;
use LeadingSystems\MerconisBundle\SearchEngine\Adapters\ClientInterface;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;

class Client implements ClientInterface
{
    public ?ElasticsearchClient $client = null;

    /*
     * Do me! Must not be hard-coded. Instead, make it configurable with a backend module!
     */
    private $host = 'https://localhost:9200';
    private $username = 'elastic';
    private $password = '7+3JVkR_XqSRohMRb-*s';
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
                            'pages'        => ['type' => 'keyword'],

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
            $response = $this->client->indices()->create([
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

    public function getAdapterName(): string
    {
        return basename(__DIR__);
    }

    public function getAdapterDescription(): string
    {
        return 'This SearchEngine works with a self-hosted version of Elasticsearch. Elasticsearch as a cloud service is currently not supported.';
    }
}