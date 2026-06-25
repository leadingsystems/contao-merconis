<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

use Doctrine\DBAL\Connection;

final class FastFilterIndexVerifier
{
    public function __construct(
        private readonly Connection $databaseConnection,
    ) {
    }

    /**
     * @return array<string, list<string>>
     */
    public function verify(): array
    {
        return [
            'consistency' => $this->verifyConsistency(),
            'completeness' => $this->verifyCompleteness(),
            'smoke' => $this->verifySmokeTests(),
        ];
    }

    /**
     * @return list<string>
     */
    private function verifyConsistency(): array
    {
        $errors = [];
        $missingQueries = [
            'published product sentinel rows' => "
                SELECT COUNT(*)
                FROM `tl_ls_shop_product` `p`
                WHERE `p`.`published` = '1'
                    AND NOT EXISTS (
                        SELECT 1
                        FROM `" . FastFilterIndexBuilder::TABLE_NAME . "` `idx`
                        WHERE `idx`.`product_id` = `p`.`id`
                            AND `idx`.`variant_id` = 0
                            AND `idx`.`producer` = `p`.`lsShopProductProducer`
                            AND `idx`.`attribute_id` = 0
                            AND `idx`.`attribute_value_id` = 0
                    )
            ",
            'published product attribute rows' => "
                SELECT COUNT(*)
                FROM `tl_ls_shop_product` `p`
                INNER JOIN `tl_ls_shop_attribute_allocation` `aa`
                    ON `aa`.`pid` = `p`.`id`
                    AND `aa`.`parentIsVariant` = '0'
                INNER JOIN `tl_ls_shop_filter_fields` `ff`
                    ON `ff`.`sourceAttribute` = `aa`.`attributeID`
                    AND `ff`.`dataSource` = 'attribute'
                    AND `ff`.`published` = '1'
                LEFT JOIN `tl_ls_shop_attribute_values` `av`
                    ON `av`.`id` = `aa`.`attributeValueID`
                WHERE `p`.`published` = '1'
                    AND NOT EXISTS (
                        SELECT 1
                        FROM `" . FastFilterIndexBuilder::TABLE_NAME . "` `idx`
                        WHERE `idx`.`product_id` = `p`.`id`
                            AND `idx`.`variant_id` = 0
                            AND `idx`.`producer` = `p`.`lsShopProductProducer`
                            AND `idx`.`filter_field_id` = `ff`.`id`
                            AND `idx`.`attribute_id` = `aa`.`attributeID`
                            AND `idx`.`attribute_value_id` = `aa`.`attributeValueID`
                            AND (
                                (`idx`.`numeric_value` IS NULL AND `av`.`numericValue` IS NULL)
                                OR `idx`.`numeric_value` = `av`.`numericValue`
                            )
                    )
            ",
            'published variant sentinel rows' => "
                SELECT COUNT(*)
                FROM `tl_ls_shop_variant` `v`
                INNER JOIN `tl_ls_shop_product` `p`
                    ON `p`.`id` = `v`.`pid`
                WHERE `p`.`published` = '1'
                    AND `v`.`published` = '1'
                    AND NOT EXISTS (
                        SELECT 1
                        FROM `" . FastFilterIndexBuilder::TABLE_NAME . "` `idx`
                        WHERE `idx`.`product_id` = `v`.`pid`
                            AND `idx`.`variant_id` = `v`.`id`
                            AND `idx`.`producer` = `p`.`lsShopProductProducer`
                            AND `idx`.`attribute_id` = 0
                            AND `idx`.`attribute_value_id` = 0
                    )
            ",
            'published variant inherited product attribute rows' => "
                SELECT COUNT(*)
                FROM `tl_ls_shop_variant` `v`
                INNER JOIN `tl_ls_shop_product` `p`
                    ON `p`.`id` = `v`.`pid`
                INNER JOIN `tl_ls_shop_attribute_allocation` `aa`
                    ON `aa`.`pid` = `p`.`id`
                    AND `aa`.`parentIsVariant` = '0'
                INNER JOIN `tl_ls_shop_filter_fields` `ff`
                    ON `ff`.`sourceAttribute` = `aa`.`attributeID`
                    AND `ff`.`dataSource` = 'attribute'
                    AND `ff`.`published` = '1'
                LEFT JOIN `tl_ls_shop_attribute_values` `av`
                    ON `av`.`id` = `aa`.`attributeValueID`
                WHERE `p`.`published` = '1'
                    AND `v`.`published` = '1'
                    AND NOT EXISTS (
                        SELECT 1
                        FROM `" . FastFilterIndexBuilder::TABLE_NAME . "` `idx`
                        WHERE `idx`.`product_id` = `v`.`pid`
                            AND `idx`.`variant_id` = `v`.`id`
                            AND `idx`.`producer` = `p`.`lsShopProductProducer`
                            AND `idx`.`filter_field_id` = `ff`.`id`
                            AND `idx`.`attribute_id` = `aa`.`attributeID`
                            AND `idx`.`attribute_value_id` = `aa`.`attributeValueID`
                            AND (
                                (`idx`.`numeric_value` IS NULL AND `av`.`numericValue` IS NULL)
                                OR `idx`.`numeric_value` = `av`.`numericValue`
                            )
                    )
            ",
            'published variant own attribute rows' => "
                SELECT COUNT(*)
                FROM `tl_ls_shop_variant` `v`
                INNER JOIN `tl_ls_shop_product` `p`
                    ON `p`.`id` = `v`.`pid`
                INNER JOIN `tl_ls_shop_attribute_allocation` `aa`
                    ON `aa`.`pid` = `v`.`id`
                    AND `aa`.`parentIsVariant` = '1'
                INNER JOIN `tl_ls_shop_filter_fields` `ff`
                    ON `ff`.`sourceAttribute` = `aa`.`attributeID`
                    AND `ff`.`dataSource` = 'attribute'
                    AND `ff`.`published` = '1'
                LEFT JOIN `tl_ls_shop_attribute_values` `av`
                    ON `av`.`id` = `aa`.`attributeValueID`
                WHERE `p`.`published` = '1'
                    AND `v`.`published` = '1'
                    AND NOT EXISTS (
                        SELECT 1
                        FROM `" . FastFilterIndexBuilder::TABLE_NAME . "` `idx`
                        WHERE `idx`.`product_id` = `v`.`pid`
                            AND `idx`.`variant_id` = `v`.`id`
                            AND `idx`.`producer` = `p`.`lsShopProductProducer`
                            AND `idx`.`filter_field_id` = `ff`.`id`
                            AND `idx`.`attribute_id` = `aa`.`attributeID`
                            AND `idx`.`attribute_value_id` = `aa`.`attributeValueID`
                            AND (
                                (`idx`.`numeric_value` IS NULL AND `av`.`numericValue` IS NULL)
                                OR `idx`.`numeric_value` = `av`.`numericValue`
                            )
                    )
            ",
        ];

        foreach ($missingQueries as $label => $query) {
            $missingCount = (int) $this->databaseConnection->fetchOne($query);

            if ($missingCount > 0) {
                $errors[] = sprintf('Missing %s: %d', $label, $missingCount);
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function verifyCompleteness(): array
    {
        $errors = [];
        $missingProductCount = (int) $this->databaseConnection->fetchOne("
            SELECT COUNT(*)
            FROM `tl_ls_shop_product` `p`
            WHERE `p`.`published` = '1'
                AND NOT EXISTS (
                    SELECT 1
                    FROM `" . FastFilterIndexBuilder::TABLE_NAME . "` `idx`
                    WHERE `idx`.`product_id` = `p`.`id`
                )
        ");

        if ($missingProductCount > 0) {
            $errors[] = sprintf('Published products without index rows: %d', $missingProductCount);
        }

        $missingVariantCount = (int) $this->databaseConnection->fetchOne("
            SELECT COUNT(*)
            FROM `tl_ls_shop_variant` `v`
            INNER JOIN `tl_ls_shop_product` `p`
                ON `p`.`id` = `v`.`pid`
            WHERE `p`.`published` = '1'
                AND `v`.`published` = '1'
                AND NOT EXISTS (
                    SELECT 1
                    FROM `" . FastFilterIndexBuilder::TABLE_NAME . "` `idx`
                    WHERE `idx`.`product_id` = `v`.`pid`
                        AND `idx`.`variant_id` = `v`.`id`
                )
        ");

        if ($missingVariantCount > 0) {
            $errors[] = sprintf('Published variants without index rows: %d', $missingVariantCount);
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function verifySmokeTests(): array
    {
        $errors = [];
        $attributeSmokeError = $this->verifyAttributeSmokeTest();

        if ($attributeSmokeError !== null) {
            $errors[] = $attributeSmokeError;
        }

        $producerSmokeError = $this->verifyProducerSmokeTest();

        if ($producerSmokeError !== null) {
            $errors[] = $producerSmokeError;
        }

        return $errors;
    }

    private function verifyAttributeSmokeTest(): ?string
    {
        $sample = $this->databaseConnection->fetchAssociative("
            SELECT `attribute_id`, `attribute_value_id`
            FROM `" . FastFilterIndexBuilder::TABLE_NAME . "`
            WHERE `attribute_id` > 0
                AND `attribute_value_id` > 0
            GROUP BY `attribute_id`, `attribute_value_id`
            ORDER BY COUNT(*) DESC
            LIMIT 1
        ");

        if ($sample === false) {
            return null;
        }

        $attributeId = (int) $sample['attribute_id'];
        $attributeValueId = (int) $sample['attribute_value_id'];
        $indexedProductIds = $this->fetchIntegerColumn("
            SELECT DISTINCT `product_id`
            FROM `" . FastFilterIndexBuilder::TABLE_NAME . "`
            WHERE `attribute_id` = ?
                AND `attribute_value_id` = ?
            ORDER BY `product_id` ASC
        ", [$attributeId, $attributeValueId]);
        $sourceProductIds = $this->fetchIntegerColumn("
            SELECT DISTINCT `matched_products`.`product_id`
            FROM (
                SELECT `p`.`id` AS `product_id`
                FROM `tl_ls_shop_product` `p`
                INNER JOIN `tl_ls_shop_attribute_allocation` `aa`
                    ON `aa`.`pid` = `p`.`id`
                    AND `aa`.`parentIsVariant` = '0'
                WHERE `p`.`published` = '1'
                    AND `aa`.`attributeID` = ?
                    AND `aa`.`attributeValueID` = ?
                UNION
                SELECT `v`.`pid` AS `product_id`
                FROM `tl_ls_shop_variant` `v`
                INNER JOIN `tl_ls_shop_product` `p`
                    ON `p`.`id` = `v`.`pid`
                INNER JOIN `tl_ls_shop_attribute_allocation` `aa`
                    ON `aa`.`pid` = `v`.`id`
                    AND `aa`.`parentIsVariant` = '1'
                WHERE `p`.`published` = '1'
                    AND `v`.`published` = '1'
                    AND `aa`.`attributeID` = ?
                    AND `aa`.`attributeValueID` = ?
            ) `matched_products`
            ORDER BY `matched_products`.`product_id` ASC
        ", [$attributeId, $attributeValueId, $attributeId, $attributeValueId]);

        if ($indexedProductIds !== $sourceProductIds) {
            return sprintf(
                'Attribute smoke test mismatch for attribute %d value %d: index=%d source=%d',
                $attributeId,
                $attributeValueId,
                count($indexedProductIds),
                count($sourceProductIds)
            );
        }

        return null;
    }

    private function verifyProducerSmokeTest(): ?string
    {
        $producer = $this->databaseConnection->fetchOne("
            SELECT `producer`
            FROM `" . FastFilterIndexBuilder::TABLE_NAME . "`
            WHERE `producer` != ''
            GROUP BY `producer`
            ORDER BY COUNT(*) DESC
            LIMIT 1
        ");

        if (!is_string($producer) || $producer === '') {
            return null;
        }

        $indexedProductIds = $this->fetchIntegerColumn("
            SELECT DISTINCT `product_id`
            FROM `" . FastFilterIndexBuilder::TABLE_NAME . "`
            WHERE `producer` = ?
            ORDER BY `product_id` ASC
        ", [$producer]);
        $sourceProductIds = $this->fetchIntegerColumn("
            SELECT `id`
            FROM `tl_ls_shop_product`
            WHERE `published` = '1'
                AND `lsShopProductProducer` = ?
            ORDER BY `id` ASC
        ", [$producer]);

        if ($indexedProductIds !== $sourceProductIds) {
            return sprintf(
                'Producer smoke test mismatch for producer "%s": index=%d source=%d',
                $producer,
                count($indexedProductIds),
                count($sourceProductIds)
            );
        }

        return null;
    }

    /**
     * @param list<mixed> $parameters
     *
     * @return list<int>
     */
    private function fetchIntegerColumn(string $query, array $parameters = []): array
    {
        return array_map(
            'intval',
            $this->databaseConnection->fetchFirstColumn($query, $parameters)
        );
    }
}
