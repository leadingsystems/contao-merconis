<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexManageInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Manage implements CommonInterface, IndexManageInterface
{
    use AdapterCommonTrait;

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function initialize(): void
    {
        // Nothing to initialize for direct MySQL usage.
    }

    public function create(): OperationResult
    {
        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setMessage('DirectMySQL adapter does not manage indices.');

        return $result;
    }
}


