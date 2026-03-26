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
