<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenBProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalScreenBProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            !function_exists('LeadingSystems\\Helpers\\ls_mul')
            || !function_exists('LeadingSystems\\Helpers\\ls_div')
        ) {
            require_once dirname(__DIR__, 2) . '/vendor/leadingsystems/contao-helpers/src/Resources/contao/functions.php';
        }

        $GLOBALS['TL_CONFIG']['ls_shop_numDecimals'] = 2;
        $GLOBALS['TL_CONFIG']['ls_shop_currency'] = 'EUR';
        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = ',';
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = '.';
        $GLOBALS['merconis_globals']['ls_shop_currencyBeforeValue'] = false;
    }

    public function testQuantityValidationRespectsDisplayMinimumAndPieceSteps(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        self::assertTrue($processor->isValidWithdrawnQuantity(1.0, 5.0, 1.0, 0, 0));
        self::assertTrue($processor->isValidWithdrawnQuantity(5.0, 5.0, 1.0, 0, 0));
        self::assertTrue($processor->isValidWithdrawnQuantity(0.1, 5.0, 0.1, 1, 0));
        self::assertTrue($processor->isValidWithdrawnQuantity(1.1, 5.0, 0.1, 1, 0));
        self::assertTrue($processor->isValidWithdrawnQuantity(10.0, 500.0, 10.0, 1, 100));
        self::assertTrue($processor->isValidWithdrawnQuantity(150.0, 500.0, 10.0, 1, 100));
        self::assertTrue($processor->isValidWithdrawnQuantity(0.01, 2.0, 0.01, 2, 1));

        self::assertFalse($processor->isValidWithdrawnQuantity(0.0, 5.0, 1.0, 0, 0));
        self::assertFalse($processor->isValidWithdrawnQuantity(5.5, 6.0, 1.0, 0, 0));
        self::assertFalse($processor->isValidWithdrawnQuantity(0.09, 5.0, 0.1, 1, 0));
        self::assertFalse($processor->isValidWithdrawnQuantity(1.05, 5.0, 0.1, 1, 0));
        self::assertFalse($processor->isValidWithdrawnQuantity(15.0, 500.0, 10.0, 1, 100));
        self::assertFalse($processor->isValidWithdrawnQuantity(510.0, 500.0, 10.0, 1, 100));
    }

    public function testChildSnapshotStoresDisplayUnitsForSalesUnitOrders(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        $snapshot = $processor->buildChildSnapshot(
            [
                'id' => 42,
                'isVariant' => '1',
                'productTitle' => 'Testprodukt',
                'variantTitle' => 'Groesse L',
                'artNr' => 'TP-001',
                'price' => '19.99',
                'quantityUnit' => 'Stueck',
                'displayQuantityUnit' => '100 Stueck',
                'salesUnit' => 'Pack',
                'salesUnitSize' => 100,
                'quantity' => '2.5',
                'quantityDecimals' => 2,
                'extendedInfo' => [
                    '_productTitle_customerLanguage' => 'Test Product',
                    '_title_customerLanguage' => 'Size L',
                    '_quantityUnit_customerLanguage' => 'pcs',
                    '_displayQuantityUnit_customerLanguage' => '100 pcs',
                    '_salesUnit_customerLanguage' => 'pack',
                ],
            ],
            1.25,
            1711536870
        );

        self::assertSame('Testprodukt', $snapshot['snapshotProductName']);
        self::assertSame('Test Product', $snapshot['snapshotProductName_customerLanguage']);
        self::assertSame('Groesse L', $snapshot['snapshotVariantTitle']);
        self::assertSame('Size L', $snapshot['snapshotVariantTitle_customerLanguage']);
        self::assertSame('19,99 EUR/100 Stueck', $snapshot['snapshotUnitPrice']);
        self::assertSame('19,99 EUR/100 pcs', $snapshot['snapshotUnitPrice_customerLanguage']);
        self::assertSame('Pack', $snapshot['snapshotQuantityUnit']);
        self::assertSame('pack', $snapshot['snapshotQuantityUnit_customerLanguage']);
        self::assertSame(2, $snapshot['snapshotQuantityDecimals']);
        self::assertSame(100, $snapshot['snapshotSalesUnitSize']);
    }

    public function testChildSnapshotFallsBackToTopLevelValuesForLegacyOrders(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        $snapshot = $processor->buildChildSnapshot(
            [
                'id' => 43,
                'productTitle' => 'Legacy Product',
                'variantTitle' => 'Legacy Variant',
                'artNr' => 'TP-002',
                'price' => '12.50',
                'quantityUnit' => 'kg',
                'quantity' => '1',
            ],
            1.0,
            1711536871
        );

        self::assertSame('Legacy Product', $snapshot['snapshotProductName']);
        self::assertSame('Legacy Product', $snapshot['snapshotProductName_customerLanguage']);
        self::assertSame('Legacy Variant', $snapshot['snapshotVariantTitle']);
        self::assertSame('Legacy Variant', $snapshot['snapshotVariantTitle_customerLanguage']);
        self::assertSame('12,50 EUR/kg', $snapshot['snapshotUnitPrice']);
        self::assertSame('12,50 EUR/kg', $snapshot['snapshotUnitPrice_customerLanguage']);
        self::assertSame('kg', $snapshot['snapshotQuantityUnit']);
        self::assertSame('kg', $snapshot['snapshotQuantityUnit_customerLanguage']);
        self::assertSame(0, $snapshot['snapshotSalesUnitSize']);
    }

    public function testChildSnapshotDoesNotReuseProductTitleAsVariantForNonVariants(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        $snapshot = $processor->buildChildSnapshot(
            [
                'id' => 44,
                'isVariant' => '',
                'productTitle' => 'Kissen',
                'variantTitle' => '',
                'artNr' => 'KS-001',
                'price' => '12.50',
                'quantityUnit' => 'Stueck',
                'quantity' => '1',
                'extendedInfo' => [
                    '_productTitle_customerLanguage' => 'Cushion',
                    '_title_customerLanguage' => 'Cushion',
                    '_quantityUnit_customerLanguage' => 'pcs',
                ],
            ],
            1.0,
            1711536872
        );

        self::assertSame('Kissen', $snapshot['snapshotProductName']);
        self::assertSame('Cushion', $snapshot['snapshotProductName_customerLanguage']);
        self::assertSame('', $snapshot['snapshotVariantTitle']);
        self::assertSame('', $snapshot['snapshotVariantTitle_customerLanguage']);
    }

    public function testDisplayQuantitiesCanBeConvertedAndFormattedPrecisionSafely(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        self::assertSame(1.5, $processor->convertDisplayQuantityToInternalQuantity(150.0, 100));
        self::assertSame(2.0, $processor->getOrderedDisplayQuantity([
            'quantity' => '2.0',
            'displayQuantity' => '200',
            'salesUnitSize' => 0,
        ]));
        self::assertSame(200.0, $processor->getOrderedDisplayQuantity([
            'quantity' => '2.0',
            'displayQuantity' => '200',
            'salesUnitSize' => 100,
        ]));
        self::assertSame('10', $processor->getDisplayStepValue(100, 1));
        self::assertSame('0.01', $processor->getDisplayStepValue(1, 2));
        self::assertSame('150', $processor->formatDisplayQuantity(1.5, 1, 100));
    }

    public function testParentSnapshotSeparatesBillingAndShippingAddressWhenDeviantShippingAddressIsUsed(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        $arrOrder = [
            'id' => 123,
            'orderNr' => '2026000001',
            'orderDate' => '2026-03-20',
            'paymentMethod_title' => 'PayPal Fallback',
            'paymentMethod_title_customerLanguage' => 'PayPal',
            'shippingMethod_title' => 'DHL Fallback',
            'shippingMethod_title_customerLanguage' => 'DHL',
            'customerData' => [
                'personalData' => [
                    'firstname' => 'Max',
                    'lastname' => 'Mustermann',
                    'email' => 'max@example.com',
                    'street' => 'Musterstr. 1',
                    'order-note' => 'Bitte klingeln',
                    'useDeviantShippingAddress' => '1',
                    'firstname_alternative' => 'Erika',
                    'lastname_alternative' => 'Musterfrau',
                    'street_alternative' => 'Beispielweg 2',
                ],
                'shippingData' => [
                    'shippingMethodHint' => 'Packstation 123',
                ],
            ],
        ];

        $snapshot = $processor->buildParentSnapshot(
            $arrOrder,
            'W-00001',
            'Max Mustermann',
            'max@example.com',
            1711536870
        );

        self::assertTrue($processor->hasCompleteParentSnapshot($snapshot));
        self::assertSame('PayPal Fallback', $snapshot['snapshotPaymentMethod']);
        self::assertSame('PayPal', $snapshot['snapshotPaymentMethod_customerLanguage']);
        self::assertSame('DHL Fallback', $snapshot['snapshotShippingMethod']);
        self::assertSame('DHL', $snapshot['snapshotShippingMethod_customerLanguage']);
        self::assertSame(
            [
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'email' => 'max@example.com',
                'street' => 'Musterstr. 1',
            ],
            unserialize((string) $snapshot['snapshotBillingAddress'], ['allowed_classes' => false])
        );
        self::assertSame(
            [
                'firstname_alternative' => 'Erika',
                'lastname_alternative' => 'Musterfrau',
                'street_alternative' => 'Beispielweg 2',
            ],
            unserialize((string) $snapshot['snapshotShippingAddress'], ['allowed_classes' => false])
        );
    }

    public function testParentSnapshotSerializesEmptyShippingAddressWithoutDeviantShippingAddress(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        $arrOrder = [
            'id' => 124,
            'orderNr' => '2026000002',
            'orderDate' => '2026-03-21',
            'paymentMethod_title' => 'Invoice Fallback',
            'shippingMethod_title' => 'UPS Fallback',
            'customerData' => [
                'personalData' => [
                    'firstname' => 'Max',
                    'lastname' => 'Mustermann',
                    'email' => 'max@example.com',
                    'order-note' => 'Bitte klingeln',
                    'firstname_alternative' => 'Erika',
                ],
                'shippingData' => [
                    'shippingMethodHint' => 'Darf nicht im Snapshot landen',
                ],
            ],
        ];

        $snapshot = $processor->buildParentSnapshot(
            $arrOrder,
            'W-00002',
            'Max Mustermann',
            'max@example.com',
            1711536871
        );

        self::assertTrue($processor->hasCompleteParentSnapshot($snapshot));
        self::assertSame('Invoice Fallback', $snapshot['snapshotPaymentMethod']);
        self::assertSame('Invoice Fallback', $snapshot['snapshotPaymentMethod_customerLanguage']);
        self::assertSame('UPS Fallback', $snapshot['snapshotShippingMethod']);
        self::assertSame('UPS Fallback', $snapshot['snapshotShippingMethod_customerLanguage']);
        self::assertSame(
            [
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'email' => 'max@example.com',
            ],
            unserialize((string) $snapshot['snapshotBillingAddress'], ['allowed_classes' => false])
        );
        self::assertSame(
            [],
            unserialize((string) $snapshot['snapshotShippingAddress'], ['allowed_classes' => false])
        );
    }
}
