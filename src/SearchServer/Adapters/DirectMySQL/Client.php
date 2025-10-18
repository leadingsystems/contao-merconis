<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL;

use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\ClientInterface;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Client implements ClientInterface
{
    use AdapterCommonTrait;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function initialize(): void
    {
        // No bootstrap required for direct MySQL access.
    }

    public function createIndex(string $indexName, array $indexDefinition): OperationResult
    {
        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setMessage('DirectMySQL adapter relies on existing tables; no index creation necessary.');

        return $result;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}


