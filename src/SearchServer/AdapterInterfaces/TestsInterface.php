<?php

namespace LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;

interface TestsInterface
{
    public function testConnection(): OperationResult;
    public function testIndex(string $indexName): OperationResult;
}