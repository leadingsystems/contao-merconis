<?php

namespace LeadingSystems\MerconisBundle\SearchEngine;

use LeadingSystems\MerconisBundle\SearchEngine\Adapters\ClientInterface;

class SearchEngine
{
    private iterable $availableClientAdapterServices;
    private ?ClientInterface $clientAdapterService = null;

    /*
     * Do me! This must not be hard-coded. Instead, there must be a backend module to configure which adapter
     *  to use with which credentials.
     */
    private $clientAdapterToUse = 'Elasticsearch';

    public function __construct(iterable $availableClientAdapterServices)
    {
        $this->availableClientAdapterServices = $availableClientAdapterServices;
        foreach ($this->availableClientAdapterServices as $clientAdapterService) {
            if ($clientAdapterService->getAdapterName() === $this->clientAdapterToUse) {
                $this->clientAdapterService = $clientAdapterService;
                $this->clientAdapterService->initialize();
            }
        }
    }

    public function runTests(): array
    {
        $testResults = [
            'Connection' => $this->clientAdapterService->testConnection()->getResultString(),
            'Index (products)' => $this->clientAdapterService->testIndex('products')->getResultString()
        ];

        return  $testResults;
    }

    public function createProductsIndex(): string
    {
        return $this->clientAdapterService->createIndex('products')->getResultString();
    }

    public function addAllProductsToIndex(): string
    {
        return $this->clientAdapterService->addAllProductsToIndex->getResultString();
    }

    public function getAvailableClientAdapters(): array
    {
        $availableClientAdapters = [];
        /** @var $clientAdapterService ClientInterface */
        foreach ($this->availableClientAdapterServices as $clientAdapterService) {
            $availableClientAdapters[] = [$clientAdapterService->getAdapterName(), $clientAdapterService->getAdapterDescription()];
        }

        return $availableClientAdapters;
    }
}