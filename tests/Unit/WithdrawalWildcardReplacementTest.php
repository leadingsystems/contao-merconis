<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\Date;
use Merconis\Core\ls_shop_generalHelper;
use PHPUnit\Framework\TestCase;

final class WithdrawalWildcardReplacementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set('UTC');
        $GLOBALS['TL_CONFIG']['dateFormat'] = 'Y-m-d';
        $GLOBALS['TL_CONFIG']['timeFormat'] = 'H:i';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_message_link'] = 'Withdrawal link';
        unset(
            $GLOBALS['merconis_globals']['ls_shop_withdrawalPagesUrl'],
            $GLOBALS['merconis_globals']['ls_shop_withdrawalPagesID'],
            $GLOBALS['merconis_globals']['ls_shop_withdrawalPagesArray']
        );
    }

    public function testWithdrawalWildcardsAreResolvedIncludingComputedAndFormattedFields(): void
    {
        $withdrawalTimestamp = 1711536870;
        $billingAddress = serialize([
            'company' => 'Example Ltd',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'street' => 'Musterstr. 1',
            'postal' => '12345',
            'city' => 'Berlin',
        ]);
        $shippingAddress = serialize([
            'firstname' => 'Erika',
            'lastname' => 'Musterfrau',
            'street' => 'Beispielweg 2',
            'postal' => '54321',
            'city' => 'Hamburg',
        ]);

        $withdrawal = [
            'id' => 99,
            'withdrawalId' => 'W-00042',
            'withdrawalTimestamp' => $withdrawalTimestamp,
            'name' => 'Max Mustermann',
            'email' => 'max@example.com',
            'freetext' => 'Bitte alle Positionen widerrufen.',
            'snapshotOrderNr' => 'ORDER-1001',
            'snapshotOrderDate' => '2026-03-20',
            'snapshotBillingAddress' => $billingAddress,
            'snapshotShippingAddress' => $shippingAddress,
            'snapshotPaymentMethod' => 'PayPal',
            'snapshotShippingMethod' => 'DHL',
            'scenario' => '2',
        ];

        $template = implode('|', [
            '##withdrawal::id##',
            '##withdrawal::withdrawalId##',
            '##withdrawal::date##',
            '##withdrawal::time##',
            '##withdrawal::name##',
            '##withdrawal::email##',
            '##withdrawal::freetext##',
            '##withdrawal::snapshotOrderNr##',
            '##withdrawal::snapshotOrderDate##',
            '##withdrawal::snapshotBillingAddress##',
            '##withdrawal::snapshotShippingAddress##',
            '##withdrawal::snapshotPaymentMethod##',
            '##withdrawal::snapshotShippingMethod##',
            '##withdrawal::withdrawalTimestamp##',
            '##withdrawal::scenario##',
        ]);

        $resolved = ls_shop_generalHelper::ls_replaceWithdrawalWildcards($template, $withdrawal);

        $expectedBillingAddress = implode("\n", [
            'company: Example Ltd',
            'firstname: Max',
            'lastname: Mustermann',
            'street: Musterstr. 1',
            'postal: 12345',
            'city: Berlin',
        ]);
        $expectedShippingAddress = implode("\n", [
            'firstname: Erika',
            'lastname: Musterfrau',
            'street: Beispielweg 2',
            'postal: 54321',
            'city: Hamburg',
        ]);

        $expected = implode('|', [
            '99',
            'W-00042',
            Date::parse($GLOBALS['TL_CONFIG']['dateFormat'], $withdrawalTimestamp),
            Date::parse($GLOBALS['TL_CONFIG']['timeFormat'], $withdrawalTimestamp),
            'Max Mustermann',
            'max@example.com',
            'Bitte alle Positionen widerrufen.',
            'ORDER-1001',
            '2026-03-20',
            $expectedBillingAddress,
            $expectedShippingAddress,
            'PayPal',
            'DHL',
            (string) $withdrawalTimestamp,
            '2',
        ]);

        self::assertSame($expected, $resolved);
    }

    public function testOrderWildcardOrderWithdrawalIdentifierIsResolved(): void
    {
        $order = [
            'miscData' => [],
            'customerData' => [],
            'orderIdentificationHash' => '',
            'orderNr' => '',
            'withdrawalIdentifier' => 'ORDER-1001-A1B2C3',
            'orderDateUnixTimestamp' => 0,
            'paymentMethod_infoAfterCheckout' => '',
            'paymentMethod_infoAfterCheckout_customerLanguage' => '',
            'shippingMethod_infoAfterCheckout' => '',
            'shippingMethod_infoAfterCheckout_customerLanguage' => '',
            'shippingTrackingNr' => '',
            'shippingTrackingUrl' => '',
        ];

        $resolved = ls_shop_generalHelper::ls_replaceOrderWildcards(
            'Identifier: ##orderWithdrawalIdentifier##',
            $order
        );

        self::assertSame('Identifier: ORDER-1001-A1B2C3', $resolved);
    }

    public function testBuildWithdrawalLinkTagCreatesExpectedAnchor(): void
    {
        $resolved = ls_shop_generalHelper::buildWithdrawalLinkTag(
            'https://example.com/withdrawal',
            'ORDER-1001-A1B2C3',
            'Withdrawal link'
        );

        self::assertSame(
            '<a href="https://example.com/withdrawal?wid=ORDER-1001-A1B2C3" rel="noopener noreferrer">Withdrawal link</a>',
            $resolved
        );
    }

    public function testOrderWithdrawalUrlWildcardIsResolvedWithoutMarkup(): void
    {
        $GLOBALS['merconis_globals']['ls_shop_withdrawalPagesUrl'] = 'withdrawal';
        $GLOBALS['merconis_globals']['ls_shop_withdrawalPagesID'] = 17;
        $GLOBALS['merconis_globals']['ls_shop_withdrawalPagesArray'] = ['id' => 17, 'alias' => 'withdrawal'];

        $order = [
            'miscData' => ['domain' => 'https://example.com/'],
            'customerData' => [],
            'orderIdentificationHash' => '',
            'orderNr' => '',
            'withdrawalIdentifier' => 'ORDER-1001-A1B2C3',
            'orderDateUnixTimestamp' => 0,
            'paymentMethod_infoAfterCheckout' => '',
            'paymentMethod_infoAfterCheckout_customerLanguage' => '',
            'shippingMethod_infoAfterCheckout' => '',
            'shippingMethod_infoAfterCheckout_customerLanguage' => '',
            'shippingTrackingNr' => '',
            'shippingTrackingUrl' => '',
        ];

        $resolved = ls_shop_generalHelper::ls_replaceOrderWildcards(
            'URL: ##orderWithdrawalUrl##',
            $order
        );

        self::assertSame(
            'URL: https://example.com/withdrawal?wid=ORDER-1001-A1B2C3',
            $resolved
        );
        self::assertStringNotContainsString('<a ', $resolved);
    }

    public function testOrderWildcardCleanupKeepsWithdrawalNamespaceTokens(): void
    {
        $order = [
            'miscData' => [],
            'customerData' => [
                'personalData' => [
                    'firstname' => 'Max',
                ],
            ],
            'orderIdentificationHash' => '',
            'orderNr' => 'ORDER-1001',
            'withdrawalIdentifier' => '',
            'orderDateUnixTimestamp' => 0,
            'paymentMethod_infoAfterCheckout' => '',
            'paymentMethod_infoAfterCheckout_customerLanguage' => '',
            'shippingMethod_infoAfterCheckout' => '',
            'shippingMethod_infoAfterCheckout_customerLanguage' => '',
            'shippingTrackingNr' => '',
            'shippingTrackingUrl' => '',
        ];

        $resolved = ls_shop_generalHelper::ls_replaceOrderWildcards(
            'Order ##orderNr##, Name ##personalData::firstname##, Withdrawal ##withdrawal::email##',
            $order
        );

        self::assertSame(
            'Order ORDER-1001, Name Max, Withdrawal ##withdrawal::email##',
            $resolved
        );
    }

    public function testOrderWithdrawalLinkWildcardIsRemovedByCleanupWhenIdentifierIsMissing(): void
    {
        $order = [
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
        ];

        $resolved = ls_shop_generalHelper::ls_replaceOrderWildcards(
            'Before ##orderWithdrawalLink## After',
            $order
        );

        self::assertSame('Before  After', $resolved);
    }

    public function testOrderWithdrawalUrlWildcardIsRemovedByCleanupWhenIdentifierIsMissing(): void
    {
        $order = [
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
        ];

        $resolved = ls_shop_generalHelper::ls_replaceOrderWildcards(
            'Before ##orderWithdrawalUrl## After',
            $order
        );

        self::assertSame('Before  After', $resolved);
    }

    public function testTemplateWildcardRendererReceivesWithdrawalData(): void
    {
        $templateRendererWasCalled = false;
        $withdrawal = [
            'withdrawalId' => 'W-00088',
            'email' => 'customer@example.org',
        ];

        $resolved = ls_shop_generalHelper::ls_replaceTemplateWildcards(
            'Start ##template::mail_withdrawal## End',
            null,
            $withdrawal,
            static function (string $template, $orderData, $withdrawalData) use (&$templateRendererWasCalled): string {
                $templateRendererWasCalled = true;
                self::assertSame('mail_withdrawal', $template);
                self::assertNull($orderData);
                self::assertSame('W-00088', $withdrawalData['withdrawalId'] ?? null);
                self::assertSame('customer@example.org', $withdrawalData['email'] ?? null);

                return 'ID=' . ($withdrawalData['withdrawalId'] ?? '') . ',MAIL=' . ($withdrawalData['email'] ?? '');
            }
        );

        self::assertTrue($templateRendererWasCalled);
        self::assertSame('Start ID=W-00088,MAIL=customer@example.org End', $resolved);
    }
}
