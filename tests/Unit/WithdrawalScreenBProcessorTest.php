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

        $GLOBALS['TL_CONFIG']['ls_shop_numDecimals'] = 2;
        $GLOBALS['TL_CONFIG']['ls_shop_currency'] = 'EUR';
        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = ',';
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = '.';
        $GLOBALS['merconis_globals']['ls_shop_currencyBeforeValue'] = false;
    }

    public function testQuantityValidationRespectsDynamicMinimumQuantity(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        self::assertTrue($processor->isValidWithdrawnQuantity(1.0, 5.0, 1.0));
        self::assertTrue($processor->isValidWithdrawnQuantity(0.1, 5.0, 0.1));
        self::assertTrue($processor->isValidWithdrawnQuantity(0.01, 5.0, 0.01));

        self::assertFalse($processor->isValidWithdrawnQuantity(0.0, 5.0, 1.0));
        self::assertFalse($processor->isValidWithdrawnQuantity(0.09, 5.0, 0.1));
        self::assertFalse($processor->isValidWithdrawnQuantity(0.009, 5.0, 0.01));
        self::assertFalse($processor->isValidWithdrawnQuantity(6.0, 5.0, 1.0));
    }

    public function testChildSnapshotContainsQuantityDecimalsAndFormattedUnitPrice(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        $snapshot = $processor->buildChildSnapshot(
            [
                'id' => 42,
                'productTitle' => 'Test Product',
                'variantTitle' => '',
                'artNr' => 'TP-001',
                'price' => '19.99',
                'quantityUnit' => 'kg',
                'quantity' => '2.5',
                'quantityDecimals' => 2,
            ],
            1.25,
            1711536870
        );

        self::assertSame(2, $snapshot['snapshotQuantityDecimals']);
        self::assertSame('19,99 EUR/kg', $snapshot['snapshotUnitPrice']);
    }

    public function testParentSnapshotSeparatesBillingAndShippingAddressWhenDeviantShippingAddressIsUsed(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        $arrOrder = [
            'id' => 123,
            'orderNr' => '2026000001',
            'orderDate' => '2026-03-20',
            'paymentMethod_title_customerLanguage' => 'PayPal',
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
            'paymentMethod_title_customerLanguage' => 'Invoice',
            'shippingMethod_title_customerLanguage' => 'UPS',
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
