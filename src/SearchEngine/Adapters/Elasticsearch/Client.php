<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters\Elasticsearch;

use Contao\StringUtil;
use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder;
use LeadingSystems\MerconisBundle\SearchEngine\Adapters\ClientInterface;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use Merconis\Core\ls_shop_singularStorage;

class Client implements ClientInterface
{
    private ?ElasticsearchClient $client = null;

    /*
     * Do me! Must not be hard-coded. Instead, make it configurable with a backend module!
     */
    private $host = 'https://localhost:9200';
    private $username = 'elastic';
    private $password = '7+3JVkR_XqSRohMRb-*s';
    private $cert;

    private string $productIndexName = 'products';

    public function initialize(): void
    {
        $this->client = ClientBuilder::create()
            ->setHosts([$this->host])
            ->setBasicAuthentication($this->username, $this->password)

            /*
             * Do me! Do not bypass SSL verification but instead provide a certificate.
             *  At the moment, we set the ssl verification to false only for a quick test.
             */
            // ->setCABundle($this->cert)
            ->setSSLVerification(false)

            ->build();
    }

    public function testConnection(): OperationResult
    {
        $operationResult = new OperationResult();

        try {
            $response = $this->client->ping();
            if ($response) {
                $operationResult->setSuccess(true);
                $operationResult->setMessage('Elasticsearch is reachable');
            } else {
                $operationResult->setSuccess(false);
                $operationResult->setMessage('Failed to reach Elasticsearch');
            }
        } catch (\Exception $e) {
            $operationResult->setException($e);
        }

        return $operationResult;
    }

    public function testIndex(string $indexName): OperationResult
    {
        $operationResult = new OperationResult();

        try {
            if ($this->client->indices()->exists(['index' => $indexName])) {
                $operationResult->setSuccess(true);

                if ($numDocumentsInIndex = $this->getNumDocumentsInIndex($indexName)) {
                    $numDocumentsMessage = 'Found ' . $numDocumentsInIndex . ' documents in the index.';
                } else {
                    $numDocumentsMessage = 'No documents found in the index.';
                }

                $operationResult->setMessage('The index "' . $indexName . '" exists. ' . $numDocumentsMessage);
            } else {
                $operationResult->setSuccess(false);
                $operationResult->setMessage('The index "' . $indexName . '" does not exist.');
            }
        } catch (\Exception $e) {
            $operationResult->setException($e);
        }

        return $operationResult;
    }

    public function getNumDocumentsInIndex(string $indexName): int
    {
        $params = [
            'index' => $indexName,
            'body'  => [
                'query' => [
                    'match_all' => new \stdClass(),
                ]
            ]
        ];

        $response = $this->client->search($params);
        return $response['hits']['total']['value'] ?? 0;
    }

    public function createIndex(string $indexName): OperationResult
    {
        $operationResult = new OperationResult();

        if ($this->testIndex($indexName)->getSuccess()) {
            $operationResult->setSuccess(false);
            $operationResult->setMessage('Index "' . $indexName . '" already exists!');
            return $operationResult;
        }

        $requestBody = [];

        switch ($indexName) {
            case $this->productIndexName:
                $requestBody = [
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
                            'pages'        => ['type' => 'keyword'],

                            'content_hash' => [
                                'type' => 'keyword',
                                'index' => false
                            ]
                        ],

                        'dynamic' => 'strict'
                    ],
                ];
                break;
        }

        if (empty($requestBody)) {
            $operationResult->setSuccess(false);
            $operationResult->setMessage('No mapping definition found for index "' . $indexName . '"');
            return $operationResult;
        }

        try {
            $response = $this->client->indices()->create([
                'index' => $indexName,
                'body' => $requestBody
            ]);


            if ($response['acknowledged'] ?? false) {
                $operationResult->setSuccess(true);
                $operationResult->setMessage('Index "' . $indexName . '" was created successfully');
            } else {
                $operationResult->setSuccess(false);
                $operationResult->setMessage('Failed to create the "' . $indexName . '" index');
            }
        } catch (\Exception $e) {
            $operationResult->setException($e);
        }

        return $operationResult;
    }

    public function syncProducts(int $batchSize = 10000): OperationResult
    {
        $operationResult = new OperationResult();
        $syncStatusSingularStorageKey = 'int_elasticSearchProductSyncPosition';

        if (!$this->testIndex($this->productIndexName)->getSuccess()) {
            $operationResult->setSuccess(false);
            $operationResult->setMessage('Index "' . $this->productIndexName . '" does not exist. Create it!');
            return $operationResult;
        }

        try {
            $lastId = ls_shop_singularStorage::getInstance()->{$syncStatusSingularStorageKey} ?? 0;

            $mysqlBatch = $this->getProductSyncBatchFromMySQL($lastId, $batchSize);

            if (empty($mysqlBatch)) {
                ls_shop_singularStorage::getInstance()->{$syncStatusSingularStorageKey} = 0;

                $operationResult->setSuccess(true);
                $operationResult->setMessage('No more batches to process. Sync completed. Will start over in the next run.');

                /*
                 * Do me! It is possible that there are documents in elasticsearch with ids higher than what was
                 *  in the last MySQL batch. If so, they would be orphaned. But still, they would need to be read
                 *  from elasticsearch and then processed here in order to be deleted from the elasticsearch index.
                 *  .
                 *  Possible solution:
                 *  - Read the highest id from the product table first in this method and then
                 *    detect the mysql batch that contains this record and therefore must be the last batch.
                 *  - Then pass the info that it is the last batch to the elasticsearch batch getter function
                 *    and in this function, eliminate the lte condition in this case.
                 *
                 */

                return $operationResult;
            }

            // Find the min/max id in this batch
            $batchFirstId = array_key_first($mysqlBatch);
            $batchLastId = array_key_last($mysqlBatch);

            $esBatch = $this->getProductSyncBatchFromElasticsearch($lastId, $batchLastId);

            // Insert missing/update changed
            $bulkOps = [];
            foreach ($mysqlBatch as $productId => $product) {
                if (!isset($esBatch[$productId])) {
                    // New to ES: Insert
                    $bulkOps[] = ['index' => ['_index' => $this->productIndexName, '_id' => $productId]];
                    $bulkOps[] = $product;
                } elseif ($esBatch[$productId] !== $product['content_hash']) {
                    // Changed: Update
                    $bulkOps[] = ['index' => ['_index' => $this->productIndexName, '_id' => $productId]];
                    $bulkOps[] = $product;
                }
            }

            // Delete orphans (docs in ES but not in MySQL for this batch)
            foreach ($esBatch as $productId => $hash) {
                if (!isset($mysqlBatch[$productId])) {
                    $bulkOps[] = ['delete' => ['_index' => $this->productIndexName, '_id' => $productId]];
                }
            }

            // Bulk process
            $allFailedItems = [];
            if (!empty($bulkOps)) {
                $chunks = array_chunk($bulkOps, 100);
                foreach ($chunks as $chunk) {
                    $response = $this->client->bulk(['body' => $chunk]);
                    if (isset($response['errors']) && $response['errors']) {
                        $failedItems = array_filter($response['items'], function ($item) {
                            return
                                (isset($item['index']) && isset($item['index']['error'])) ||
                                (isset($item['delete']) && isset($item['delete']['error']));
                        });
                        $allFailedItems = array_merge($allFailedItems, $failedItems);
                    }
                }
            }

            // 8. Store sync position
            ls_shop_singularStorage::getInstance()->{$syncStatusSingularStorageKey} = $batchLastId;

            $message = "Batch {$batchFirstId} - {$batchLastId} processed. "
                . count($bulkOps) . ' ES actions. ';
            if (!empty($allFailedItems)) {
                $message .= "Some failures (" . count($allFailedItems) . ")";
                $operationResult->setSuccess(false);
                $operationResult->setMessage($message);
            } else {
                $operationResult->setSuccess(true);
                $operationResult->setMessage($message);
            }
        } catch (\Exception $e) {
            $operationResult->setSuccess(false);
            $operationResult->setException($e);
        }
        return $operationResult;
    }

    private function getProductSyncBatchFromMySQL($lastIdFromPreviousBatch, $batchSize): array
    {
        $batch = [];

        $dbres_productBatch = \Database::getInstance()
            ->prepare("
                    SELECT
                        id,
                        lsShopProductCode,
                        title_de,
                        description_de,
                        pages
                    FROM tl_ls_shop_product
                    WHERE id > ?
                    ORDER BY id ASC
                ")
            ->limit($batchSize)
            ->execute($lastIdFromPreviousBatch);

        while ($dbres_productBatch->next()) {
            $product = [
                'id' => $dbres_productBatch->id,
                'product_code' => $dbres_productBatch->lsShopProductCode,
                'title' => $dbres_productBatch->title_de ?: '',
                'description' => $dbres_productBatch->description_de ?: '',
                'pages' => StringUtil::deserialize($dbres_productBatch->pages, true),
            ];
            $product['content_hash'] = $this->createProductDataHash($product);
            $batch[$dbres_productBatch->id] = $product;
        }

        return $batch;
    }

    private function getProductSyncBatchFromElasticsearch($lastIdFromPreviousBatch, $lastIdFromCurrentBatch): array
    {
        $batch = [];
        $esParams = [
            'index' => $this->productIndexName,
            'scroll' => '2m',
            'body' => [
                '_source' => ['id', 'content_hash'],
                'query' => [
                    'range' => [
                        'id' => [
                            'gt' => $lastIdFromPreviousBatch,
                            'lte' => $lastIdFromCurrentBatch,
                        ]
                    ]
                ],

                /*
                 * We fetch only chunks of 1000 documents using the Scroll API
                 */
                'size' => 1000,
                'sort' => [['id' => 'asc']]
            ]
        ];
        $response = $this->client->search($esParams);
        while (true) {
            if (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
                foreach ($response['hits']['hits'] as $hit) {
                    $batch[$hit['_source']['id']] = $hit['_source']['content_hash'] ?? null;
                }

                if (isset($response['_scroll_id'])) {
                    $scrollId = $response['_scroll_id'];
                    $response = $this->client->scroll([
                        'scroll_id' => $scrollId,
                        'scroll' => '2m'
                    ]);
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        if (isset($scrollId)) {
            $this->client->clearScroll(['scroll_id' => $scrollId]);
        }

        return $batch;
    }

    public function createProductDataHash(array $productData): string
    {
        return md5(json_encode($productData));
    }

    public function getAdapterName(): string
    {
        return basename(__DIR__);
    }

    public function getAdapterDescription(): string
    {
        return 'This SearchEngine works with a self-hosted version of Elasticsearch. Elasticsearch as a cloud service is currently not supported.';
    }
}