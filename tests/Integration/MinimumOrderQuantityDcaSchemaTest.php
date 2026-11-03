<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class MinimumOrderQuantityDcaSchemaTest extends TestCase
{
    private const DCA_BASE_PATH = __DIR__ . '/../../src/Resources/contao/dca/';
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';
    private const CONFIG_BASE_PATH = __DIR__ . '/../../src/Resources/config/';

    public function testProductDcaContainsMinimumOrderQuantityFieldAndPalettePlacement(): void
    {
        $productDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_product.php');

        self::assertStringContainsString("'lsShopProductMinimumOrderQuantity' => [", $productDcaContents);
        self::assertStringContainsString("'rgxp' => 'numberWithDecimals'", $productDcaContents);
        self::assertStringContainsString(
            "\"decimal(12,4) NOT NULL default '0.0000'\"",
            $productDcaContents
        );
        self::assertStringContainsString(
            "lsShopProductSalesUnit,\n\t\t\tlsShopProductMinimumOrderQuantity,\n\t\t\tlsShopProductMengenvergleichUnit;",
            $productDcaContents
        );
    }

    public function testVariantDcaContainsMinimumOrderQuantityFieldAndPalettePlacement(): void
    {
        $variantDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_variant.php');

        self::assertStringContainsString("'lsShopVariantMinimumOrderQuantity' => [", $variantDcaContents);
        self::assertStringContainsString("'rgxp' => 'numberWithDecimals'", $variantDcaContents);
        self::assertStringContainsString(
            "\"decimal(12,4) NOT NULL default '0.0000'\"",
            $variantDcaContents
        );
        self::assertStringContainsString(
            "lsShopVariantSalesUnit,\n\t\t\tlsShopVariantMinimumOrderQuantity,\n\t\t\tlsShopVariantMengenvergleichUnit;",
            $variantDcaContents
        );
    }

    public function testShopSettingsDcaContainsStockHandlingFieldAndPalettePlacement(): void
    {
        $shopSettingsDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_lsShopSettings.php');

        self::assertStringContainsString("'ls_shop_minimumOrderQuantityStockHandling' => [", $shopSettingsDcaContents);
        self::assertStringContainsString("'options' => ['denyOrder', 'allowAvailableQuantity']", $shopSettingsDcaContents);
        self::assertStringContainsString(
            'ls_shop_weightUnit,ls_shop_quantityDefault,ls_shop_salesUnit,ls_shop_minimumOrderQuantityStockHandling,ls_shop_versandkostenType',
            $shopSettingsDcaContents
        );
    }

    public function testLanguageFilesContainMinimumOrderQuantityLabelsAndDescriptions(): void
    {
        $productLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_ls_shop_product.php');
        $productLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_ls_shop_product.php');
        $variantLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_ls_shop_variant.php');
        $variantLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_ls_shop_variant.php');
        $shopSettingsLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_lsShopSettings.php');
        $shopSettingsLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_lsShopSettings.php');

        self::assertStringContainsString("['lsShopProductMinimumOrderQuantity']", $productLanguageDe);
        self::assertStringContainsString('Varianten ohne eigene Mindestbestellmenge', $productLanguageDe);
        self::assertStringContainsString("['lsShopProductMinimumOrderQuantity']", $productLanguageEn);
        self::assertStringContainsString('Variants without their own minimum order quantity', $productLanguageEn);

        self::assertStringContainsString("['lsShopVariantMinimumOrderQuantity']", $variantLanguageDe);
        self::assertStringContainsString('übernimmt die Mindestbestellmenge des Hauptproduktes', $variantLanguageDe);
        self::assertStringContainsString("['lsShopVariantMinimumOrderQuantity']", $variantLanguageEn);
        self::assertStringContainsString('inherits the minimum order quantity from the main product', $variantLanguageEn);

        self::assertStringContainsString("['ls_shop_minimumOrderQuantityStockHandling']", $shopSettingsLanguageDe);
        self::assertStringContainsString('Verhalten bei Lagerbestand unter Mindestbestellmenge', $shopSettingsLanguageDe);
        self::assertStringContainsString("['ls_shop_minimumOrderQuantityStockHandling']", $shopSettingsLanguageEn);
        self::assertStringContainsString('Behavior when stock falls below minimum order quantity', $shopSettingsLanguageEn);
    }

    public function testServicesRegisterMinimumOrderQuantityMigration(): void
    {
        $servicesContents = (string) file_get_contents(self::CONFIG_BASE_PATH . 'services.yml');

        self::assertStringContainsString(
            'merconis.migration.backfill_minimum_order_quantity_migration:',
            $servicesContents
        );
        self::assertStringContainsString(
            'LeadingSystems\\MerconisBundle\\Migration\\BackfillMinimumOrderQuantityMigration',
            $servicesContents
        );
        self::assertStringContainsString(
            "- { name: contao.migration, priority: 0 }",
            $servicesContents
        );
    }
}
