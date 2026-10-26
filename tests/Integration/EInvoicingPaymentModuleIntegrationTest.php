<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingPaymentModuleHook;
use PHPUnit\Framework\TestCase;

final class EInvoicingPaymentModuleIntegrationTest extends TestCase
{
    private const CONFIG_FILE = __DIR__ . '/../../src/Resources/contao/config/config.php';
    private const PAYMENT_HOOK_FILE = __DIR__ . '/../../src/EInvoicing/Contao/EInvoicingPaymentModuleHook.php';
    private const STANDARD_PAYMENT_MODULE_FILE = __DIR__ . '/../../src/EInvoicing/Contao/EInvoicingStandardPaymentModule.php';

    public function testConfigRegistersPaymentModuleTypeHook(): void
    {
        $configContents = (string) file_get_contents(self::CONFIG_FILE);

        self::assertStringContainsString("'modifyPaymentModuleTypes'", $configContents);
        self::assertStringContainsString(EInvoicingPaymentModuleHook::class, $configContents);
    }

    public function testPaymentModuleHookOverridesStandardPaymentModuleClass(): void
    {
        $hookContents = (string) file_get_contents(self::PAYMENT_HOOK_FILE);

        self::assertStringContainsString("\$types['standard']['className']", $hookContents);
        self::assertStringContainsString('EInvoicingStandardPaymentModule::class', $hookContents);
    }

    public function testStandardPaymentModuleContainsSepaCheckoutOverrides(): void
    {
        $moduleContents = (string) file_get_contents(self::STANDARD_PAYMENT_MODULE_FILE);

        self::assertStringContainsString('statusOkayToShowAdditionalDataForm', $moduleContents);
        self::assertStringContainsString('getFormIDForAdditionalData', $moduleContents);
        self::assertStringContainsString('getCustomUserInterface', $moduleContents);
        self::assertStringContainsString("=== '59'", $moduleContents);
        self::assertStringContainsString('StoredSepaMandateFormatter::hasStoredMandate', $moduleContents);
    }
}
