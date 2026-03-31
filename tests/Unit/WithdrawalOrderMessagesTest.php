<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_orderMessages;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class WithdrawalOrderMessagesTest extends TestCase
{
    public function testMessageContextReplacesWithdrawalWildcards(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithWithdrawal([
            'withdrawalId' => 'W-00077',
            'email' => 'customer@example.org',
            'withdrawalTimestamp' => 1711536870,
        ]);

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            ['Mail to ##withdrawal::email## / ##withdrawal::withdrawalId##']
        );

        self::assertSame('Mail to customer@example.org / W-00077', $result);
    }

    public function testReceiverAddressCanBeResolvedFromWithdrawalData(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithWithdrawal([
            'email' => 'customer@example.org',
            'withdrawalId' => 'W-00077',
            'withdrawalTimestamp' => 1711536870,
        ]);

        $receiverAddresses = $this->invokeProtectedMethod(
            $orderMessages,
            'getReceiverAddresses',
            [[
                'id' => 10,
                'sendToCustomerAddress1' => '1',
                'customerDataType1' => 'withdrawalData',
                'customerDataField1' => 'email',
                'sendToCustomerAddress2' => '',
                'customerDataType2' => '',
                'customerDataField2' => '',
                'sendToMemberAddress' => '',
                'sendToSpecificAddress' => '',
                'specificAddress' => '',
            ]]
        );

        self::assertSame(
            [
                'main' => 'customer@example.org',
                'bcc' => null,
            ],
            $receiverAddresses
        );
    }

    public function testReceiverAddressUsesWithdrawalDataWhenOrderExists(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            [
                'customerData' => [
                    'personalData' => [
                        'email' => 'order-customer@example.org',
                    ],
                ],
            ],
            [
                'email' => 'withdrawal-customer@example.org',
                'withdrawalId' => 'W-00077',
                'withdrawalTimestamp' => 1711536870,
            ]
        );

        $receiverAddresses = $this->invokeProtectedMethod(
            $orderMessages,
            'getReceiverAddresses',
            [[
                'id' => 11,
                'sendToCustomerAddress1' => '1',
                'customerDataType1' => 'withdrawalData',
                'customerDataField1' => 'email',
                'sendToCustomerAddress2' => '',
                'customerDataType2' => '',
                'customerDataField2' => '',
                'sendToMemberAddress' => '',
                'sendToSpecificAddress' => '',
                'specificAddress' => '',
            ]]
        );

        self::assertSame(
            [
                'main' => 'withdrawal-customer@example.org',
                'bcc' => null,
            ],
            $receiverAddresses
        );
    }

    public function testReplaceWildcardsResolvesOrderAndWithdrawalNamespacesInFullPath(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'orderNr' => 'ORDER-1001',
                'customerData' => [
                    'personalData' => [
                        'firstname' => 'Max',
                    ],
                ],
            ]),
            [
                'email' => 'withdrawal-customer@example.org',
                'withdrawalId' => 'W-00077',
                'withdrawalTimestamp' => 1711536870,
            ]
        );

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            ['Order ##orderNr## / ##personalData::firstname## / ##withdrawal::email##']
        );

        self::assertSame(
            'Order ORDER-1001 / Max / withdrawal-customer@example.org',
            $result
        );
    }

    public function testTemplateWildcardsAreResolvedWithoutOrderContext(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithWithdrawal([
            'email' => 'customer@example.org',
            'withdrawalId' => 'W-00077',
            'withdrawalTimestamp' => 1711536870,
        ]);

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            ['Begin ##template::definitely_missing_template## End']
        );

        self::assertSame('Begin  End', $result);
    }

    public function testTemplateWildcardsReceiveWithdrawalDataWithOrderContext(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'orderNr' => 'ORDER-2002',
            ]),
            [
                'email' => 'withdrawal-customer@example.org',
                'withdrawalId' => 'W-00088',
                'withdrawalTimestamp' => 1711536870,
            ]
        );

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                'Begin ##template::mail_withdrawal## End',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('mail_withdrawal', $template);
                    self::assertSame('ORDER-2002', $orderData['orderNr'] ?? null);
                    self::assertSame('W-00088', $withdrawalData['withdrawalId'] ?? null);
                    self::assertSame('withdrawal-customer@example.org', $withdrawalData['email'] ?? null);

                    return 'OID=' . ($orderData['orderNr'] ?? '') . ',WID=' . ($withdrawalData['withdrawalId'] ?? '');
                },
            ]
        );

        self::assertSame('Begin OID=ORDER-2002,WID=W-00088 End', $result);
    }

    private function createOrderMessagesInstanceWithWithdrawal(array $withdrawal): ls_shop_orderMessages
    {
        $reflectionClass = new ReflectionClass(ls_shop_orderMessages::class);
        $orderMessages = $reflectionClass->newInstanceWithoutConstructor();

        $this->setProtectedProperty($orderMessages, 'counterNr', null);
        $this->setProtectedProperty($orderMessages, 'arrOrder', null);
        $this->setProtectedProperty($orderMessages, 'obj_product', null);
        $this->setProtectedProperty($orderMessages, 'arr_memberData', null);
        $this->setProtectedProperty($orderMessages, 'arrWithdrawal', $withdrawal);
        $this->setProtectedProperty($orderMessages, 'ls_language', 'en');

        return $orderMessages;
    }

    private function createOrderMessagesInstanceWithOrderAndWithdrawal(array $order, array $withdrawal): ls_shop_orderMessages
    {
        $reflectionClass = new ReflectionClass(ls_shop_orderMessages::class);
        $orderMessages = $reflectionClass->newInstanceWithoutConstructor();

        $this->setProtectedProperty($orderMessages, 'counterNr', null);
        $this->setProtectedProperty($orderMessages, 'arrOrder', $order);
        $this->setProtectedProperty($orderMessages, 'obj_product', null);
        $this->setProtectedProperty($orderMessages, 'arr_memberData', null);
        $this->setProtectedProperty($orderMessages, 'arrWithdrawal', $withdrawal);
        $this->setProtectedProperty($orderMessages, 'ls_language', 'en');

        return $orderMessages;
    }

    private function buildOrderData(array $overrides = []): array
    {
        return array_merge(
            [
                'miscData' => [],
                'customerData' => [],
                'orderIdentificationHash' => '',
                'orderNr' => '',
                'withdrawalIdentifier' => '',
                'orderDateUnixTimestamp' => 0,
                'paymentMethod_infoAfterCheckout' => '',
                'paymentMethod_infoAfterCheckout_customerLanguage' => '',
                'shippingMethod_infoAfterCheckout' => '',
                'shippingMethod_infoAfterCheckout_customerLanguage' => '',
                'shippingTrackingNr' => '',
                'shippingTrackingUrl' => '',
            ],
            $overrides
        );
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokeProtectedMethod(object $instance, string $methodName, array $arguments): mixed
    {
        $reflectionMethod = new \ReflectionMethod($instance, $methodName);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($instance, $arguments);
    }

    private function setProtectedProperty(object $instance, string $propertyName, mixed $value): void
    {
        $reflectionProperty = new \ReflectionProperty($instance, $propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($instance, $value);
    }
}
