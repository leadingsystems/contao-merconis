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
        self::assertSame(
            [
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'email' => 'max@example.com',
                'street' => 'Musterstr. 1',
            ],
            unserialize((string) $parentSnapshot['snapshotBillingAddress'], ['allowed_classes' => false])
        );
        self::assertSame(
            [
                'firstname_alternative' => 'Erika',
                'lastname_alternative' => 'Musterfrau',
                'street_alternative' => 'Beispielweg 2',
            ],
            unserialize((string) $parentSnapshot['snapshotShippingAddress'], ['allowed_classes' => false])
        );

        self::assertSame(456, $childSnapshot['orderItemReference']);
        self::assertSame('Produkt A', $childSnapshot['snapshotProductName']);
        self::assertSame(5.0, $childSnapshot['snapshotOrderedQuantity']);
        self::assertSame(3.0, $childSnapshot['withdrawnQuantity']);
    }

    public function testScreenBFlowKeepsParentSnapshotCompleteWithoutDeviantShippingAddress(): void
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

        $parentSnapshot = $processor->buildParentSnapshot(
            $arrOrder,
            'W-00002',
            'Max Mustermann',
            'widerruf@example.com',
            1711536871
        );

        self::assertTrue($processor->hasCompleteParentSnapshot($parentSnapshot));
        self::assertSame(
            [
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'email' => 'max@example.com',
            ],
            unserialize((string) $parentSnapshot['snapshotBillingAddress'], ['allowed_classes' => false])
        );
        self::assertSame(
            [],
            unserialize((string) $parentSnapshot['snapshotShippingAddress'], ['allowed_classes' => false])
        );
    }
}
