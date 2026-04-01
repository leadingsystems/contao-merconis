<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WithdrawalOrderDataTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_heading'] = 'Order data';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_order_number_label'] = 'Order number';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_order_date_label'] = 'Order date';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_billing_address_label'] = 'Billing address';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_shipping_address_label'] = 'Shipping address';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_payment_method_label'] = 'Payment method';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_shipping_method_label'] = 'Shipping method';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_fallback'] = 'Fallback';
    }

    public function testTemplateRendersDeserializedAddressLinesForScenarioOne(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 1,
            'snapshotOrderNr' => 'ORDER-1001',
            'snapshotOrderDate' => '2026-04-01',
            'snapshotBillingAddress' => serialize([
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'street' => 'Musterstr. 1',
            ]),
            'snapshotShippingAddress' => serialize([
                'firstname' => 'Erika',
                'lastname' => 'Musterfrau',
                'street' => 'Beispielweg 2',
            ]),
            'snapshotPaymentMethod' => 'PayPal',
            'snapshotShippingMethod' => 'DHL',
        ]);

        self::assertStringContainsString('firstname: Max', $rendered);
        self::assertStringContainsString('lastname: Mustermann', $rendered);
        self::assertStringContainsString('street: Musterstr. 1', $rendered);
        self::assertStringContainsString('firstname: Erika', $rendered);
        self::assertStringContainsString('street: Beispielweg 2', $rendered);
        self::assertStringNotContainsString('a:3:{', $rendered);
    }

    public function testTemplateSkipsAddressRowsForEmptyOrInvalidSerializedValues(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 1,
            'snapshotOrderNr' => 'ORDER-1002',
            'snapshotOrderDate' => '2026-04-02',
            'snapshotBillingAddress' => serialize([]),
            'snapshotShippingAddress' => 'not-serialized',
            'snapshotPaymentMethod' => 'Invoice',
            'snapshotShippingMethod' => 'UPS',
        ]);

        self::assertStringNotContainsString('Billing address', $rendered);
        self::assertStringNotContainsString('Shipping address', $rendered);
        self::assertStringNotContainsString('not-serialized', $rendered);
    }

    /**
     * @param array<string, mixed> $withdrawalData
     */
    private function renderTemplate(array $withdrawalData): string
    {
        $templatePath = dirname(__DIR__, 2) . '/src/Resources/contao/templates/template_mail_withdrawal_order_data.html5';

        $templateRenderer = new class($withdrawalData, $templatePath)
        {
            /** @var array<string, mixed> */
            public array $arrWithdrawal;
            private string $templatePath;

            /**
             * @param array<string, mixed> $arrWithdrawal
             */
            public function __construct(array $arrWithdrawal, string $templatePath)
            {
                $this->arrWithdrawal = $arrWithdrawal;
                $this->templatePath = $templatePath;
            }

            public function render(): string
            {
                ob_start();
                include $this->templatePath;

                return (string) ob_get_clean();
            }
        };

        return $templateRenderer->render();
    }
}
