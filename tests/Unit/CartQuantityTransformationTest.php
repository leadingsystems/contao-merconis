<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_cartHelper;
use Merconis\Core\ls_shop_product;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CartQuantityTransformationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setEnglishLocale();
    }

    public function testPrepareQuantityForCartOperationDividesBeforeCleaningWhenSalesUnitsAreActive(): void
    {
        $product = $this->createProduct(0, 100);

        self::assertSame('5', ls_shop_cartHelper::prepareQuantityForCartOperation($product, '500'));
    }

    public function testPrepareQuantityForCartOperationUsesPrecisionSafeDivision(): void
    {
        $product = $this->createProduct(2, 10);

        self::assertSame('0.10', ls_shop_cartHelper::prepareQuantityForCartOperation($product, '1'));
    }

    public function testPrepareQuantityForCartOperationLeavesInactiveSalesUnitModeUntouched(): void
    {
        $product = $this->createProduct(1, 0);

        self::assertSame('2.5', ls_shop_cartHelper::prepareQuantityForCartOperation($product, '2.5'));
    }

    public function testPrepareQuantityForCartOperationDoesNotTransformNegativeDeleteQuantity(): void
    {
        $product = $this->createProduct(0, 100);

        self::assertSame('-1', ls_shop_cartHelper::prepareQuantityForCartOperation($product, '-1'));
    }

    public function testPrepareQuantityForCartOperationKeepsDecimalSalesUnitResultInGermanLocale(): void
    {
        $this->setGermanLocale();
        $product = $this->createProduct(1, 250);

        self::assertSame('2.2', ls_shop_cartHelper::prepareQuantityForCartOperation($product, '550'));
    }

    public function testCleanQuantitySupportsBothNormalizationModes(): void
    {
        $this->setGermanLocale();
        $product = $this->createProduct(1, 0);

        self::assertSame('2.2', ls_shop_cartHelper::cleanQuantity($product, '2.2', true));
        self::assertSame('2.2', ls_shop_cartHelper::cleanQuantity($product, '2,2'));
    }

    private function createProduct(int $quantityDecimals, int $salesUnitSize): ls_shop_product
    {
        $product = (new ReflectionClass(ls_shop_product::class))->newInstanceWithoutConstructor();
        $product->mainData = [
            'lsShopProductQuantityDecimals' => $quantityDecimals,
            'lsShopProductSalesUnitSize' => $salesUnitSize,
            'lsShopProductQuantityUnit' => 'pcs',
            'lsShopProductStock' => '0',
            'lsShopProductSalesUnit' => '',
        ];
        $product->currentLanguageData = [
            'lsShopProductQuantityUnit' => 'pcs',
            'lsShopProductSalesUnit' => '',
        ];
        $product->ls_variants = [];
        $product->ls_currentVariantID = 0;

        return $product;
    }

    private function setEnglishLocale(): void
    {
        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = '.';
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = ',';
    }

    private function setGermanLocale(): void
    {
        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = ',';
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = '.';
    }
}
