<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters;

interface ClientInterface
{
    public function getAdapterName(): string;
    public function getAdapterDescription(): string;
    public function initialize(): void;
    public function testConnection(): TestResult;
    public function testIndex(string $indexName): TestResult;
    public function getNumProductsInIndex(string $indexName): int;
}