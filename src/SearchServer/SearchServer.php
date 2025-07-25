<?php

namespace LeadingSystems\MerconisBundle\SearchServer;

use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\ClientInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexManageInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSearchInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSyncInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\TestsInterface;

class SearchServer
{
    private iterable $availableClientAdapterServices;
    private ?ClientInterface $clientAdapterService = null;

    /*
     * Do me! This must not be hard-coded. Instead, there must be a backend module to configure which adapter
     *  to use with which credentials.
     */
    private $clientAdapterToUse = 'Elasticsearch';

    private IndexManageInterface $serviceIndexProductManage;
    private IndexSearchInterface $serviceIndexProductSearch;
    private IndexSyncInterface $serviceIndexProductSync;
    private ClientInterface $serviceClient;
    private TestsInterface $serviceTests;

    public function __construct(iterable ...$availableServices)
    {
        foreach ($availableServices as $services) {
            foreach ($services as $service) {
                if ($service->getAdapterName() === $this->clientAdapterToUse) {

                    // 1. Get the FQCN and split by backslash "\"
                    $classWithNamespace = get_class($service);
                    $classParts = explode('\\', $classWithNamespace);

                    // 2. Find "Adapters" segment
                    $adaptersIndex = array_search('Adapters', $classParts);
                    if ($adaptersIndex === false) {
                        throw new \Exception('Unexpected FQCN!');
                    } else {
                        // 3. Skip "Adapters" and adapter type itself
                        $propertyParts = array_slice($classParts, $adaptersIndex + 2);
                    }

                    // 4. Create property name from remaining segments (e.g., Index/Product/Manage -> indexProductManage)
                    $propertyName = '';
                    foreach ($propertyParts as $part) {
                        $propertyName .= ucfirst($part);
                    }

                    // Prefix with "service"
                    $propertyName = 'service' . $propertyName;

                    $this->{$propertyName} = $service;
                    $service->initialize();
                    break;
                }
            }
        }
    }

    public function runTests(): array
    {
        $testResults = [
            'Connection' => $this->serviceTests->testConnection()->getResultString(),
            'Index (products)' => $this->serviceTests->testIndex('products')->getResultString()
        ];

        return  $testResults;
    }

    public function createProductsIndex(): string
    {
        return $this->serviceIndexProductManage->create()->getResultString();
    }

    public function addAllProductsToIndex(): string
    {
        return $this->serviceIndexProductSync->sync()->getResultString();
    }

    public function search(Adapter &$productSearchAdapter)
    {
        return $this->serviceIndexProductSearch->search($productSearchAdapter);
    }
}