<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingCheckoutSnapshotHook;
use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingOrderMessageHook;
use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingProductImportHook;
use PHPUnit\Framework\TestCase;

final class EInvoicingCheckoutSnapshotIntegrationTest extends TestCase
{
    private const CONFIG_FILE = __DIR__ . '/../../src/Resources/contao/config/config.php';
    private const ORDERS_DCA_FILE = __DIR__ . '/../../src/Resources/contao/dca/tl_ls_shop_orders.php';
    private const CHECKOUT_SNAPSHOT_HOOK_FILE = __DIR__ . '/../../src/EInvoicing/Contao/EInvoicingCheckoutSnapshotHook.php';

    public function testEinvoicingConfigRegistersCheckoutSnapshotHooks(): void
    {
        $configContents = (string) file_get_contents(self::CONFIG_FILE);

        self::assertStringContainsString("'afterCheckout'", $configContents);
        self::assertStringContainsString("'storeCartItemInOrder'", $configContents);
        self::assertStringContainsString("'import_afterInsertingProductData'", $configContents);
        self::assertStringContainsString("'import_afterUpdatingProductData'", $configContents);
        self::assertStringContainsString("'beforeSendingOrderMessage'", $configContents);
        self::assertStringContainsString(EInvoicingCheckoutSnapshotHook::class, $configContents);
        self::assertStringContainsString(EInvoicingProductImportHook::class, $configContents);
        self::assertStringContainsString(EInvoicingOrderMessageHook::class, $configContents);
    }

    public function testOrdersDcaContainsPaymentMeansSnapshotField(): void
    {
        $ordersDcaContents = (string) file_get_contents(self::ORDERS_DCA_FILE);

        self::assertStringContainsString('paymentMethod_einvoicingPaymentMeansCode', $ordersDcaContents);
        self::assertStringContainsString('einvoicingTaxCountry', $ordersDcaContents);
        self::assertStringContainsString('einvoicingVatExemptionStatus', $ordersDcaContents);
        self::assertStringContainsString('shippingMethod_einvoicingTaxCategory', $ordersDcaContents);
        self::assertStringContainsString('paymentMethod_einvoicingTaxCategory', $ordersDcaContents);
        self::assertStringContainsString('einvoicingMandateReference', $ordersDcaContents);
        self::assertStringContainsString('einvoicingCreditorId', $ordersDcaContents);
        self::assertStringContainsString('einvoicingDebtorIban', $ordersDcaContents);
        self::assertStringContainsString("varchar(16) NOT NULL default ''", $ordersDcaContents);
        self::assertStringContainsString("varchar(2) NOT NULL default ''", $ordersDcaContents);
        self::assertStringContainsString("varchar(32) NOT NULL default ''", $ordersDcaContents);
        self::assertStringContainsString("varchar(35) NOT NULL default ''", $ordersDcaContents);
        self::assertStringContainsString("varchar(34) NOT NULL default ''", $ordersDcaContents);
        self::assertStringContainsString('paymentMethod_moduleReturnData,', $ordersDcaContents);
        self::assertStringContainsString('paymentMethod_einvoicingPaymentMeansCode;', $ordersDcaContents);
    }

    public function testCheckoutSnapshotHookPersistsSepaSpecificOrderSnapshotFields(): void
    {
        $hookContents = (string) file_get_contents(self::CHECKOUT_SNAPSHOT_HOOK_FILE);

        self::assertStringContainsString("private const SEPA_DIRECT_DEBIT_CODE = '59';", $hookContents);
        self::assertStringContainsString("loadRecordById('tl_member', \$memberId)", $hookContents);
        self::assertStringContainsString('ORDER_MANDATE_REFERENCE_COLUMN', $hookContents);
        self::assertStringContainsString('ORDER_CREDITOR_ID_COLUMN', $hookContents);
        self::assertStringContainsString('ORDER_DEBTOR_IBAN_COLUMN', $hookContents);
        self::assertStringContainsString('MEMBER_MANDATE_REFERENCE_FIELD', $hookContents);
        self::assertStringContainsString('MEMBER_DEBTOR_IBAN_FIELD', $hookContents);
        self::assertStringContainsString('SHOP_SETTINGS_CREDITOR_ID_FIELD', $hookContents);
        self::assertStringContainsString("\$snapshotOrder['customerNr']", $hookContents);
    }
}
