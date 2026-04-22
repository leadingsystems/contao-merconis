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
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_billing_address_heading'] = 'Billing address';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_shipping_address_heading'] = 'Shipping address';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_payment_method_label'] = 'Payment method';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_shipping_method_label'] = 'Shipping method';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_order_data_address_field_labels'] = [
            'firstname' => 'First name',
            'lastname' => 'Last name',
            'street' => 'Street',
            'useDeviantShippingAddress' => '',
            'firstname_alternative' => 'First name',
            'country_alternative' => 'Country',
        ];
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
                'country_alternative' => 'Germany',
            ]),
            'snapshotPaymentMethod' => 'PayPal',
            'snapshotShippingMethod' => 'DHL',
        ]);

        self::assertStringContainsString('First name: Max', $rendered);
        self::assertStringContainsString('Last name: Mustermann', $rendered);
        self::assertStringContainsString('Street: Musterstr. 1', $rendered);
        self::assertStringContainsString('First name: Erika', $rendered);
        self::assertStringContainsString('Street: Beispielweg 2', $rendered);
        self::assertStringContainsString('Country: Germany', $rendered);
        self::assertStringContainsString('Billing address', $rendered);
        self::assertStringContainsString('Shipping address', $rendered);
        self::assertStringNotContainsString('a:3:{', $rendered);
    }

    public function testTemplateUsesOriginalKeyNameAsFallbackForUnknownAddressFields(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 1,
            'snapshotOrderNr' => 'ORDER-1003',
            'snapshotOrderDate' => '2026-04-03',
            'snapshotBillingAddress' => serialize([
                'firstname' => 'Max',
                'unknown_key' => 'Custom value',
            ]),
            'snapshotShippingAddress' => serialize([]),
            'snapshotPaymentMethod' => 'Invoice',
            'snapshotShippingMethod' => 'UPS',
        ]);

        self::assertStringContainsString('First name: Max', $rendered);
        self::assertStringContainsString('unknown_key: Custom value', $rendered);
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

    public function testTemplateSkipsDashValuesAndFieldsWithEmptyConfiguredLabel(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 1,
            'snapshotOrderNr' => 'ORDER-1004',
            'snapshotOrderDate' => '2026-04-04',
            'snapshotBillingAddress' => serialize([
                'firstname' => 'Max',
            ]),
            'snapshotShippingAddress' => serialize([
                'useDeviantShippingAddress' => '1',
                'country_alternative' => '-',
                'firstname_alternative' => 'Erika',
            ]),
            'snapshotPaymentMethod' => 'Invoice',
            'snapshotShippingMethod' => 'UPS',
        ]);

        self::assertStringContainsString('First name: Erika', $rendered);
        self::assertStringNotContainsString('useDeviantShippingAddress', $rendered);
        self::assertStringNotContainsString('Country: -', $rendered);
    }

    public function testTemplateRendersOnlyBillingAddressHeadingWhenShippingAddressIsEmptyAfterFiltering(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 1,
            'snapshotOrderNr' => 'ORDER-1005',
            'snapshotOrderDate' => '2026-04-05',
            'snapshotBillingAddress' => serialize([
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
            ]),
            'snapshotShippingAddress' => serialize([
                'country_alternative' => '-',
            ]),
            'snapshotPaymentMethod' => 'Invoice',
            'snapshotShippingMethod' => 'UPS',
        ]);

        self::assertStringContainsString('Billing address', $rendered);
        self::assertStringNotContainsString('Shipping address', $rendered);
    }

    public function testTemplateLeftAlignsAndTopAlignsAllOrderDataLabels(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 1,
            'snapshotOrderNr' => 'ORDER-1006',
            'snapshotOrderDate' => '2026-04-06',
            'snapshotBillingAddress' => serialize([
                'firstname' => 'Max',
            ]),
            'snapshotShippingAddress' => serialize([
                'firstname_alternative' => 'Erika',
            ]),
            'snapshotPaymentMethod' => 'Invoice',
            'snapshotShippingMethod' => 'UPS',
        ]);

        self::assertSame(6, substr_count($rendered, '<th style="text-align: left; vertical-align: top;">'));
    }

    public function testTemplateRendersFallbackForScenarioTwoWithoutAddressHeadings(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 2,
            'snapshotBillingAddress' => serialize([
                'firstname' => 'Max',
            ]),
            'snapshotShippingAddress' => serialize([
                'firstname_alternative' => 'Erika',
            ]),
        ]);

        self::assertStringContainsString('Fallback', $rendered);
        self::assertStringNotContainsString('Billing address', $rendered);
        self::assertStringNotContainsString('Shipping address', $rendered);
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
