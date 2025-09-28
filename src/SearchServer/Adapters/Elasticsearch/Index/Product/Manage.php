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
        'settings' => [
            'analysis' => [
                'tokenizer' => [
                    'comma_tokenizer' => [
                        'type' => 'pattern',
                        'pattern' => ','
                    ]
                ],
                'analyzer' => [
                    'comma_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'comma_tokenizer',
                        'filter' => ['lowercase']
                    ]
                ]
            ]
        ],
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
                'attributes' => [
                    'type' => 'nested',
                    'properties' => [
                        'attribute_id' => ['type' => 'integer'],
                        'value_id' => ['type' => 'integer']
                    ]
                ],
                'stock' => [
                    'type' => 'double'
                ],

                // ==== MULTI-LANGUAGE FIELDS ====
                'title' => [
                    'type' => 'object',
                    'dynamic' => true,
                    'properties' => [
                        'de' => [
                            'type' => 'text',
                            'analyzer' => 'german',
                            'fields' => [
                                'raw' => ['type' => 'keyword']
                            ]
                        ],
                        'en' => [
                            'type' => 'text',
                            'analyzer' => 'english',
                            'fields' => [
                                'raw' => ['type' => 'keyword']
                            ]
                        ],
                    ]
                ],

                'keywords' => [
                    'type' => 'object',
                    'dynamic' => true,
                    'properties' => [
                        'de' => [
                            'type' => 'text',
                            'analyzer' => 'comma_analyzer',
                            'fields' => [
                                'raw' => ['type' => 'keyword']
                            ]
                        ],
                        'en' => [
                            'type' => 'text',
                            'analyzer' => 'comma_analyzer',
                            'fields' => [
                                'raw' => ['type' => 'keyword']
                            ]
                        ],
                    ]
                ],

                'short_description' => [
                    'type' => 'object',
                    'dynamic' => true,
                    'properties' => [
                        'de' => [
                            'type' => 'text',
                            'analyzer' => 'german',
                            'fields' => [
                                'raw' => ['type' => 'keyword']
                            ]
                        ],
                        'en' => [
                            'type' => 'text',
                            'analyzer' => 'english',
                            'fields' => [
                                'raw' => ['type' => 'keyword']
                            ]
                        ],
                    ]
                ],

                'description' => [
                    'type' => 'object',
                    'dynamic' => true,
                    'properties' => [
                        'de' => [
                            'type' => 'text',
                            'analyzer' => 'german',
                            'fields' => [
                                'raw' => ['type' => 'keyword']
                            ]
                        ],
                        'en' => [
                            'type' => 'text',
                            'analyzer' => 'english',
                            'fields' => [
                                'raw' => ['type' => 'keyword']
                            ]
                        ],
                    ]
                ],
                // ==== END MULTI-LANGUAGE FIELDS ====

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
                ],
                'variants' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => [
                            'type' => 'integer'
                        ],
                        'variant_code' => [
                            'type' => 'keyword',
                            'fields' => [
                                'text' => ['type' => 'text']
                            ]
                        ],
                        'attributes' => [
                            'type' => 'nested',
                            'properties' => [
                                'attribute_id' => ['type' => 'integer'],
                                'value_id' => ['type' => 'integer']
                            ]
                        ],
                        'stock' => [
                            'type' => 'double'
                        ],
                        'is_published' => [
                            'type' => 'boolean'
                        ]
                    ]
                ],
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