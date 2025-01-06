<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters\OpenSearch;

use LeadingSystems\MerconisBundle\SearchEngine\Adapters\ClientInterface;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;

class Client implements ClientInterface
{
    public function getAdapterName(): string
    {
        return basename(__DIR__);
    }

    public function getAdapterDescription(): string
    {
        return 'Not implemented yet!';
    }

    public function initialize(): void
    {
        // TODO: Implement initialize() method.
    }

    public function testConnection(): OperationResult
    {
        $operationResult = new OperationResult();
        return $operationResult;
    }

    public function testIndex(string $indexName): OperationResult
    {
        $operationResult = new OperationResult();
        return $operationResult;
    }

    public function getNumDocumentsInIndex(string $indexName): int
    {
        return 0;
    }

    public function createIndex(string $indexName): OperationResult
    {
        $operationResult = new OperationResult();
        return $operationResult;
    }

    public function addAllProductsToIndex(): OperationResult
    {
        $operationResult = new OperationResult();
        return $operationResult;
    }
}