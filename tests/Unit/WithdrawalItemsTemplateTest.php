<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WithdrawalItemsTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_heading'] = 'Withdrawn items';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_freetext_heading'] = 'Contract identification details';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_table_header_product'] = 'Product description';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_table_header_product_number'] = 'Product number';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_table_header_unit_price'] = 'Unit price';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_table_header_quantity'] = 'Quantity';
        $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_quantity_partial'] = '%s of %s';
    }

    public function testTemplateRendersCombinedProductDescriptionAndUnitPriceColumn(): void
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

        self::assertStringContainsString('Product description', $rendered);
        self::assertStringContainsString('Unit price', $rendered);
        self::assertStringContainsString('T-Shirt, Blue / XL', $rendered);
        self::assertStringContainsString('Hoodie', $rendered);
        self::assertStringContainsString('19.99 EUR', $rendered);
        self::assertStringContainsString('39.99 EUR', $rendered);
        self::assertStringContainsString('1 of 3 pcs', $rendered);
        self::assertStringContainsString('>2<', $rendered);
        self::assertStringNotContainsString('<th>Variant</th>', $rendered);
    }

    /**
     * @param array<string, mixed> $withdrawalData
     */
    private function renderTemplate(array $withdrawalData): string
    {
        $templatePath = dirname(__DIR__, 2) . '/src/Resources/contao/templates/template_mail_withdrawal.html5';

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
