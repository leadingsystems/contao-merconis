<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_cartHelper;
use Merconis\Core\ls_shop_product;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class MinimumOrderQuantityValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText016'] = 'Quantity';
        $GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['minimumOrderQuantityFE'] =
            'Field "%s" is below the minimum order quantity. Please enter at least %s.';
        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = '.';
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = ',';
        $GLOBALS['TL_CONFIG'] ??= [];
        $GLOBALS['TL_CONFIG']['ls_shop_minimumOrderQuantityStockHandling'] = 'denyOrder';
        unset($GLOBALS['MERCONIS_HOOKS']['addToCartCustomLogic']);
    }

    public function testAddToCartRejectsFirstPositionBelowMinimumOrderQuantity(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '3',
            ]
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Please enter at least 3 pcs.');

        ls_shop_cartHelper::assertMinimumOrderQuantityForAddToCart($product, '2', false);
    }

    public function testAddToCartAllowsExistingCartKeyBelowMinimumCheck(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '3',
            ]
        );

        ls_shop_cartHelper::assertMinimumOrderQuantityForAddToCart($product, '1', true);

        self::addToAssertionCount(1);
    }

    public function testAddToCartAllowsAvailableStockWhenOptionBFallsBelowMinimumOrderQuantity(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '5',
            ]
        );
        $GLOBALS['TL_CONFIG']['ls_shop_minimumOrderQuantityStockHandling'] = 'allowAvailableQuantity';

        ls_shop_cartHelper::assertMinimumOrderQuantityForAddToCart($product, '2', false, '2');

        self::addToAssertionCount(1);
    }

    public function testAddToCartRejectsQuantityBelowAvailableStockWhenOptionBFallsBelowMinimumOrderQuantity(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '5',
            ]
        );
        $GLOBALS['TL_CONFIG']['ls_shop_minimumOrderQuantityStockHandling'] = 'allowAvailableQuantity';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Please enter at least 2 pcs.');

        ls_shop_cartHelper::assertMinimumOrderQuantityForAddToCart($product, '1', false, '2');
    }

    public function testCartUpdateRejectsLoweringBelowSubMinimumQuantity(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '3',
            ]
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Please enter at least 2 pcs.');

        ls_shop_cartHelper::assertMinimumOrderQuantityForCartUpdate($product, '1', '2');
    }

    public function testCartUpdateAllowsNonPositiveQuantityForRemoval(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '3',
            ]
        );

        ls_shop_cartHelper::assertMinimumOrderQuantityForCartUpdate($product, '0', '2');
        ls_shop_cartHelper::assertMinimumOrderQuantityForCartUpdate($product, '-1', '2');

        self::addToAssertionCount(1);
    }

    private function createProduct(array $mainData = [], array $currentLanguageData = []): ls_shop_product
    {
        $product = (new ReflectionClass(ls_shop_product::class))
            ->newInstanceWithoutConstructor();
        $product->mainData = array_merge(
            [
                'lsShopProductQuantityUnit' => 'pcs',
                'lsShopProductQuantityDecimals' => 0,
                'lsShopProductStock' => '0',
                'lsShopProductSalesUnitSize' => 0,
                'lsShopProductSalesUnit' => '',
                'lsShopProductMinimumOrderQuantity' => '0',
            ],
            $mainData
        );
        $product->currentLanguageData = array_merge(
            [
                'lsShopProductQuantityUnit' => $product->mainData['lsShopProductQuantityUnit'],
                'lsShopProductSalesUnit' => $product->mainData['lsShopProductSalesUnit'],
            ],
            $currentLanguageData
        );
        $product->ls_variants = [];
        $product->ls_currentVariantID = 0;

        return $product;
    }
}
