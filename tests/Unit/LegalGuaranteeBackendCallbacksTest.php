<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\DataContainer;
use Contao\Input;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\LegalGuaranteeBackendCallbacks;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeBackendCallbacksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['tl_ls_shop_product']['guaranteeValidationMessages'] = [
            'garan_disabled_gll_required' => 'Produkt: GLL fehlt.',
            'garan_disabled_gll_variant_override' => 'Produkt: Varianten mit override: %s.',
            'garan_disabled_invalid_duration' => 'Produkt: Ungültige Dauer %s.',
        ];
        $GLOBALS['TL_LANG']['tl_ls_shop_variant']['guaranteeValidationMessages'] = [
            'garan_invalid_mode' => 'Variante: Ungültiger Modus %s.',
            'garan_override_gll_required' => 'Variante: Elternprodukt braucht GLL.',
            'garan_disabled_invalid_duration' => 'Variante: Ungültige Dauer %s.',
        ];
    }

    protected function tearDown(): void
    {
        Input::setPost('enableGll', null);
        Input::setPost('enableGll_42', null);
        Input::resetCache();

        parent::tearDown();
    }

    public function testProductDurationValidationReturnsNormalizedStoredValue(): void
    {
        $callbacks = new LegalGuaranteeBackendCallbacks($this->createConnectionStub());

        self::assertSame(
            '30',
            $callbacks->validateProductGuaranteeDurationMonths('30', $this->createDataContainer(7))
        );
        self::assertNull(
            $callbacks->validateProductGuaranteeDurationMonths('', $this->createDataContainer(7))
        );
    }

    public function testProductDurationValidationThrowsForInvalidMonthValue(): void
    {
        $callbacks = new LegalGuaranteeBackendCallbacks($this->createConnectionStub());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Produkt: Ungültige Dauer 31.');

        $callbacks->validateProductGuaranteeDurationMonths('31', $this->createDataContainer(7));
    }

    public function testProductEnableGaranValidationUsesPostedEnableGllValue(): void
    {
        Input::setPost('enableGll', '');

        $callbacks = new LegalGuaranteeBackendCallbacks(
            $this->createConnectionStub(
                products: [
                    7 => [
                        'enableGll' => '1',
                        'enableGaran' => '',
                        'guaranteeDurationMonths' => '',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                        'lsShopProductProducer' => 'Producer',
                    ],
                ],
            )
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Produkt: GLL fehlt.');

        $callbacks->validateProductEnableGaran('1', $this->createDataContainer(7));
    }

    public function testProductEnableGaranValidationFallsBackToStoredEnableGll(): void
    {
        $callbacks = new LegalGuaranteeBackendCallbacks(
            $this->createConnectionStub(
                products: [
                    7 => [
                        'enableGll' => '',
                        'enableGaran' => '',
                        'guaranteeDurationMonths' => '',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                        'lsShopProductProducer' => 'Producer',
                    ],
                ],
            )
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Produkt: GLL fehlt.');

        $callbacks->validateProductEnableGaran('1', $this->createDataContainer(7));
    }

    public function testProductEnableGaranValidationReadsEditAllSuffix(): void
    {
        Input::setPost('enableGll_42', '');

        $callbacks = new LegalGuaranteeBackendCallbacks(
            $this->createConnectionStub(
                products: [
                    42 => [
                        'enableGll' => '1',
                        'enableGaran' => '',
                        'guaranteeDurationMonths' => '',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                        'lsShopProductProducer' => 'Producer',
                    ],
                ],
            )
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Produkt: GLL fehlt.');

        $callbacks->validateProductEnableGaran('1', $this->createDataContainer(42));
    }

    public function testProductEnableGllValidationRejectsOverrideVariants(): void
    {
        $callbacks = new LegalGuaranteeBackendCallbacks(
            $this->createConnectionStub(
                products: [
                    7 => [
                        'enableGll' => '1',
                        'enableGaran' => '',
                        'guaranteeDurationMonths' => '',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                        'lsShopProductProducer' => 'Producer',
                    ],
                ],
                relatedVariants: [
                    7 => [
                        ['id' => 11, 'title' => 'Variante A', 'lsShopVariantCode' => 'VAR-001', 'garanMode' => 'override'],
                        ['id' => 12, 'title' => 'Variante B', 'lsShopVariantCode' => 'VAR-002', 'garanMode' => 'override'],
                    ],
                ],
            )
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Produkt: Varianten mit override: VAR-001, VAR-002.');

        $callbacks->validateProductEnableGll('', $this->createDataContainer(7));
    }

    public function testVariantGaranModeValidationRejectsOverrideWithoutParentGll(): void
    {
        $callbacks = new LegalGuaranteeBackendCallbacks(
            $this->createConnectionStub(
                products: [
                    7 => [
                        'enableGll' => '',
                        'enableGaran' => '',
                        'guaranteeDurationMonths' => '',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                        'lsShopProductProducer' => 'Producer',
                    ],
                ],
                variants: [
                    15 => [
                        'id' => 15,
                        'pid' => 7,
                        'garanMode' => 'inherit',
                        'guaranteeDurationMonths' => '',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                    ],
                ],
            )
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Variante: Elternprodukt braucht GLL.');

        $callbacks->validateVariantGaranMode('override', $this->createDataContainer(15));
    }

    public function testProductGuaranteeBrandLoadUsesTrimmedProducerOnlyWhenStoredValueIsEmpty(): void
    {
        $callbacks = new LegalGuaranteeBackendCallbacks(
            $this->createConnectionStub(
                products: [
                    7 => [
                        'enableGll' => '1',
                        'enableGaran' => '',
                        'guaranteeDurationMonths' => '',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                        'lsShopProductProducer' => '  ACME  ',
                    ],
                ],
            )
        );

        self::assertSame('ACME', $callbacks->loadProductGuaranteeBrand('', $this->createDataContainer(7)));
        self::assertSame('Bereits gesetzt', $callbacks->loadProductGuaranteeBrand('Bereits gesetzt', $this->createDataContainer(7)));
    }

    public function testVariantGuaranteeBrandLoadUsesParentBrandBeforeProducer(): void
    {
        $callbacks = new LegalGuaranteeBackendCallbacks(
            $this->createConnectionStub(
                products: [
                    7 => [
                        'enableGll' => '1',
                        'enableGaran' => '1',
                        'guaranteeDurationMonths' => '36',
                        'guaranteeBrand' => 'Elternmarke',
                        'guaranteeModelIdentifier' => 'ParentModel',
                        'lsShopProductProducer' => '  ACME  ',
                    ],
                    8 => [
                        'enableGll' => '1',
                        'enableGaran' => '1',
                        'guaranteeDurationMonths' => '36',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => 'ParentModel',
                        'lsShopProductProducer' => '  ACME  ',
                    ],
                ],
                variants: [
                    15 => [
                        'id' => 15,
                        'pid' => 7,
                        'garanMode' => 'override',
                        'guaranteeDurationMonths' => '',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                    ],
                    16 => [
                        'id' => 16,
                        'pid' => 8,
                        'garanMode' => 'override',
                        'guaranteeDurationMonths' => '',
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                    ],
                ],
            )
        );

        self::assertSame('Elternmarke', $callbacks->loadVariantGuaranteeBrand('', $this->createDataContainer(15)));
        self::assertSame('ACME', $callbacks->loadVariantGuaranteeBrand('', $this->createDataContainer(16)));
    }

    private function createDataContainer(int $id): DataContainer
    {
        return new class ($id) extends DataContainer {
            public function __construct(int $id)
            {
                $this->__set('id', $id);
                $this->__set('activeRecord', (object) ['id' => $id]);
            }

            public function getPalette()
            {
                return '';
            }

            protected function save($varValue)
            {
                return $varValue;
            }
        };
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<int, array<string, mixed>> $variants
     * @param array<int, list<array<string, mixed>>> $relatedVariants
     */
    private function createConnectionStub(
        array $products = [],
        array $variants = [],
        array $relatedVariants = [],
    ): Connection {
        $connection = $this->createMock(Connection::class);

        $connection
            ->method('fetchAssociative')
            ->willReturnCallback(
                static function (string $query, array $params) use ($products, $variants): array|false {
                    $recordId = (int) ($params[0] ?? 0);

                    if (str_contains($query, 'FROM tl_ls_shop_product')) {
                        return $products[$recordId] ?? false;
                    }

                    if (str_contains($query, 'FROM tl_ls_shop_variant')) {
                        return $variants[$recordId] ?? false;
                    }

                    return false;
                }
            );

        $connection
            ->method('fetchAllAssociative')
            ->willReturnCallback(
                static function (string $query, array $params) use ($relatedVariants): array {
                    if (!str_contains($query, 'FROM tl_ls_shop_variant')) {
                        return [];
                    }

                    $productId = (int) ($params[0] ?? 0);

                    return $relatedVariants[$productId] ?? [];
                }
            );

        return $connection;
    }
}
