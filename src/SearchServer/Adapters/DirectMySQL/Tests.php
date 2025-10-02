<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\TestsInterface;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Tests implements CommonInterface, TestsInterface
{
    use AdapterCommonTrait;

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function initialize(): void
    {
        // No initialization required.
    }

    public function testConnection(): OperationResult
    {
        try {
            $connection = $this->client->getConnection();
            if (!$connection->isConnected()) {
                $connection->connect();
            }
            $result = new OperationResult(true, 'Successfully connected to the MySQL database.');
        } catch (\Throwable $e) {
            $result = new OperationResult(false);
            $result->setException($e instanceof \Exception ? $e : new \Exception($e->getMessage(), (int)$e->getCode(), $e));
        }

        return $result;
    }

    public function testIndex(string $indexName): OperationResult
    {
        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setMessage('DirectMySQL adapter does not manage indices.');

        return $result;
    }
}


