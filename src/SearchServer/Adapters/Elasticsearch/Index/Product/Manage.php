<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Index\Product;

use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexManageInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Manage implements CommonInterface, IndexManageInterface
{
    use AdapterCommonTrait;

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}