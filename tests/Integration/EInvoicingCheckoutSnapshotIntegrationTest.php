<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingCheckoutSnapshotHook;
use PHPUnit\Framework\TestCase;

final class EInvoicingCheckoutSnapshotIntegrationTest extends TestCase
{
    private const CONFIG_FILE = __DIR__ . '/../../src/Resources/contao/config/einvoicing.php';
    private const ORDERS_DCA_FILE = __DIR__ . '/../../src/Resources/contao/dca/tl_ls_shop_orders.php';

    public function testEinvoicingConfigRegistersCheckoutSnapshotHooks(): void
    {
        $configContents = (string) file_get_contents(self::CONFIG_FILE);

        self::assertStringContainsString("'afterCheckout'", $configContents);
        self::assertStringContainsString("'storeCartItemInOrder'", $configContents);
        self::assertStringContainsString(EInvoicingCheckoutSnapshotHook::class, $configContents);
    }

    public function testOrdersDcaContainsPaymentMeansSnapshotField(): void
    {
        $ordersDcaContents = (string) file_get_contents(self::ORDERS_DCA_FILE);

        self::assertStringContainsString('ls_shop_einvoicingPaymentMeansCode', $ordersDcaContents);
        self::assertStringContainsString("varchar(16) NOT NULL default ''", $ordersDcaContents);
        self::assertStringContainsString(
            "paymentMethod_moduleReturnData,\n\t\t\tls_shop_einvoicingPaymentMeansCode;",
            $ordersDcaContents
        );
    }
}
