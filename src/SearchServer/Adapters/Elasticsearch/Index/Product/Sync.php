<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Index\Product;

use Contao\StringUtil;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSyncInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;
use Merconis\Core\ls_shop_singularStorage;

class Sync implements CommonInterface, IndexSyncInterface
{
    use AdapterCommonTrait;

    private Client $client;
    private string $indexName = 'products';

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function sync(int $batchSize = 10000): OperationResult
    {
        $operationResult = new OperationResult();
        $syncStatusSingularStorageKey = 'int_elasticSearchProductSyncPosition';

        try {
            $highestProductIdFromMySQL = $this->getHighestProductIdFromMySQL();

            $lastId = ls_shop_singularStorage::getInstance()->{$syncStatusSingularStorageKey} ?? 0;

            $mysqlBatch = $this->getProductSyncBatchFromMySQL($lastId, $batchSize);

            if (empty($mysqlBatch)) {
                ls_shop_singularStorage::getInstance()->{$syncStatusSingularStorageKey} = 0;

                $operationResult->setSuccess(true);
                $operationResult->setMessage('No more batches to process. Sync completed. Will start over in the next run.');

                return $operationResult;
            }

            // Find the min/max id in this batch
            $batchFirstId = array_key_first($mysqlBatch);
            $batchLastId = array_key_last($mysqlBatch);

            $isLastBatch = (int) $batchLastId === $highestProductIdFromMySQL;

            $esBatch = $this->getProductSyncBatchFromElasticsearch($lastId, $batchLastId, $isLastBatch);

            // Insert missing/update changed
            $numOperations = [
                'insert' => 0,
                'update' => 0,
                'delete' => 0
            ];

            $bulkOps = [];
            foreach ($mysqlBatch as $productId => $product) {
                if (!isset($esBatch[$productId])) {
                    // New to ES: Insert
                    $bulkOps[] = ['index' => ['_index' => $this->indexName, '_id' => $productId]];
                    $bulkOps[] = $product;
                    $numOperations['insert']++;
                } elseif ($esBatch[$productId] !== $product['content_hash']) {
                    // Changed: Update
                    $bulkOps[] = ['index' => ['_index' => $this->indexName, '_id' => $productId]];
                    $bulkOps[] = $product;
                    $numOperations['update']++;
                }
            }

            // Delete orphans (docs in ES but not in MySQL for this batch)
            foreach ($esBatch as $productId => $hash) {
                if (!isset($mysqlBatch[$productId])) {
                    $bulkOps[] = ['delete' => ['_index' => $this->indexName, '_id' => $productId]];
                    $numOperations['delete']++;
                }
            }

            // Bulk process
            $allFailedItems = [];
            if (!empty($bulkOps)) {
                $chunks = array_chunk($bulkOps, 100);
                foreach ($chunks as $chunk) {
                    $response = $this->client->elasticsearchClient->bulk(['body' => $chunk]);
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

            // Store sync position
            ls_shop_singularStorage::getInstance()->{$syncStatusSingularStorageKey} = $batchLastId;

            $message = "Batch {$batchFirstId} - {$batchLastId} processed. "
                . ' Insert: ' . $numOperations['insert']
                . ', Update: ' . $numOperations['update']
                . ', Delete: ' . $numOperations['delete'];

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

    private function getHighestProductIdFromMySQL(): int
    {
        $dbres = \Database::getInstance()
            ->prepare("
                SELECT id
                FROM tl_ls_shop_product
                ORDER BY id DESC
            ")
            ->limit(1)
            ->execute();

        if (!$dbres->numRows) {
            return 0;
        }

        return (int) $dbres->first()->id;
    }

    private function getProductSyncBatchFromMySQL($lastIdFromPreviousBatch, $batchSize): array
    {
        $batch = [];

        $dbres_productBatch = \Database::getInstance()
            ->prepare("
                    SELECT
                        id,
                        lsShopProductCode,
                        lsShopProductProducer,
                        title_de,
                        keywords_de,
                        shortDescription_de,
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
                'id' => (int)$dbres_productBatch->id,
                'product_code' => $dbres_productBatch->lsShopProductCode ?: '',
                'producer' => $dbres_productBatch->lsShopProductProducer ?: '',
                'title' => $dbres_productBatch->title_de ?: '',
                'keywords' => $dbres_productBatch->keywords_de ?: '',
                'shortDescription' => $dbres_productBatch->shortDescription_de ?: '',
                'description' => $dbres_productBatch->description_de ?: '',
                'pages' => array_map('intval', StringUtil::deserialize($dbres_productBatch->pages, true))
            ];
            $product['content_hash'] = $this->createProductDataHash($product);
            $batch[$product['id']] = $product;
        }

        return $batch;
    }

    private function getProductSyncBatchFromElasticsearch($lastIdFromPreviousBatch, $lastIdFromCurrentBatch, $isLastBatch = false): array
    {
        $batch = [];

        $range = [
            'gt' => $lastIdFromPreviousBatch
        ];

        // Only add 'lte' if this is NOT the last batch
        if (!$isLastBatch) {
            $range['lte'] = $lastIdFromCurrentBatch;
        }

        $esParams = [
            'index' => $this->indexName,
            'scroll' => '2m',
            'body' => [
                '_source' => ['id', 'content_hash'],
                'query' => [
                    'range' => [
                        'id' => $range
                    ]
                ],

                /*
                 * We fetch only chunks of 1000 documents using the Scroll API
                 */
                'size' => 1000,
                'sort' => [['id' => 'asc']]
            ]
        ];
        $response = $this->client->elasticsearchClient->search($esParams);
        while (true) {
            if (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
                foreach ($response['hits']['hits'] as $hit) {
                    $batch[$hit['_source']['id']] = $hit['_source']['content_hash'] ?? null;
                }

                if (isset($response['_scroll_id'])) {
                    $scrollId = $response['_scroll_id'];
                    $response = $this->client->elasticsearchClient->scroll([
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
            $this->client->elasticsearchClient->clearScroll(['scroll_id' => $scrollId]);
        }

        return $batch;
    }

    private function createProductDataHash(array $productData): string
    {
        $tmp = $productData;
        unset($tmp['content_hash']);
        ksort($tmp);
        return md5(json_encode($tmp));
    }
}