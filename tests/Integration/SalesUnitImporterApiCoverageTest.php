<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class SalesUnitImporterApiCoverageTest extends TestCase
{
    private const MERCONIS_BASE_PATH = __DIR__ . '/../../src/Resources/contao/';

    public function testProductManagementApiPreprocessorExposesSalesUnitFields(): void
    {
        $preprocessorContents = (string) file_get_contents(
            self::MERCONIS_BASE_PATH . 'classes/ls_shop_productManagementApiPreprocessor.php'
        );

        self::assertStringContainsString("'salesUnitSize' => array(", $preprocessorContents);
        self::assertStringContainsString("'preprocessor' => 'preprocess_salesUnitSize'", $preprocessorContents);
        self::assertStringContainsString("'salesUnit' => array(", $preprocessorContents);
        self::assertStringContainsString(
            'protected static function preprocess_salesUnitSize(',
            $preprocessorContents
        );
    }

    public function testProductManagementApiWritesSalesUnitFieldsForProductsAndVariants(): void
    {
        $helperContents = (string) file_get_contents(
            self::MERCONIS_BASE_PATH . 'helpers/ls_shop_productManagementApiHelper.php'
        );

        self::assertStringContainsString('`lsShopProductSalesUnitSize` = ?', $helperContents);
        self::assertStringContainsString('`lsShopProductSalesUnit` = ?', $helperContents);
        self::assertStringContainsString("'lsShopProductSalesUnit'", $helperContents);
        self::assertStringContainsString("\$arr_preprocessedDataRow['salesUnitSize']", $helperContents);
        self::assertStringContainsString("\$arr_preprocessedDataRow['salesUnit']", $helperContents);

        self::assertStringContainsString('`lsShopVariantSalesUnitSize` = ?', $helperContents);
        self::assertStringContainsString('`lsShopVariantSalesUnit` = ?', $helperContents);
        self::assertStringContainsString("'lsShopVariantSalesUnit'", $helperContents);
    }

    public function testCsvImporterWritesAndValidatesSalesUnitFields(): void
    {
        $importControllerContents = (string) file_get_contents(
            self::MERCONIS_BASE_PATH . 'classes/ls_shop_importController.php'
        );

        self::assertStringContainsString("'valueInvalid_salesUnit' => false", $importControllerContents);
        self::assertStringContainsString(
            "'productOrVariantValueInvalid_salesUnitSize' => false",
            $importControllerContents
        );
        self::assertStringContainsString("case 'valueInvalid_salesUnit':", $importControllerContents);
        self::assertStringContainsString(
            "case 'productOrVariantValueInvalid_salesUnitSize':",
            $importControllerContents
        );

        self::assertStringContainsString('`lsShopProductSalesUnitSize` = ?', $importControllerContents);
        self::assertStringContainsString('`lsShopProductSalesUnit` = ?', $importControllerContents);
        self::assertStringContainsString('`lsShopVariantSalesUnitSize` = ?', $importControllerContents);
        self::assertStringContainsString('`lsShopVariantSalesUnit` = ?', $importControllerContents);
        self::assertStringContainsString("'lsShopProductSalesUnit'", $importControllerContents);
        self::assertStringContainsString("'lsShopVariantSalesUnit'", $importControllerContents);
    }

    public function testExporterStillUsesGenericProductAndVariantRows(): void
    {
        $exportContents = (string) file_get_contents(
            self::MERCONIS_BASE_PATH . 'classes/ls_shop_export.php'
        );

        self::assertStringContainsString("SELECT\t\t*", $exportContents);
        self::assertStringContainsString("FROM\t\t`tl_ls_shop_product`", $exportContents);
        self::assertStringContainsString("FROM\t\t`tl_ls_shop_variant`", $exportContents);
    }
}
