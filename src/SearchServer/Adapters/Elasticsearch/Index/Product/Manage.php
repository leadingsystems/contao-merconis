<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Index\Product;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexManageInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Manage implements CommonInterface, IndexManageInterface
{
    use AdapterCommonTrait;

    private Client $client;
    private string $indexName = 'products';
    private array $indexDefinition = [
        'mappings' => [
            'properties' => [
                'id' => ['type' => 'integer'],
                'product_code' => ['type' => 'text'],
                'title' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                ],
                'description' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                ],
                'pages'        => ['type' => 'integer'],

                'content_hash' => [
                    'type' => 'keyword',
                    'index' => false
                ]
            ],

            'dynamic' => 'strict'
        ],
    ];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(): OperationResult
    {
        return $this->client->createIndex($this->indexName, $this->indexDefinition);
    }
}