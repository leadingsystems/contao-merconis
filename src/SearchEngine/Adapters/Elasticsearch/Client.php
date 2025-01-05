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
        $testResult = new OperationResult();

        try {
            $response = $this->client->ping();
            if ($response) {
                $testResult->setSuccess(true);
                $testResult->setMessage('Elasticsearch is reachable');
            } else {
                $testResult->setSuccess(false);
                $testResult->setMessage('Failed to reach Elasticsearch');
            }
        } catch (\Exception $e) {
            $testResult->setException($e);
        }

        return $testResult;
    }

    public function testIndex(string $indexName): OperationResult
    {
        $testResult = new OperationResult();

        try {
            if ($this->client->indices()->exists(['index' => $indexName])) {
                $testResult->setSuccess(true);

                if ($numProductsInIndex = $this->getNumProductsInIndex($indexName)) {
                    $numDocumentsMessage = 'Found ' . $numProductsInIndex . ' documents in the index.';
                } else {
                    $numDocumentsMessage = 'No documents found in the index.';
                }

                $testResult->setMessage('The index "' . $indexName . '" exists. ' . $numDocumentsMessage);
            } else {
                $testResult->setSuccess(false);
                $testResult->setMessage('The index "' . $indexName . '" does not exist.');
            }
        } catch (\Exception $e) {
            $testResult->setException($e);
        }

        return $testResult;
    }

    public function getNumProductsInIndex(string $indexName): int
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
        $testResult = new OperationResult();

        if ($this->testIndex($indexName)->getSuccess()) {
            $testResult->setSuccess(false);
            $testResult->setMessage('Index "' . $indexName . '" already exists!');
            return $testResult;
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
                $testResult->setSuccess(true);
                $testResult->setMessage('Index "' . $indexName . '" was created successfully');
            } else {
                $testResult->setSuccess(true);
                $testResult->setMessage('Failed to create the "' . $indexName . '" index');
            }
        } catch (\Exception $e) {
            $testResult->setException($e);
        }

        return $testResult;
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