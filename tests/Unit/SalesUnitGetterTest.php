<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SalesUnitGetterTest extends TestCase
{
    private bool $hadGlobalSalesUnit = false;
    private mixed $previousGlobalSalesUnit = null;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_CONFIG'] ??= [];
        $this->hadGlobalSalesUnit = array_key_exists('ls_shop_salesUnit', $GLOBALS['TL_CONFIG']);
        $this->previousGlobalSalesUnit = $GLOBALS['TL_CONFIG']['ls_shop_salesUnit'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->hadGlobalSalesUnit) {
            $GLOBALS['TL_CONFIG']['ls_shop_salesUnit'] = $this->previousGlobalSalesUnit;
            return;
        }

        unset($GLOBALS['TL_CONFIG']['ls_shop_salesUnit']);
    }

    public function testInactiveSalesUnitModeFallsBackToQuantityUnit(): void
    {
        $GLOBALS['TL_CONFIG']['ls_shop_salesUnit'] = 'pcs';

        $product = $this->createProduct(
            [
                'lsShopProductQuantityUnit' => 'kg',
                'lsShopProductQuantityDecimals' => 2,
                'lsShopProductSalesUnitSize' => 0,
            ]
        );

        self::assertFalse($product->_hasSalesUnit);
        self::assertSame('kg', $product->_salesUnit);
        self::assertSame('kg', $product->_displayQuantityUnit);
        self::assertSame('0.01', (string) $product->_displayQuantityStep);
    }

    public function testSelectedVariantOverridesProductSalesUnitValues(): void
    {
        $GLOBALS['TL_CONFIG']['ls_shop_salesUnit'] = 'pcs';

        $product = $this->createProduct(
            [
                'lsShopProductQuantityUnit' => 'kg',
                'lsShopProductQuantityDecimals' => 0,
                'lsShopProductSalesUnitSize' => 100,
                'lsShopProductSalesUnit' => 'Stk.',
            ]
        );
        $variant = $this->createVariant(
            $product,
            [
                'lsShopVariantSalesUnitSize' => 250,
                'lsShopVariantSalesUnit' => 'Pack',
            ]
        );

        $product->ls_variants = [7 => $variant];
        $product->ls_currentVariantID = 7;

        self::assertSame(250, $product->_salesUnitSize);
        self::assertTrue($product->_hasSalesUnit);
        self::assertSame('Pack', $product->_salesUnit);
        self::assertSame('250 Pack', $product->_displayQuantityUnit);
    }

    public function testVariantUsesProductAndGlobalFallbacksWhenActive(): void
    {
        $GLOBALS['TL_CONFIG']['ls_shop_salesUnit'] = 'pcs';

        $product = $this->createProduct(
            [
                'lsShopProductQuantityUnit' => 'kg',
                'lsShopProductQuantityDecimals' => 0,
                'lsShopProductSalesUnitSize' => 100,
                'lsShopProductSalesUnit' => '',
            ]
        );
        $variant = $this->createVariant(
            $product,
            [
                'lsShopVariantQuantityUnit' => '',
                'lsShopVariantSalesUnitSize' => 0,
                'lsShopVariantSalesUnit' => '',
            ]
        );

        self::assertSame(100, $variant->_salesUnitSize);
        self::assertTrue($variant->_hasSalesUnit);
        self::assertSame('pcs', $variant->_salesUnit);
        self::assertSame('100 pcs', $variant->_displayQuantityUnit);
    }

    #[DataProvider('pieceStepProvider')]
    public function testDisplayQuantityStepUsesSalesUnitSizeAndQuantityDecimals(
        int $salesUnitSize,
        int $quantityDecimals,
        string $expectedStep
    ): void {
        $product = $this->createProduct(
            [
                'lsShopProductQuantityUnit' => 'kg',
                'lsShopProductQuantityDecimals' => $quantityDecimals,
                'lsShopProductSalesUnitSize' => $salesUnitSize,
            ]
        );

        self::assertSame($expectedStep, (string) $product->_displayQuantityStep);
    }

    public function testDisplayStockUsesPrecisionSafeMultiplication(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductQuantityUnit' => 'kg',
                'lsShopProductQuantityDecimals' => 1,
                'lsShopProductSalesUnitSize' => 0,
            ]
        );
        $variant = $this->createVariant(
            $product,
            [
                'lsShopVariantQuantityUnit' => 'kg',
                'lsShopVariantSalesUnitSize' => 3,
                'lsShopVariantStock' => '0.1',
            ]
        );

        self::assertSame('0.3', (string) $variant->_displayStock);
    }

    public static function pieceStepProvider(): array
    {
        return [
            'integer-only units' => [100, 0, '100'],
            'tenths of unit' => [100, 1, '10'],
            'hundredths of unit' => [100, 2, '1'],
            'inactive fallback' => [0, 2, '0.01'],
        ];
    }

    private function createProduct(array $mainData = [], array $currentLanguageData = []): ls_shop_product
    {
        $product = (new ReflectionClass(ls_shop_product::class))->newInstanceWithoutConstructor();
        $product->mainData = array_merge(
            [
                'lsShopProductQuantityUnit' => 'kg',
                'lsShopProductQuantityDecimals' => 0,
                'lsShopProductStock' => '0',
                'lsShopProductSalesUnitSize' => 0,
                'lsShopProductSalesUnit' => '',
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

    private function createVariant(
        ls_shop_product $product,
        array $mainData = [],
        array $currentLanguageData = []
    ): ls_shop_variant {
        $variant = (new ReflectionClass(ls_shop_variant::class))->newInstanceWithoutConstructor();
        $variant->ls_objParentProduct = $product;
        $variant->mainData = array_merge(
            [
                'lsShopVariantQuantityUnit' => '',
                'lsShopVariantSalesUnitSize' => 0,
                'lsShopVariantSalesUnit' => '',
                'lsShopVariantStock' => '0',
            ],
            $mainData
        );
        $variant->currentLanguageData = array_merge(
            [
                'lsShopVariantQuantityUnit' => $variant->mainData['lsShopVariantQuantityUnit'],
                'lsShopVariantSalesUnit' => $variant->mainData['lsShopVariantSalesUnit'],
            ],
            $currentLanguageData
        );

        return $variant;
    }
}
