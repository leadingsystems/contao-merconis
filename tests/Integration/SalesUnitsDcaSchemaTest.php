<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class SalesUnitsDcaSchemaTest extends TestCase
{
    private const BASE_PATH = __DIR__ . '/../../src/Resources/contao/';
    private const DCA_BASE_PATH = self::BASE_PATH . 'dca/';
    private const LANGUAGE_BASE_PATH = self::BASE_PATH . 'languages/';
    private const CLASS_BASE_PATH = self::BASE_PATH . 'classes/';

    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['TL_DCA']['tl_ls_shop_orders_items']);
    }

    public function testOrderItemsDcaContainsSalesUnitSnapshotFields(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_orders_items.php';

        self::assertArrayHasKey('tl_ls_shop_orders_items', $GLOBALS['TL_DCA']);
        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_orders_items'];

        self::assertSame(
            "int(10) unsigned NOT NULL default '0'",
            $tableConfig['fields']['salesUnitSize']['sql']
        );
        self::assertSame(
            "decimal(12,4) NOT NULL default '0.0000'",
            $tableConfig['fields']['displayQuantity']['sql']
        );
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['displayQuantityUnit']['sql']
        );
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['salesUnit']['sql']
        );
        self::assertSame(
            "int(10) unsigned NOT NULL default '0'",
            $tableConfig['fields']['quantityDecimals']['sql']
        );
    }

    public function testProductDcaContainsSalesUnitFieldsAndPalettePlacement(): void
    {
        $productDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_product.php');

        self::assertStringContainsString("'lsShopProductSalesUnitSize' => [", $productDcaContents);
        self::assertStringContainsString("'lsShopProductSalesUnit' => [", $productDcaContents);
        self::assertStringContainsString("'rgxp' => 'digit'", $productDcaContents);
        self::assertStringContainsString(
            "\"int(10) unsigned NOT NULL default '0'\"",
            $productDcaContents
        );
        self::assertStringContainsString(
            "lsShopProductQuantityUnit,\n\t\t\tlsShopProductSalesUnitSize,\n\t\t\tlsShopProductSalesUnit,\n\t\t\tlsShopProductMengenvergleichUnit;",
            $productDcaContents
        );
    }

    public function testVariantDcaContainsSalesUnitFieldsAndPalettePlacement(): void
    {
        $variantDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_variant.php');

        self::assertStringContainsString("'lsShopVariantSalesUnitSize' => [", $variantDcaContents);
        self::assertStringContainsString("'lsShopVariantSalesUnit' => [", $variantDcaContents);
        self::assertStringContainsString("'rgxp' => 'digit'", $variantDcaContents);
        self::assertStringContainsString(
            "\"int(10) unsigned NOT NULL default '0'\"",
            $variantDcaContents
        );
        self::assertStringContainsString(
            "lsShopVariantQuantityUnit,\n\t\t\tlsShopVariantSalesUnitSize,\n\t\t\tlsShopVariantSalesUnit,\n\t\t\tlsShopVariantMengenvergleichUnit;",
            $variantDcaContents
        );
    }

    public function testProductSalesUnitDcaKeysMatchGetterAccess(): void
    {
        $productDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_product.php');
        $productClassContents = (string) file_get_contents(self::CLASS_BASE_PATH . 'ls_shop_product.php');

        self::assertStringContainsString("'lsShopProductSalesUnitSize' => [", $productDcaContents);
        self::assertStringContainsString("'lsShopProductSalesUnit' => [", $productDcaContents);
        self::assertStringContainsString("['lsShopProductSalesUnitSize'] ?? 0", $productClassContents);
        self::assertStringContainsString("['lsShopProductSalesUnit'] ?? ''", $productClassContents);
    }

    public function testVariantSalesUnitDcaKeysMatchGetterAccess(): void
    {
        $variantDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_variant.php');
        $variantClassContents = (string) file_get_contents(self::CLASS_BASE_PATH . 'ls_shop_variant.php');

        self::assertStringContainsString("'lsShopVariantSalesUnitSize' => [", $variantDcaContents);
        self::assertStringContainsString("'lsShopVariantSalesUnit' => [", $variantDcaContents);
        self::assertStringContainsString("['lsShopVariantSalesUnitSize'] ?? 0", $variantClassContents);
        self::assertStringContainsString("['lsShopVariantSalesUnit'] ?? ''", $variantClassContents);
        self::assertStringContainsString("['lsShopProductSalesUnitSize'] ?? 0", $variantClassContents);
        self::assertStringContainsString("['lsShopProductSalesUnit'] ?? ''", $variantClassContents);
    }

    public function testShopSettingsDcaContainsGlobalSalesUnitFieldAndPalettePlacement(): void
    {
        $shopSettingsDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_lsShopSettings.php');

        self::assertStringContainsString("'ls_shop_salesUnit' => [", $shopSettingsDcaContents);
        self::assertStringContainsString(
            'ls_shop_weightUnit,ls_shop_quantityDefault,ls_shop_salesUnit,ls_shop_versandkostenType',
            $shopSettingsDcaContents
        );
    }

    public function testLanguageFilesContainSalesUnitLabelsAndFallbackDescriptions(): void
    {
        $productLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_ls_shop_product.php');
        $productLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_ls_shop_product.php');
        $variantLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_ls_shop_variant.php');
        $variantLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_ls_shop_variant.php');
        $shopSettingsLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_lsShopSettings.php');
        $shopSettingsLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_lsShopSettings.php');

        self::assertStringContainsString("['lsShopProductSalesUnitSize']", $productLanguageDe);
        self::assertStringContainsString("['lsShopProductSalesUnit']", $productLanguageDe);
        self::assertStringContainsString('globale Standard-Stückbezeichnung', $productLanguageDe);
        self::assertStringContainsString("['lsShopProductSalesUnitSize']", $productLanguageEn);
        self::assertStringContainsString("['lsShopProductSalesUnit']", $productLanguageEn);
        self::assertStringContainsString('global default piece label', $productLanguageEn);

        self::assertStringContainsString("['lsShopVariantSalesUnitSize']", $variantLanguageDe);
        self::assertStringContainsString("['lsShopVariantSalesUnit']", $variantLanguageDe);
        self::assertStringContainsString('zuerst die Produktangabe', $variantLanguageDe);
        self::assertStringContainsString("['lsShopVariantSalesUnitSize']", $variantLanguageEn);
        self::assertStringContainsString("['lsShopVariantSalesUnit']", $variantLanguageEn);
        self::assertStringContainsString('product value is used first', $variantLanguageEn);

        self::assertStringContainsString("['ls_shop_salesUnit']", $shopSettingsLanguageDe);
        self::assertStringContainsString('einsprachige Standard-Stückbezeichnung', $shopSettingsLanguageDe);
        self::assertStringContainsString('Produkt- und Variantenebene', $shopSettingsLanguageDe);
        self::assertStringContainsString("['ls_shop_salesUnit']", $shopSettingsLanguageEn);
        self::assertStringContainsString('monolingual default piece label', $shopSettingsLanguageEn);
        self::assertStringContainsString('product and variant level', $shopSettingsLanguageEn);
    }
}
