<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Index\Product;

use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSyncInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Sync implements CommonInterface, IndexSyncInterface
{
    use AdapterCommonTrait;

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}