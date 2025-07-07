<?php

namespace LeadingSystems\MerconisBundle\SearchEngine\Adapters\Elasticsearch;

use Contao\StringUtil;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use Merconis\Core\ls_shop_singularStorage;

class ProductSync
{
    private Client $elasticsearchAdapterClient;

    public function setClient(Client $elasticsearchAdapterClient): void
    {
        $this->elasticsearchAdapterClient = $elasticsearchAdapterClient;
    }

    public function performSync(int $batchSize = 10000): OperationResult
    {
        $operationResult = new OperationResult();
        $syncStatusSingularStorageKey = 'int_elasticSearchProductSyncPosition';

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
            $numOperations = [
                'insert' => 0,
                'update' => 0,
                'delete' => 0
            ];

            $bulkOps = [];
            foreach ($mysqlBatch as $productId => $product) {
                if (!isset($esBatch[$productId])) {
                    // New to ES: Insert
                    $bulkOps[] = ['index' => ['_index' => $this->elasticsearchAdapterClient->productIndexName, '_id' => $productId]];
                    $bulkOps[] = $product;
                    $numOperations['insert']++;
                } elseif ($esBatch[$productId] !== $product['content_hash']) {
                    // Changed: Update
                    $bulkOps[] = ['index' => ['_index' => $this->elasticsearchAdapterClient->productIndexName, '_id' => $productId]];
                    $bulkOps[] = $product;
                    $numOperations['update']++;
                }
            }

            // Delete orphans (docs in ES but not in MySQL for this batch)
            foreach ($esBatch as $productId => $hash) {
                if (!isset($mysqlBatch[$productId])) {
                    $bulkOps[] = ['delete' => ['_index' => $this->elasticsearchAdapterClient->productIndexName, '_id' => $productId]];
                    $numOperations['delete']++;
                }
            }

            // Bulk process
            $allFailedItems = [];
            if (!empty($bulkOps)) {
                $chunks = array_chunk($bulkOps, 100);
                foreach ($chunks as $chunk) {
                    $response = $this->elasticsearchAdapterClient->client->bulk(['body' => $chunk]);
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
            'index' => $this->elasticsearchAdapterClient->productIndexName,
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
        $response = $this->elasticsearchAdapterClient->client->search($esParams);
        while (true) {
            if (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
                foreach ($response['hits']['hits'] as $hit) {
                    $batch[$hit['_source']['id']] = $hit['_source']['content_hash'] ?? null;
                }

                if (isset($response['_scroll_id'])) {
                    $scrollId = $response['_scroll_id'];
                    $response = $this->elasticsearchAdapterClient->client->scroll([
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
            $this->elasticsearchAdapterClient->client->clearScroll(['scroll_id' => $scrollId]);
        }

        return $batch;
    }

    public function createProductDataHash(array $productData): string
    {
        return md5(json_encode($productData));
    }
}