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

    public function testParentSnapshotContainsAllRequiredFields(): void
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
                ],
                'shippingData' => [
                    'firstname' => 'Erika',
                    'lastname' => 'Musterfrau',
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
    }
}
