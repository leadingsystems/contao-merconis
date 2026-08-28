<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

use Doctrine\DBAL\Connection;

final class FastFilterIndexBuilder
{
    public const TABLE_NAME = 'tl_ls_shop_fast_filter_index';

    public function __construct(
        private readonly Connection $databaseConnection,
    ) {
    }

    /**
     * Baut das materialisierte Read Model vollständig neu auf.
     *
     * @return array<string, int>
     */
    public function rebuild(): array
    {
        $this->databaseConnection->executeStatement(
            'TRUNCATE TABLE `' . self::TABLE_NAME . '`'
        );

        $productUnits = $this->databaseConnection->executeStatement("
            INSERT IGNORE INTO `" . self::TABLE_NAME . "`
                (`tstamp`, `product_id`, `variant_id`, `producer`, `filter_field_id`, `attribute_id`, `attribute_value_id`, `numeric_value`)
            SELECT      UNIX_TIMESTAMP(), `p`.`id`, 0, `p`.`lsShopProductProducer`, 0, 0, 0, NULL
            FROM        `tl_ls_shop_product` `p`
            WHERE       `p`.`published` = '1'
        ");

        $productAttributeRows = $this->databaseConnection->executeStatement("
            INSERT IGNORE INTO `" . self::TABLE_NAME . "`
                (`tstamp`, `product_id`, `variant_id`, `producer`, `filter_field_id`, `attribute_id`, `attribute_value_id`, `numeric_value`)
            SELECT      UNIX_TIMESTAMP(),
                        `p`.`id`,
                        0,
                        `p`.`lsShopProductProducer`,
                        `ff`.`id`,
                        `aa`.`attributeID`,
                        `aa`.`attributeValueID`,
                        `av`.`numericValue`
            FROM        `tl_ls_shop_product` `p`
            INNER JOIN  `tl_ls_shop_attribute_allocation` `aa`
                ON      `aa`.`pid` = `p`.`id`
                AND     `aa`.`parentIsVariant` = '0'
            INNER JOIN  `tl_ls_shop_filter_fields` `ff`
                ON      `ff`.`sourceAttribute` = `aa`.`attributeID`
                AND     `ff`.`dataSource` = 'attribute'
                AND     `ff`.`published` = '1'
            LEFT JOIN   `tl_ls_shop_attribute_values` `av`
                ON      `av`.`id` = `aa`.`attributeValueID`
            WHERE       `p`.`published` = '1'
        ");

        $variantUnits = $this->databaseConnection->executeStatement("
            INSERT IGNORE INTO `" . self::TABLE_NAME . "`
                (`tstamp`, `product_id`, `variant_id`, `producer`, `filter_field_id`, `attribute_id`, `attribute_value_id`, `numeric_value`)
            SELECT      UNIX_TIMESTAMP(), `v`.`pid`, `v`.`id`, `p`.`lsShopProductProducer`, 0, 0, 0, NULL
            FROM        `tl_ls_shop_variant` `v`
            INNER JOIN  `tl_ls_shop_product` `p`
                ON      `p`.`id` = `v`.`pid`
            WHERE       `p`.`published` = '1'
                AND     `v`.`published` = '1'
        ");

        $variantProductAttributeRows = $this->databaseConnection->executeStatement("
            INSERT IGNORE INTO `" . self::TABLE_NAME . "`
                (`tstamp`, `product_id`, `variant_id`, `producer`, `filter_field_id`, `attribute_id`, `attribute_value_id`, `numeric_value`)
            SELECT      UNIX_TIMESTAMP(),
                        `v`.`pid`,
                        `v`.`id`,
                        `p`.`lsShopProductProducer`,
                        `ff`.`id`,
                        `aa`.`attributeID`,
                        `aa`.`attributeValueID`,
                        `av`.`numericValue`
            FROM        `tl_ls_shop_variant` `v`
            INNER JOIN  `tl_ls_shop_product` `p`
                ON      `p`.`id` = `v`.`pid`
            INNER JOIN  `tl_ls_shop_attribute_allocation` `aa`
                ON      `aa`.`pid` = `p`.`id`
                AND     `aa`.`parentIsVariant` = '0'
            INNER JOIN  `tl_ls_shop_filter_fields` `ff`
                ON      `ff`.`sourceAttribute` = `aa`.`attributeID`
                AND     `ff`.`dataSource` = 'attribute'
                AND     `ff`.`published` = '1'
            LEFT JOIN   `tl_ls_shop_attribute_values` `av`
                ON      `av`.`id` = `aa`.`attributeValueID`
            WHERE       `p`.`published` = '1'
                AND     `v`.`published` = '1'
        ");

        $variantAttributeRows = $this->databaseConnection->executeStatement("
            INSERT IGNORE INTO `" . self::TABLE_NAME . "`
                (`tstamp`, `product_id`, `variant_id`, `producer`, `filter_field_id`, `attribute_id`, `attribute_value_id`, `numeric_value`)
            SELECT      UNIX_TIMESTAMP(),
                        `v`.`pid`,
                        `v`.`id`,
                        `p`.`lsShopProductProducer`,
                        `ff`.`id`,
                        `aa`.`attributeID`,
                        `aa`.`attributeValueID`,
                        `av`.`numericValue`
            FROM        `tl_ls_shop_variant` `v`
            INNER JOIN  `tl_ls_shop_product` `p`
                ON      `p`.`id` = `v`.`pid`
            INNER JOIN  `tl_ls_shop_attribute_allocation` `aa`
                ON      `aa`.`pid` = `v`.`id`
                AND     `aa`.`parentIsVariant` = '1'
            INNER JOIN  `tl_ls_shop_filter_fields` `ff`
                ON      `ff`.`sourceAttribute` = `aa`.`attributeID`
                AND     `ff`.`dataSource` = 'attribute'
                AND     `ff`.`published` = '1'
            LEFT JOIN   `tl_ls_shop_attribute_values` `av`
                ON      `av`.`id` = `aa`.`attributeValueID`
            WHERE       `p`.`published` = '1'
                AND     `v`.`published` = '1'
        ");

        return [
            'productUnits' => (int) $productUnits,
            'productAttributeRows' => (int) $productAttributeRows,
            'variantUnits' => (int) $variantUnits,
            'variantProductAttributeRows' => (int) $variantProductAttributeRows,
            'variantAttributeRows' => (int) $variantAttributeRows,
        ];
    }
}
