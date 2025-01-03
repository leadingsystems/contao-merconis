<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters\OpenSearch;

use LeadingSystems\MerconisBundle\SearchEngine\Adapters\ClientInterface;

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

    public function testConnection(): string
    {
        return '';
    }

    public function testProductsIndex(): string
    {
        return '';
    }
}