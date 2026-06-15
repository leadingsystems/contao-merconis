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

    public function testSecondWithdrawalReceiverWithEmptyFieldDoesNotOverrideResolvedMainAddress(): void
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
                'id' => 12,
                'sendToCustomerAddress1' => '1',
                'customerDataType1' => 'withdrawalData',
                'customerDataField1' => 'email',
                'sendToCustomerAddress2' => '1',
                'customerDataType2' => 'withdrawalData',
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

    public function testPlaintextWithdrawalTemplateWildcardReceivesPreparedWithdrawalData(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'orderNr' => 'ORDER-3003',
            ]),
            [
                'email' => 'withdrawal-customer@example.org',
                'withdrawalId' => 'W-00303',
                'withdrawalTimestamp' => 1711536870,
            ]
        );

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                'Begin ##template::mail_withdrawal_plaintext## End',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('mail_withdrawal_plaintext', $template);
                    self::assertSame('ORDER-3003', $orderData['orderNr'] ?? null);
                    self::assertSame('W-00303', $withdrawalData['withdrawalId'] ?? null);

                    return 'PLAINTEXT-WITHDRAWAL=' . ($withdrawalData['withdrawalId'] ?? '');
                },
            ]
        );

        self::assertSame('Begin PLAINTEXT-WITHDRAWAL=W-00303 End', $result);
    }

    public function testPlaintextOrderDataTemplateWildcardReceivesPreparedWithdrawalData(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'orderNr' => 'ORDER-3004',
            ]),
            [
                'email' => 'withdrawal-customer@example.org',
                'withdrawalId' => 'W-00304',
                'withdrawalTimestamp' => 1711536870,
                'snapshotOrderNr' => 'ORDER-3004',
            ]
        );

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                'Begin ##template::mail_withdrawal_order_data_plaintext## End',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('mail_withdrawal_order_data_plaintext', $template);
                    self::assertSame('ORDER-3004', $orderData['orderNr'] ?? null);
                    self::assertSame('ORDER-3004', $withdrawalData['snapshotOrderNr'] ?? null);

                    return 'PLAINTEXT-ORDER=' . ($withdrawalData['snapshotOrderNr'] ?? '');
                },
            ]
        );

        self::assertSame('Begin PLAINTEXT-ORDER=ORDER-3004 End', $result);
    }

    public function testAlreadySentMessageTypeIsSkippedWithoutWithdrawalContext(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'messageTypesSent' => [15],
            ]),
            []
        );

        $this->setProtectedProperty($orderMessages, 'arrWithdrawal', null);

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'shouldSkipMessageTypeForCurrentContext',
            [[
                'id' => 15,
            ]]
        );

        self::assertTrue($result);
    }

    public function testAlreadySentMessageTypeIsNotSkippedInWithdrawalContext(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'messageTypesSent' => [15],
            ]),
            [
                'withdrawalId' => 'W-00099',
                'email' => 'customer@example.org',
            ]
        );

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'shouldSkipMessageTypeForCurrentContext',
            [[
                'id' => 15,
            ]]
        );

        self::assertFalse($result);
    }

    public function testWithdrawalMessageUsesExplicitMessageLanguageForLanguageFileLoading(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'customerLanguage' => 'de',
            ]),
            [
                'withdrawalId' => 'W-00100',
            ],
            'en'
        );

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'getMessageLanguageToLoad',
            []
        );

        self::assertSame('en', $result);
    }

    public function testWithdrawalWildcardsUseCustomerLanguageSnapshotVariantForCustomerMessage(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'customerLanguage' => 'de',
            ]),
            $this->buildWithdrawalData(),
            'de'
        );

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                '##withdrawal::snapshotPaymentMethod##|##withdrawal::snapshotShippingMethod##|##template::mail_withdrawal##',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('mail_withdrawal', $template);
                    self::assertSame('Rechnung', $withdrawalData['snapshotPaymentMethod'] ?? null);
                    self::assertSame('Standardversand', $withdrawalData['snapshotShippingMethod'] ?? null);
                    self::assertSame('Stuhl', $withdrawalData['items'][0]['snapshotProductName'] ?? null);
                    self::assertSame('Gross', $withdrawalData['items'][0]['snapshotVariantTitle'] ?? null);
                    self::assertSame('19,99 EUR/Stueck', $withdrawalData['items'][0]['snapshotUnitPrice'] ?? null);
                    self::assertSame('Stueck', $withdrawalData['items'][0]['snapshotQuantityUnit'] ?? null);

                    return implode('|', [
                        $withdrawalData['items'][0]['snapshotProductName'] ?? '',
                        $withdrawalData['items'][0]['snapshotVariantTitle'] ?? '',
                        $withdrawalData['items'][0]['snapshotUnitPrice'] ?? '',
                        $withdrawalData['items'][0]['snapshotQuantityUnit'] ?? '',
                    ]);
                },
            ]
        );

        self::assertSame(
            'Rechnung|Standardversand|Stuhl|Gross|19,99 EUR/Stueck|Stueck',
            $result
        );
    }

    public function testWithdrawalWildcardsUseShopFallbackSnapshotVariantForMerchantMessage(): void
    {
        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'customerLanguage' => 'de',
            ]),
            $this->buildWithdrawalData(),
            'en'
        );

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                '##withdrawal::snapshotPaymentMethod##|##withdrawal::snapshotShippingMethod##|##template::mail_withdrawal##',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('mail_withdrawal', $template);
                    self::assertSame('Invoice', $withdrawalData['snapshotPaymentMethod'] ?? null);
                    self::assertSame('Standard shipping', $withdrawalData['snapshotShippingMethod'] ?? null);
                    self::assertSame('Chair', $withdrawalData['items'][0]['snapshotProductName'] ?? null);
                    self::assertSame('Large', $withdrawalData['items'][0]['snapshotVariantTitle'] ?? null);
                    self::assertSame('19.99 EUR/piece', $withdrawalData['items'][0]['snapshotUnitPrice'] ?? null);
                    self::assertSame('piece', $withdrawalData['items'][0]['snapshotQuantityUnit'] ?? null);

                    return implode('|', [
                        $withdrawalData['items'][0]['snapshotProductName'] ?? '',
                        $withdrawalData['items'][0]['snapshotVariantTitle'] ?? '',
                        $withdrawalData['items'][0]['snapshotUnitPrice'] ?? '',
                        $withdrawalData['items'][0]['snapshotQuantityUnit'] ?? '',
                    ]);
                },
            ]
        );

        self::assertSame(
            'Invoice|Standard shipping|Chair|Large|19.99 EUR/piece|piece',
            $result
        );
    }

    public function testPreparedWithdrawalItemsContainConfigReferenceNumberFromConfiguratorSnapshot(): void
    {
        $withdrawalData = $this->buildWithdrawalData();
        $withdrawalData['items'][0]['snapshotConfiguratorReferenceNumber'] = 'A3F7B2C1';
        $withdrawalData['items'][0]['snapshotCustomizerReferenceNumber'] = '';

        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData(['customerLanguage' => 'en']),
            $withdrawalData,
            'en'
        );

        $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                '##template::mail_withdrawal##',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('A3F7B2C1', $withdrawalData['items'][0]['configReferenceNumber'] ?? '');

                    return '';
                },
            ]
        );
    }

    public function testPreparedWithdrawalItemsContainConfigReferenceNumberFromCustomizerSnapshot(): void
    {
        $withdrawalData = $this->buildWithdrawalData();
        $withdrawalData['items'][0]['snapshotConfiguratorReferenceNumber'] = '';
        $withdrawalData['items'][0]['snapshotCustomizerReferenceNumber'] = 'D9E8F0B3';

        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData(['customerLanguage' => 'en']),
            $withdrawalData,
            'en'
        );

        $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                '##template::mail_withdrawal##',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('D9E8F0B3', $withdrawalData['items'][0]['configReferenceNumber'] ?? '');

                    return '';
                },
            ]
        );
    }

    public function testPreparedWithdrawalItemsConfigReferenceNumberPrefersConfiguratorOverCustomizer(): void
    {
        $withdrawalData = $this->buildWithdrawalData();
        $withdrawalData['items'][0]['snapshotConfiguratorReferenceNumber'] = 'CONF1234';
        $withdrawalData['items'][0]['snapshotCustomizerReferenceNumber'] = 'CUST5678';

        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData(['customerLanguage' => 'en']),
            $withdrawalData,
            'en'
        );

        $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                '##template::mail_withdrawal##',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('CONF1234', $withdrawalData['items'][0]['configReferenceNumber'] ?? '');

                    return '';
                },
            ]
        );
    }

    public function testPreparedWithdrawalItemsConfigReferenceNumberIsEmptyWhenBothSnapshotFieldsAreEmpty(): void
    {
        $withdrawalData = $this->buildWithdrawalData();
        $withdrawalData['items'][0]['snapshotConfiguratorReferenceNumber'] = '';
        $withdrawalData['items'][0]['snapshotCustomizerReferenceNumber'] = '';

        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData(['customerLanguage' => 'en']),
            $withdrawalData,
            'en'
        );

        $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                '##template::mail_withdrawal##',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('', $withdrawalData['items'][0]['configReferenceNumber'] ?? '');

                    return '';
                },
            ]
        );
    }

    public function testWithdrawalWildcardsFallBackToShopFallbackVariantWhenCustomerVariantIsMissing(): void
    {
        $withdrawalData = $this->buildWithdrawalData();
        $withdrawalData['snapshotPaymentMethod_customerLanguage'] = '';
        $withdrawalData['snapshotShippingMethod_customerLanguage'] = '';
        $withdrawalData['items'][0]['snapshotProductName_customerLanguage'] = '';
        $withdrawalData['items'][0]['snapshotVariantTitle_customerLanguage'] = '';
        $withdrawalData['items'][0]['snapshotUnitPrice_customerLanguage'] = '';
        $withdrawalData['items'][0]['snapshotQuantityUnit_customerLanguage'] = '';

        $orderMessages = $this->createOrderMessagesInstanceWithOrderAndWithdrawal(
            $this->buildOrderData([
                'customerLanguage' => 'de',
            ]),
            $withdrawalData,
            'de'
        );

        $result = $this->invokeProtectedMethod(
            $orderMessages,
            'ls_replaceWildcards',
            [
                '##withdrawal::snapshotPaymentMethod##|##template::mail_withdrawal##',
                static function (string $template, $orderData, $withdrawalData): string {
                    self::assertSame('mail_withdrawal', $template);
                    self::assertSame('Invoice', $withdrawalData['snapshotPaymentMethod'] ?? null);
                    self::assertSame('Chair', $withdrawalData['items'][0]['snapshotProductName'] ?? null);
                    self::assertSame('19.99 EUR/piece', $withdrawalData['items'][0]['snapshotUnitPrice'] ?? null);

                    return implode('|', [
                        $withdrawalData['items'][0]['snapshotProductName'] ?? '',
                        $withdrawalData['items'][0]['snapshotUnitPrice'] ?? '',
                    ]);
                },
            ]
        );

        self::assertSame('Invoice|Chair|19.99 EUR/piece', $result);
    }

    private function createOrderMessagesInstanceWithWithdrawal(array $withdrawal, string $language = 'en'): ls_shop_orderMessages
    {
        $reflectionClass = new ReflectionClass(ls_shop_orderMessages::class);
        $orderMessages = $reflectionClass->newInstanceWithoutConstructor();

        $this->setProtectedProperty($orderMessages, 'counterNr', null);
        $this->setProtectedProperty($orderMessages, 'arrOrder', null);
        $this->setProtectedProperty($orderMessages, 'obj_product', null);
        $this->setProtectedProperty($orderMessages, 'arr_memberData', null);
        $this->setProtectedProperty($orderMessages, 'arrWithdrawal', $withdrawal);
        $this->setProtectedProperty($orderMessages, 'ls_language', $language);

        return $orderMessages;
    }

    private function createOrderMessagesInstanceWithOrderAndWithdrawal(
        array $order,
        array $withdrawal,
        string $language = 'en'
    ): ls_shop_orderMessages
    {
        $reflectionClass = new ReflectionClass(ls_shop_orderMessages::class);
        $orderMessages = $reflectionClass->newInstanceWithoutConstructor();

        $this->setProtectedProperty($orderMessages, 'counterNr', null);
        $this->setProtectedProperty($orderMessages, 'arrOrder', $order);
        $this->setProtectedProperty($orderMessages, 'obj_product', null);
        $this->setProtectedProperty($orderMessages, 'arr_memberData', null);
        $this->setProtectedProperty($orderMessages, 'arrWithdrawal', $withdrawal);
        $this->setProtectedProperty($orderMessages, 'ls_language', $language);

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

    private function buildWithdrawalData(): array
    {
        return [
            'withdrawalId' => 'W-00077',
            'withdrawalTimestamp' => 1711536870,
            'snapshotPaymentMethod' => 'Invoice',
            'snapshotPaymentMethod_customerLanguage' => 'Rechnung',
            'snapshotShippingMethod' => 'Standard shipping',
            'snapshotShippingMethod_customerLanguage' => 'Standardversand',
            'items' => [
                [
                    'snapshotProductName' => 'Chair',
                    'snapshotProductName_customerLanguage' => 'Stuhl',
                    'snapshotVariantTitle' => 'Large',
                    'snapshotVariantTitle_customerLanguage' => 'Gross',
                    'snapshotUnitPrice' => '19.99 EUR/piece',
                    'snapshotUnitPrice_customerLanguage' => '19,99 EUR/Stueck',
                    'snapshotQuantityUnit' => 'piece',
                    'snapshotQuantityUnit_customerLanguage' => 'Stueck',
                ],
            ],
        ];
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
