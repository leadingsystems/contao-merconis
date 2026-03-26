<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenBProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalScreenBFlowTest extends TestCase
{
    public function testScreenBFlowBuildsConsistentParentAndChildSnapshots(): void
    {
        $processor = new WithdrawalScreenBProcessor();
        $withdrawalTimestamp = 1711536870;

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
                    'firstname' => 'Max',
                    'lastname' => 'Mustermann',
                ],
            ],
        ];

        $orderItem = [
            'id' => 456,
            'productTitle' => 'Produkt A',
            'variantTitle' => 'Variante M',
            'artNr' => 'ART-001',
            'price' => '12,50 EUR',
            'quantityUnit' => 'Stueck',
            'quantity' => 5.0,
        ];

        $parentSnapshot = $processor->buildParentSnapshot(
            $arrOrder,
            'W-00001',
            'Max Mustermann',
            'widerruf@example.com',
            $withdrawalTimestamp
        );
        $childSnapshot = $processor->buildChildSnapshot($orderItem, 3.0, $withdrawalTimestamp);

        self::assertTrue($processor->hasCompleteParentSnapshot($parentSnapshot));
        self::assertSame(123, $parentSnapshot['orderReference']);
        self::assertSame('2026000001', $parentSnapshot['snapshotOrderNr']);
        self::assertSame('PayPal', $parentSnapshot['snapshotPaymentMethod']);

        self::assertSame(456, $childSnapshot['orderItemReference']);
        self::assertSame('Produkt A', $childSnapshot['snapshotProductName']);
        self::assertSame(5.0, $childSnapshot['snapshotOrderedQuantity']);
        self::assertSame(3.0, $childSnapshot['withdrawnQuantity']);
    }
}
