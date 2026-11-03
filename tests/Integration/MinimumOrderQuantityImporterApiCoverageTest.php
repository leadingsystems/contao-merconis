<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class MinimumOrderQuantityImporterApiCoverageTest extends TestCase
{
    private const MERCONIS_BASE_PATH = __DIR__ . '/../../src/Resources/contao/';

    public function testProductManagementApiPreprocessorExposesMinimumOrderQuantityField(): void
    {
        $preprocessorContents = (string) file_get_contents(
            self::MERCONIS_BASE_PATH . 'classes/ls_shop_productManagementApiPreprocessor.php'
        );

        self::assertStringContainsString("'minimumOrderQuantity' => array(", $preprocessorContents);
        self::assertStringContainsString(
            "'preprocessor' => 'preprocess_minimumOrderQuantity'",
            $preprocessorContents
        );
        self::assertStringContainsString(
            'protected static function preprocess_minimumOrderQuantity(',
            $preprocessorContents
        );
    }

    public function testProductManagementApiWritesMinimumOrderQuantityForProductsAndVariants(): void
    {
        $helperContents = (string) file_get_contents(
            self::MERCONIS_BASE_PATH . 'helpers/ls_shop_productManagementApiHelper.php'
        );

        self::assertStringContainsString('`lsShopProductMinimumOrderQuantity` = ?', $helperContents);
        self::assertStringContainsString('`lsShopVariantMinimumOrderQuantity` = ?', $helperContents);
        self::assertStringContainsString("\$arr_preprocessedDataRow['minimumOrderQuantity']", $helperContents);
    }

    public function testCsvImporterWritesAndValidatesMinimumOrderQuantity(): void
    {
        $importControllerContents = (string) file_get_contents(
            self::MERCONIS_BASE_PATH . 'classes/ls_shop_importController.php'
        );

        self::assertStringContainsString("'valueInvalid_minimumOrderQuantity' => false", $importControllerContents);
        self::assertStringContainsString("case 'valueInvalid_minimumOrderQuantity':", $importControllerContents);
        self::assertStringContainsString('`lsShopProductMinimumOrderQuantity` = ?', $importControllerContents);
        self::assertStringContainsString('`lsShopVariantMinimumOrderQuantity` = ?', $importControllerContents);
        self::assertStringContainsString("\$row['minimumOrderQuantity']", $importControllerContents);
    }
}
