<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;

interface ClientInterface
{
    public function getAdapterName(): string;
    public function getAdapterDescription(): string;
    public function initialize(): void;
    public function testConnection(): OperationResult;
    public function testIndex(string $indexName): OperationResult;
    public function getNumDocumentsInIndex(string $indexName): int;
    public function createIndex(string $indexName): OperationResult;
}