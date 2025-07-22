<?php

namespace LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;

interface ClientInterface
{
    public function initialize(): void;
    public function syncProducts(): OperationResult;
    public function createIndex(string $indexName): OperationResult;

    public function dummySearch(Adapter &$productSearchAdapter): array;
}