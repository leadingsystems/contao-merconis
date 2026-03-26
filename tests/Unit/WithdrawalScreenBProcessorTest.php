<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenBProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalScreenBProcessorTest extends TestCase
{
    public function testQuantityValidationAcceptsRangeWithinOrderedQuantity(): void
    {
        $processor = new WithdrawalScreenBProcessor();

        self::assertTrue($processor->isValidWithdrawnQuantity(1.0, 5.0));
        self::assertTrue($processor->isValidWithdrawnQuantity(5.0, 5.0));
        self::assertFalse($processor->isValidWithdrawnQuantity(0.0, 5.0));
        self::assertFalse($processor->isValidWithdrawnQuantity(6.0, 5.0));
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
