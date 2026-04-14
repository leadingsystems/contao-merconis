<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WithdrawalItemsPlaintextTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_heading'] = 'Withdrawn items';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_freetext_heading'] = 'Contract identification details';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_table_header_product_number'] = 'Product number';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_table_header_unit_price'] = 'Unit price';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_table_header_quantity'] = 'Quantity';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_quantity_partial'] = '%s of %s';
    }

    public function testTemplateRendersScenarioOneAsPlaintextBlocks(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 1,
            'items' => [
                [
                    'snapshotProductName' => 'T-Shirt',
                    'snapshotVariantTitle' => 'Blue / XL',
                    'snapshotProductNumber' => 'TS-01',
                    'snapshotUnitPrice' => '19.99 EUR',
                    'snapshotOrderedQuantity' => '3',
                    'withdrawnQuantity' => '1',
                    'snapshotQuantityUnit' => 'pcs',
                ],
                [
                    'snapshotProductName' => 'Hoodie',
                    'snapshotVariantTitle' => '',
                    'snapshotProductNumber' => 'HD-02',
                    'snapshotUnitPrice' => '39.99 EUR',
                    'snapshotOrderedQuantity' => '2',
                    'withdrawnQuantity' => '2',
                    'snapshotQuantityUnit' => '',
                ],
            ],
        ]);

        self::assertSame(
            implode("\n", [
                'Withdrawn items',
                '',
                '- T-Shirt, Blue / XL',
                '  Product number: TS-01',
                '  Unit price: 19.99 EUR',
                '  Quantity: 1 of 3 pcs',
                '',
                '- Hoodie',
                '  Product number: HD-02',
                '  Unit price: 39.99 EUR',
                '  Quantity: 2',
            ]),
            $rendered
        );
        self::assertStringNotContainsString('<table', $rendered);
        self::assertStringNotContainsString('<h3', $rendered);
    }

    public function testTemplateRendersScenarioTwoAsPlaintextFreetext(): void
    {
        $rendered = $this->renderTemplate([
            'scenario' => 2,
            'freetext' => "Order ref\nCustomer message",
        ]);

        self::assertSame(
            "Contract identification details\n\nOrder ref\nCustomer message",
            $rendered
        );
        self::assertStringNotContainsString('<p', $rendered);
        self::assertStringNotContainsString('<br', $rendered);
    }

    /**
     * @param array<string, mixed> $withdrawalData
     */
    private function renderTemplate(array $withdrawalData): string
    {
        $templatePath = dirname(__DIR__, 2) . '/src/Resources/contao/templates/template_mail_withdrawal_plaintext.html5';

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
