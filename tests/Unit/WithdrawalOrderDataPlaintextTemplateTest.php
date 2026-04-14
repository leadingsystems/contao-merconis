<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WithdrawalOrderDataPlaintextTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_heading'] = 'Order data';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_order_number_label'] = 'Order number';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_order_date_label'] = 'Order date';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_billing_address_heading'] = 'Billing address';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_shipping_address_heading'] = 'Shipping address';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_payment_method_label'] = 'Payment method';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_shipping_method_label'] = 'Shipping method';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_address_field_labels'] = [
            'firstname' => 'First name',
            'lastname' => 'Last name',
            'street' => 'Street',
            'country_alternative' => 'Country',
            'firstname_alternative' => 'First name',
            'useDeviantShippingAddress' => '',
        ];
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_fallback'] = 'Fallback';
    }

    public function testTemplateRendersScenarioOneAsPlaintextSnapshot(): void
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
                'firstname_alternative' => 'Erika',
                'country_alternative' => 'Germany',
            ]),
            'snapshotPaymentMethod' => 'PayPal',
            'snapshotShippingMethod' => 'DHL',
        ]);

        self::assertSame(
            implode("\n", [
                'Order data',
                '',
                'Order number: ORDER-1001',
                'Order date: 2026-04-01',
                'Billing address:',
                'First name: Max',
                'Last name: Mustermann',
                'Street: Musterstr. 1',
                'Shipping address:',
                'First name: Erika',
                'Country: Germany',
                'Payment method: PayPal',
                'Shipping method: DHL',
            ]),
            $rendered
        );
        self::assertStringNotContainsString('<table', $rendered);
        self::assertStringNotContainsString('<h3', $rendered);
    }

    public function testTemplateSkipsShippingAddressWhenItMatchesBillingAddress(): void
    {
        $address = serialize([
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
        ]);

        $rendered = $this->renderTemplate([
            'scenario' => 1,
            'snapshotOrderNr' => 'ORDER-1002',
            'snapshotOrderDate' => '2026-04-02',
            'snapshotBillingAddress' => $address,
            'snapshotShippingAddress' => $address,
            'snapshotPaymentMethod' => 'Invoice',
            'snapshotShippingMethod' => 'UPS',
        ]);

        self::assertStringContainsString('Billing address:', $rendered);
        self::assertStringNotContainsString('Shipping address:', $rendered);
    }

    public function testTemplateRendersScenarioTwoFallbackWithoutHtmlMarkup(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 2,
        ]);

        self::assertSame("Order data\n\nFallback", $rendered);
        self::assertStringNotContainsString('<p', $rendered);
        self::assertStringNotContainsString('<br', $rendered);
    }

    /**
     * @param array<string, mixed> $withdrawalData
     */
    private function renderTemplate(array $withdrawalData): string
    {
        $templatePath = dirname(__DIR__, 2) . '/src/Resources/contao/templates/template_mail_withdrawal_order_data_plaintext.html5';

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
