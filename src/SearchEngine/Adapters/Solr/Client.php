<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters\Solr;

use LeadingSystems\MerconisBundle\SearchEngine\Adapters\ClientInterface;
use LeadingSystems\MerconisBundle\SearchEngine\Adapters\TestResult;

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

    public function testConnection(): TestResult
    {
        $testResult = new TestResult();
        return $testResult;
    }

    public function testIndex(string $indexName): TestResult
    {
        $testResult = new TestResult();
        return $testResult;
    }

    public function getNumProductsInIndex(string $indexName): int
    {
        return 0;
    }

    public function createIndex(string $indexName): TestResult
    {
        $testResult = new TestResult();
        return $testResult;
    }
}