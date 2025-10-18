<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSyncInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Sync implements CommonInterface, IndexSyncInterface
{
    use AdapterCommonTrait;

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function initialize(): void
    {
        // No synchronization needed for direct MySQL.
    }

    public function sync(): OperationResult
    {
        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setMessage('DirectMySQL adapter operates on live data; no sync required.');

        return $result;
    }
}


