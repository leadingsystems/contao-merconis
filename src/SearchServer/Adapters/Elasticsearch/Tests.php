<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\TestsInterface;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Tests implements CommonInterface, TestsInterface
{
    use AdapterCommonTrait;

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function testConnection(): OperationResult
    {
        $operationResult = new OperationResult();

        try {
            $response = $this->client->elasticsearchClient->ping();
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
            if ($this->client->elasticsearchClient->indices()->exists(['index' => $indexName])) {
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

        $response = $this->client->elasticsearchClient->search($params);
        return $response['hits']['total']['value'] ?? 0;
    }
}