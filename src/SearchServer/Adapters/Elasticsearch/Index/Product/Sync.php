<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Index\Product;

use Contao\StringUtil;
use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSyncInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\Elasticsearch\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;
use Merconis\Core\ls_shop_languageHelper;
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
        $allLanguages = ls_shop_languageHelper::getAllLanguages();

        // map: ES field => MySQL base field
        $multiLangFieldMapping = [
            'title' => 'title',
            'keywords' => 'keywords',
            'short_description' => 'shortDescription',
            'description' => 'description'
        ];

        // Build SELECT fields
        $fieldsToSelect = [
            'id',
            'lsShopProductCode',
            'lsShopProductProducer',
            'pages',
            'published',
            'lsShopProductIsNew',
            'lsShopProductIsOnSale',
            'lsShopProductStock',
            'lsShopProductAttributesValues'
        ];

        foreach ($multiLangFieldMapping as $esField => $dbField) {
            foreach ($allLanguages as $lang) {
                $fieldsToSelect[] = "{$dbField}_{$lang}";
            }
            $fieldsToSelect[] = $dbField; // fallback, no suffix
        }

        $fieldsToSelect = array_unique($fieldsToSelect);

        $sql = "SELECT " . implode(",\n", $fieldsToSelect) . "\nFROM tl_ls_shop_product WHERE id > ? ORDER BY id ASC";

        $dbres_productBatch = \Database::getInstance()
            ->prepare($sql)
            ->limit($batchSize)
            ->execute($lastIdFromPreviousBatch);

        $dbres_variantsForProductBatch = \Database::getInstance()
            ->prepare("
                SELECT
                    v.id,
                    v.pid,
                    v.lsShopVariantCode,
                    v.lsShopProductVariantAttributesValues,
                    v.lsShopVariantStock,
                    v.published
                FROM tl_ls_shop_variant v
                INNER JOIN (
                    SELECT id
                    FROM tl_ls_shop_product
                    WHERE id > ?
                    ORDER BY id ASC
                    LIMIT " . $batchSize . "
                ) p ON v.pid = p.id
            ")
            ->execute(
                $lastIdFromPreviousBatch
            );

         $variantsForProductBatch = [];

        while ($dbres_variantsForProductBatch->next()) {
            $variantsForProductBatch[$dbres_variantsForProductBatch->pid][] = $dbres_variantsForProductBatch->row();
        }

        while ($dbres_productBatch->next()) {
            $productHasVariants = isset($variantsForProductBatch[$dbres_productBatch->id]);

            $product = [
                'id' => (int) $dbres_productBatch->id,
                'product_code' => $dbres_productBatch->lsShopProductCode ?: '',
                'producer' => $dbres_productBatch->lsShopProductProducer ?: '',
                'pages' => array_map('intval', StringUtil::deserialize($dbres_productBatch->pages, true)),
                'is_published' => ($dbres_productBatch->published === '1'),
                'is_new' => ($dbres_productBatch->lsShopProductIsNew === '1'),
                'is_sale' => ($dbres_productBatch->lsShopProductIsOnSale === '1'),
                'stock' => (float) $dbres_productBatch->lsShopProductStock,

                /*
                 * If the product has variants, its attributes will not be indexed at product level because its attributes
                 * are inherited to all its variants therefore, during query time, the variants will deliver appropriate
                 * matches when filtering for attributes and we don't want the main product to also match itself because
                 * that would make it impossible to get the correct number of estimated matches in the aggregations/facets
                 * without performing a very performance-heavy union calculation.
                 *
                 * The following example shows the problem that we would have if we indexed attributes at the product level
                 * for products that have variants:
                 *
                 * Situation 1:
                 *
                 * - We have 4 products, 2 products with no variants and 2 products with 3 variants each.
                 * - All products have color:green.
                 * - Because of the attribute inheritance, we have
                 *   - 4 products that have color:green
                 *   - 6 variants that have color:green because they inherited it.
                 *
                 * When we get the ES  aggregations, we then find out that for color:green, we have
                 * - 4 matching products in the product-level aggs
                 * - 2 matching products in the variant-level aggs (because the aggs can identify
                 *   multiple variant matches inside a product as one product match)
                 *
                 * The information we want to get from the aggs is the number of unique products that will match when
                 * filtering for color:green. But there is no way to know whether we have to add the 4 matching products
                 * from the product-level aggs to the 2 products from the variant-level aggs or if the 2 products from
                 * the variant-level aggs are already included in the 4 products from the product-level aggs.
                 *
                 * To understand this, consider another scenario as described below.
                 *
                 * Situation 2:
                 *
                 * - We have 4 products, 2 products with no variants and 2 products with 3 variants each, exactly as before
                 * - But on a product level (meaning in the main product data) the 2 products without variants have
                 *   color:green and one of the products with variants also has color:green because all variants have
                 *   the same color and therefore color isn't set as an attribute in the variant records. But the
                 *   second product that has variants, has not set color as an attribute on the product level because
                 *   the variants have different colors. And one of the three variants of this product has color:green.
                 * - Because of the attribute inheritance, we have
                 *   - 3 products with color:green
                 *   - 4 variants with color:green, 3 of which inherited it and 1 that actually has color:green itself.
                 *
                 * Now, when we get the ES aggs, we find out, that for color:green, we have
                 * - 3 matching products in the product-level aggs
                 * - 2 matching products in the variant-level aggs (because, again, the aggs can identify
                 *   multiple variant matches inside a product as one product match)
                 *
                 * In both situations, the correct number of unique product matches for color:green would be 4 but as
                 * the second described situation shows, we can't expect the number of matching products in the
                 * product-level aggs to be what we need and we can't just add both numbers. We would need a real union.
                 *
                 * The problem with the real union is, that we would have to get product ids along with the aggs from
                 * ES to be able to determine unique product match numbers and that doesn't scale well. With millions
                 * of product and variant records and attributes that possibly could appear in most of them, this is
                 * a huge performance issue.
                 *
                 * What happens if we don't index attributes at the product level if a product has variants?
                 *
                 * Situation 1:
                 * - 2 matching products in the product-level aggs
                 *   (the 2 products with variants won't match on a product level)
                 * - 2 matching products in the variant-level aggs
                 * - Adding both numbers gives us the correct total of 4 products
                 *
                 * Situation 2:
                 * - 2 matching products in the product-level aggs
                 *   (the 2 products with variants won't match on a product level)
                 * - 2 matching products in the variant-level aggs
                 * - Adding both numbers gives us the correct total of 4 products
                 *
                 * In both situations, we can simply add the numbers from the product-level and variant-level aggs!
                 */
                'attributes' => $productHasVariants ? [] : $this->getAttributesForESPayload($dbres_productBatch->lsShopProductAttributesValues)
            ];
            // Multilanguage fields mapping, using $multiLangFieldMapping!
            foreach ($multiLangFieldMapping as $esField => $dbField) {
                $mlField = [];
                foreach ($allLanguages as $lang) {
                    $colName = "{$dbField}_{$lang}";
                    if (isset($dbres_productBatch->$colName) && $dbres_productBatch->$colName !== '' && $dbres_productBatch->$colName !== null) {
                        $mlField[$lang] = $dbres_productBatch->$colName;
                    }
                }
                // Fallback field (no suffix)
                if (isset($dbres_productBatch->$dbField) && $dbres_productBatch->$dbField !== '' && $dbres_productBatch->$dbField !== null) {
                    $mlField['fallback'] = $dbres_productBatch->$dbField;
                }
                $product[$esField] = $mlField; // Always use ES/snake_case as key
            }

            $product['variants'] = $this->getVariantsForESPayload($product['id'], $variantsForProductBatch, $dbres_productBatch->lsShopProductAttributesValues);
            $product['content_hash'] = $this->createProductDataHash($product);
            $batch[$product['id']] = $product;
        }

        return $batch;
    }

    private function getVariantsEffectiveAttributesAndValues(?string $variantAttributesAndValuesJSON = null, ?string $productAttributesAndValuesJSON = null): string
    {
        if ($variantAttributesAndValuesJSON === null || $productAttributesAndValuesJSON === null) {
            return $variantAttributesAndValuesJSON;
        }

        $productAttributesAndValues = json_decode($productAttributesAndValuesJSON, true);

        if (!is_array($productAttributesAndValues) || !count($productAttributesAndValues)) {
            /*
             * If the product does not have attributes/values, there's nothing to merge and therefore
             * we return the variant's attributes/values as the original JSON
             */
            return $variantAttributesAndValuesJSON;
        }

        $variantAttributesAndValues = json_decode($variantAttributesAndValuesJSON, true);
        if (!is_array($variantAttributesAndValues) || !count($variantAttributesAndValues)) {
            /*
             * If the variant does not have attributes/values, its effective attributes/values would be exactly
             * what the product itself has. Since this variant would never match any attribute/value requirements
             * that the product itself wouldn't match, there's no point in writing these effective attributes/values
             * to the variant. This would only bloat the index for no benefit.
             */
            return $variantAttributesAndValuesJSON;
        }

        $effectiveAttributesAndValues = array_merge($variantAttributesAndValues, $productAttributesAndValues);

        // Remove duplicate assignments
        $unique = [];
        foreach ($effectiveAttributesAndValues as $assignment) {
            if (
                is_array($assignment) &&
                isset($assignment[0]) && isset($assignment[1])
            ) {
                $unique[$assignment[0] . ':' . $assignment[1]] = $assignment;
            }
        }
        $effectiveAttributesAndValues = array_values($unique);

        $effectiveAttributesAndValuesJSON = json_encode($effectiveAttributesAndValues);
        return $effectiveAttributesAndValuesJSON;
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
        $tmp = $this->deepSortForHash($tmp);
        return md5(json_encode($tmp));
    }

    private function deepSortForHash($val): mixed
    {
        if (is_array($val)) {
            // If associative (string keys), ksort
            if ($this->isAssoc($val)) {
                ksort($val);
                foreach ($val as &$v) {
                    $v = $this->deepSortForHash($v);
                }
            } else {
                // Numerically indexed: sort by a unique subfield if present
                if (!empty($val) && isset($val[0]['variant_id'])) {
                    usort($val, function($a, $b) {
                        return strcmp($a['variant_id'], $b['variant_id']);
                    });
                    foreach ($val as &$v) {
                        $v = $this->deepSortForHash($v);
                    }
                } elseif (!empty($val) && isset($val[0]['attribute_id'], $val[0]['value_id'])) {
                    usort($val, function($a, $b) {
                        return strcmp($a['attribute_id'], $b['attribute_id'])
                            ?: strcmp($a['value_id'], $b['value_id']);
                    });
                    foreach ($val as &$v) {
                        $v = $this->deepSortForHash($v);
                    }
                } else {
                    // Default: sort values if possible, then recurse
                    sort($val);
                    foreach ($val as &$v) {
                        $v = $this->deepSortForHash($v);
                    }
                }
            }
        }
        return $val;
    }

    private function isAssoc(array $arr): bool
    {
        if ([] === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function getAttributesForESPayload(string $attributesJson): array
    {
        $attributes = json_decode($attributesJson, true);

        $attributesForES = [];

        if (!is_array($attributes)) {
            return $attributesForES;
        }

        foreach ($attributes as $attributeValueAssignment) {
            if (!is_array($attributeValueAssignment) || count($attributeValueAssignment) < 2) {
                continue;
            }
            $attributesForES[] = [
                'attribute_id' => (int)$attributeValueAssignment[0],
                'value_id'     => (int)$attributeValueAssignment[1],
            ];
        }

        return $attributesForES;
    }

    private function getVariantsForESPayload(int $productId, array $variantsForProductBatch, ?string $productAttributesAndValuesJSON = null): array
    {
        $variantsForES = [];

        if (!isset($variantsForProductBatch[$productId]) || !is_array($variantsForProductBatch[$productId])) {
            return $variantsForES;
        }

        foreach($variantsForProductBatch[$productId] as $variant) {
            $variant['lsShopProductVariantAttributesValues'] = $this->getVariantsEffectiveAttributesAndValues($variant['lsShopProductVariantAttributesValues'], $productAttributesAndValuesJSON);

            $variantsForES[] = [
                'id' => (int) $variant['id'],
                'variant_code' => $variant['lsShopVariantCode'],
                'attributes' => $this->getAttributesForESPayload($variant['lsShopProductVariantAttributesValues']),
                'stock' => (float) $variant['lsShopVariantStock'],
                'is_published' => ($variant['published'] === '1'),
            ];
        }

        return $variantsForES;
    }
}