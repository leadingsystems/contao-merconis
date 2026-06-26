<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\System;
use LeadingSystems\MerconisBundle\Helpers\MinimumOrderQuantityCalculator;
use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MinimumOrderQuantityGetterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_shop']['minimumOrderQuantityHint'] = 'Minimum order quantity: %s';
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['minimumOrderQuantityStockConflictCart'] =
            'The minimum order quantity of %s cannot be reached because of the available stock. Only %s are still available.';
        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = '.';
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = ',';
        $GLOBALS['TL_CONFIG'] = [];

        $container = new ContainerBuilder();
        $container->set(
            'merconis.session',
            new class () {
                public function getSession(): object
                {
                    return new class () {
                        public function get(string $key): array
                        {
                            return [];
                        }
                    };
                }
            }
        );
        System::setContainer($container);
    }

    public function testVariantUsesOwnMinimumOrderQuantityWhenPositive(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '1.5',
            ]
        );
        $variant = $this->createVariant(
            $product,
            [
                'lsShopVariantMinimumOrderQuantity' => '2.25',
            ]
        );

        self::assertSame('2.25', (string) $variant->_minimumOrderQuantity);
        self::assertTrue($variant->_hasMinimumOrderQuantity);
    }

    public function testVariantFallsBackToProductMinimumOrderQuantity(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '1.5',
            ]
        );
        $variant = $this->createVariant(
            $product,
            [
                'lsShopVariantMinimumOrderQuantity' => '0.0000',
            ]
        );

        self::assertSame('1.5', (string) $variant->_minimumOrderQuantity);
        self::assertTrue($variant->_hasMinimumOrderQuantity);
    }

    public function testMinimumOrderQuantityIsInactiveWhenProductAndVariantValuesAreZero(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '0.0000',
            ]
        );
        $variant = $this->createVariant(
            $product,
            [
                'lsShopVariantMinimumOrderQuantity' => '0.0000',
            ]
        );

        self::assertSame('0', (string) $variant->_minimumOrderQuantity);
        self::assertFalse($variant->_hasMinimumOrderQuantity);
        self::assertSame('0', (string) $variant->_effectiveMinimumOrderQuantity);
    }

    public function testSelectedVariantGetterUsesVariantMinimumOrderQuantity(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductQuantityDecimals' => 0,
                'lsShopProductSalesUnitSize' => 100,
                'lsShopProductMinimumOrderQuantity' => '1',
            ]
        );
        $variant = $this->createVariant(
            $product,
            [
                'lsShopVariantSalesUnitSize' => 250,
                'lsShopVariantMinimumOrderQuantity' => '0.5',
            ]
        );
        $product->ls_variants = [7 => $variant];
        $product->ls_currentVariantID = 7;

        self::assertSame('0.5', (string) $product->_minimumOrderQuantity);
        self::assertSame('250', (string) $product->_effectiveMinimumOrderQuantity);
    }

    public function testMinimumOrderQuantityHintUsesEffectiveDisplayQuantityAndSalesUnit(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductQuantityDecimals' => 0,
                'lsShopProductSalesUnitSize' => 100,
                'lsShopProductSalesUnit' => 'pcs.',
                'lsShopProductMinimumOrderQuantity' => '2.5',
            ]
        );

        self::assertSame(
            'Minimum order quantity: 300 pcs.',
            ls_shop_generalHelper::getMinimumOrderQuantityHint($product)
        );
    }

    public function testMinimumOrderQuantityHintIsEmptyWhenFeatureIsInactive(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductMinimumOrderQuantity' => '0',
            ]
        );

        self::assertSame('', ls_shop_generalHelper::getMinimumOrderQuantityHint($product));
    }

    public function testMinimumOrderQuantityStockConflictMessageUsesEffectiveMinimumAndAvailableStock(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductQuantityUnit' => 'pcs.',
                'lsShopProductQuantityDecimals' => 0,
                'lsShopProductSalesUnitSize' => 0,
                'lsShopProductSalesUnit' => 'pcs.',
                'lsShopProductMinimumOrderQuantity' => '500',
            ]
        );

        self::assertSame(
            'The minimum order quantity of 500 pcs. cannot be reached because of the available stock. Only 250 pcs. are still available.',
            ls_shop_generalHelper::getMinimumOrderQuantityStockConflictCartMessage($product, '250')
        );
    }

    public function testQuantityInputStateIgnoresEmptyQuantityDefaultConfig(): void
    {
        $product = $this->createProduct(
            [
                'lsShopProductQuantityDecimals' => 0,
                'lsShopProductSalesUnitSize' => 0,
                'lsShopProductMinimumOrderQuantity' => '0',
            ]
        );
        $product->_displayQuantityStep = '1';
        $product->_effectiveMinimumOrderQuantity = '0';
        $product->_hasMinimumOrderQuantity = false;
        $product->_quantityDecimals = 0;
        $product->_salesUnitSize = 0;
        $product->_cartKey = 'product-1';

        $GLOBALS['TL_CONFIG']['ls_shop_quantityDefault'] = '';

        self::assertSame(
            [
                'min' => '1',
                'value' => '',
                'resolvedMinimumDisplayQuantity' => null,
            ],
            ls_shop_generalHelper::getQuantityInputState($product)
        );
    }

    #[DataProvider('effectiveMinimumOrderQuantityProvider')]
    public function testEffectiveMinimumOrderQuantity(
        array $productData,
        array $variantData,
        string $expectedEffectiveMinimum
    ): void {
        $product = $this->createProduct($productData);
        $variant = $this->createVariant($product, $variantData);

        self::assertSame(
            $expectedEffectiveMinimum,
            (string) $variant->_effectiveMinimumOrderQuantity
        );
    }

    public static function effectiveMinimumOrderQuantityProvider(): array
    {
        return [
            'inactive sales-unit mode keeps quantity' => [
                [
                    'lsShopProductQuantityDecimals' => 2,
                    'lsShopProductSalesUnitSize' => 0,
                    'lsShopProductMinimumOrderQuantity' => '1.25',
                ],
                [
                    'lsShopVariantMinimumOrderQuantity' => '0',
                ],
                '1.25',
            ],
            'active sales-unit mode keeps exact multiple' => [
                [
                    'lsShopProductQuantityDecimals' => 1,
                    'lsShopProductSalesUnitSize' => 3,
                    'lsShopProductMinimumOrderQuantity' => '0.1',
                ],
                [
                    'lsShopVariantMinimumOrderQuantity' => '0',
                ],
                '0.3',
            ],
            'active sales-unit mode rounds up to next step' => [
                [
                    'lsShopProductQuantityDecimals' => 0,
                    'lsShopProductSalesUnitSize' => 100,
                    'lsShopProductMinimumOrderQuantity' => '2.5',
                ],
                [
                    'lsShopVariantMinimumOrderQuantity' => '0',
                ],
                '300',
            ],
            'four-decimal raw value rounds precision-safe' => [
                [
                    'lsShopProductQuantityDecimals' => 2,
                    'lsShopProductSalesUnitSize' => 3,
                    'lsShopProductMinimumOrderQuantity' => '0.3333',
                ],
                [
                    'lsShopVariantMinimumOrderQuantity' => '0',
                ],
                '1.02',
            ],
        ];
    }

    #[DataProvider('contextualQuantityInputStateProvider')]
    public function testContextualQuantityInputState(
        string $displayStep,
        string $effectiveMinimumQuantity,
        bool $hasActiveMinimumOrderQuantity,
        bool $cartItemAlreadyExists,
        bool $isCartContext,
        ?string $currentDisplayQuantity,
        int $quantityDecimals,
        ?string $inactiveProductPageDefaultValue,
        string $expectedMinimum,
        string $expectedValue
    ): void {
        self::assertSame(
            $expectedMinimum,
            MinimumOrderQuantityCalculator::getContextualDisplayMinimumValue(
                $displayStep,
                $effectiveMinimumQuantity,
                $hasActiveMinimumOrderQuantity,
                $cartItemAlreadyExists,
                $isCartContext,
                $currentDisplayQuantity,
                $quantityDecimals
            )
        );
        self::assertSame(
            $expectedValue,
            MinimumOrderQuantityCalculator::getContextualDisplayInitialValue(
                $displayStep,
                $effectiveMinimumQuantity,
                $hasActiveMinimumOrderQuantity,
                $cartItemAlreadyExists,
                $isCartContext,
                $currentDisplayQuantity,
                $quantityDecimals,
                $inactiveProductPageDefaultValue
            )
        );
    }

    public static function contextualQuantityInputStateProvider(): array
    {
        return [
            'product page uses effective minimum for first add' => [
                '100',
                '300',
                true,
                false,
                false,
                null,
                0,
                null,
                '300',
                '300',
            ],
            'product page falls back to step when cart item already exists' => [
                '100',
                '300',
                true,
                true,
                false,
                null,
                0,
                null,
                '100',
                '100',
            ],
            'cart keeps effective minimum when current quantity is above minimum' => [
                '100',
                '300',
                true,
                false,
                true,
                '500',
                0,
                null,
                '300',
                '500',
            ],
            'cart uses sub-minimum quantity as new minimum' => [
                '100',
                '300',
                true,
                false,
                true,
                '200',
                0,
                null,
                '200',
                '200',
            ],
            'cart rounds sub-minimum quantity up to next step' => [
                '0.25',
                '1.25',
                true,
                false,
                true,
                '0.6',
                2,
                null,
                '0.75',
                '0.6',
            ],
            'inactive minimum order quantity keeps product-page step defaults' => [
                '0.1',
                '0',
                false,
                false,
                false,
                null,
                1,
                null,
                '0.1',
                '0.1',
            ],
            'inactive minimum order quantity keeps legacy product-page default value' => [
                '0.1',
                '0',
                false,
                false,
                false,
                null,
                1,
                '5',
                '0.1',
                '5',
            ],
            'inactive minimum order quantity keeps cart value unchanged' => [
                '0.1',
                '0',
                false,
                false,
                true,
                '1.4',
                1,
                null,
                '0.1',
                '1.4',
            ],
        ];
    }

    private function createProduct(
        array $mainData = [],
        array $currentLanguageData = []
    ): ls_shop_product {
        $product = (new ReflectionClass(ls_shop_product::class))
            ->newInstanceWithoutConstructor();
        $product->mainData = array_merge(
            [
                'lsShopProductQuantityUnit' => 'kg',
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

    private function createVariant(
        ls_shop_product $product,
        array $mainData = [],
        array $currentLanguageData = []
    ): ls_shop_variant {
        $variant = (new ReflectionClass(ls_shop_variant::class))
            ->newInstanceWithoutConstructor();
        $variant->ls_objParentProduct = $product;
        $variant->mainData = array_merge(
            [
                'lsShopVariantQuantityUnit' => '',
                'lsShopVariantSalesUnitSize' => 0,
                'lsShopVariantSalesUnit' => '',
                'lsShopVariantStock' => '0',
                'lsShopVariantMinimumOrderQuantity' => '0',
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
