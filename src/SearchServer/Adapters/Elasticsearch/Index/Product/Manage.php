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
            'dynamic' => 'strict',
            'properties' => [
                'id' => [
                    'type' => 'integer'
                ],
                'product_code' => [
                    'type' => 'keyword',
                    'fields' => [
                        'text' => ['type' => 'text']
                    ]
                ],
                'title' => [
                    'type' => 'text',
                    'fields' => [
                        'raw' => ['type' => 'keyword']
                    ],
                    'analyzer' => 'standard',
                ],
                'keywords' => [
                    'type' => 'text',
                    'fields' => [
                        'raw' => ['type' => 'keyword']
                    ]
                ],
                'short_description' => [
                    'type' => 'text',
                    'fields' => [
                        'raw' => ['type' => 'keyword']
                    ]
                ],
                'description' => [
                    'type' => 'text',
                    'fields' => [
                        'raw' => ['type' => 'keyword']
                    ],
                    'analyzer' => 'standard'
                ],
                'producer' => [
                    'type' => 'keyword',
                    'fields' => [
                        'text' => ['type' => 'text']
                    ]
                ],
                'pages' => [
                    'type' => 'integer'
                ],

                'is_published' => [
                    'type' => 'boolean'
                ],
                'is_new' => [
                    'type' => 'boolean'
                ],
                'is_sale' => [
                    'type' => 'boolean'
                ],

                'content_hash' => [
                    'type' => 'keyword',
                    'index' => false
                ]
            ]
        ]
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