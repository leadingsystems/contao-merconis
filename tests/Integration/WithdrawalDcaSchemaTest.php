<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class WithdrawalDcaSchemaTest extends TestCase
{
    private const DCA_BASE_PATH = __DIR__ . '/../../src/Resources/contao/dca/';

    protected function setUp(): void
    {
        parent::setUp();
        unset(
            $GLOBALS['TL_DCA']['tl_ls_shop_withdrawal'],
            $GLOBALS['TL_DCA']['tl_ls_shop_withdrawal_items'],
            $GLOBALS['TL_DCA']['tl_ls_shop_withdrawal_rate_limit']
        );
    }

    public function testWithdrawalParentTableSchemaIsDefined(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_withdrawal.php';

        self::assertArrayHasKey('tl_ls_shop_withdrawal', $GLOBALS['TL_DCA']);
        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_withdrawal'];

        self::assertSame('primary', $tableConfig['config']['sql']['keys']['id']);
        self::assertSame('index', $tableConfig['config']['sql']['keys']['orderReference']);
        self::assertSame('unique', $tableConfig['config']['sql']['keys']['withdrawalId']);

        self::assertSame("varchar(64) NOT NULL default ''", $tableConfig['fields']['withdrawalId']['sql']);
        self::assertSame("int(10) unsigned NOT NULL default '0'", $tableConfig['fields']['withdrawalTimestamp']['sql']);
        self::assertSame("blob NULL", $tableConfig['fields']['snapshotBillingAddress']['sql']);
        self::assertSame("blob NULL", $tableConfig['fields']['snapshotShippingAddress']['sql']);
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['snapshotPaymentMethod_customerLanguage']['sql']
        );
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['snapshotShippingMethod_customerLanguage']['sql']
        );
        self::assertSame("char(1) NOT NULL default ''", $tableConfig['fields']['scenario']['sql']);
    }

    public function testWithdrawalChildTableSchemaIsDefined(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_withdrawal_items.php';

        self::assertArrayHasKey('tl_ls_shop_withdrawal_items', $GLOBALS['TL_DCA']);
        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_withdrawal_items'];

        self::assertSame('primary', $tableConfig['config']['sql']['keys']['id']);
        self::assertSame('index', $tableConfig['config']['sql']['keys']['pid']);

        self::assertSame("bigint(20) unsigned NOT NULL default '0'", $tableConfig['fields']['orderItemReference']['sql']);
        self::assertSame("varchar(255) NOT NULL default ''", $tableConfig['fields']['snapshotProductName']['sql']);
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['snapshotProductName_customerLanguage']['sql']
        );
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['snapshotVariantTitle_customerLanguage']['sql']
        );
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['snapshotUnitPrice_customerLanguage']['sql']
        );
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['snapshotQuantityUnit_customerLanguage']['sql']
        );
        self::assertSame("decimal(12,4) NOT NULL default '0.0000'", $tableConfig['fields']['snapshotOrderedQuantity']['sql']);
        self::assertSame("decimal(12,4) NOT NULL default '0.0000'", $tableConfig['fields']['withdrawnQuantity']['sql']);
    }

    public function testRateLimitTableSchemaIsDefined(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_withdrawal_rate_limit.php';

        self::assertArrayHasKey('tl_ls_shop_withdrawal_rate_limit', $GLOBALS['TL_DCA']);
        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_withdrawal_rate_limit'];

        self::assertSame('primary', $tableConfig['config']['sql']['keys']['id']);
        self::assertSame(
            'unique',
            $tableConfig['config']['sql']['keys']['scope,identifierHash,timeBucket']
        );
        self::assertSame('index', $tableConfig['config']['sql']['keys']['tstamp']);

        self::assertSame("varchar(16) NOT NULL default ''", $tableConfig['fields']['scope']['sql']);
        self::assertSame("varchar(64) NOT NULL default ''", $tableConfig['fields']['identifierHash']['sql']);
        self::assertSame("int(10) unsigned NOT NULL default '0'", $tableConfig['fields']['timeBucket']['sql']);
        self::assertSame("int(10) unsigned NOT NULL default '0'", $tableConfig['fields']['hitCount']['sql']);
    }

    public function testOrdersDcaContainsWithdrawalIdentifierFieldSqlDefinition(): void
    {
        $ordersDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_orders.php');

        self::assertStringContainsString("'withdrawalIdentifier' => array", $ordersDcaContents);
        self::assertStringContainsString(
            "\"varchar(64) NOT NULL default ''\"",
            $ordersDcaContents
        );
    }

    public function testShopSettingsDcaContainsWithdrawalSettingsFields(): void
    {
        $shopSettingsDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_lsShopSettings.php');

        self::assertStringContainsString("'ls_shop_withdrawalIdCounter' => array", $shopSettingsDcaContents);
        self::assertStringContainsString("'inputType' => 'simpleOutput'", $shopSettingsDcaContents);
        self::assertStringContainsString("'ls_shop_withdrawalPages' => array", $shopSettingsDcaContents);
        self::assertStringContainsString("'ls_shop_withdrawalConfirmationPages' => array", $shopSettingsDcaContents);
        self::assertStringContainsString('ls_shop_orderNrCounter,ls_shop_withdrawalIdCounter,ls_shop_orderNrString', $shopSettingsDcaContents);
        self::assertStringContainsString('ls_shop_afterCheckoutPages,ls_shop_withdrawalPages,ls_shop_withdrawalConfirmationPages', $shopSettingsDcaContents);
    }

    public function testMessageTypeDcaContainsWithdrawalSendWhenOptions(): void
    {
        $messageTypeDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_message_type.php');
        $messageTypeLanguageEn = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/en/tl_ls_shop_message_type.php');
        $messageTypeLanguageDe = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/de/tl_ls_shop_message_type.php');

        self::assertStringContainsString("'asWithdrawalConfirmation'", $messageTypeDcaContents);
        self::assertStringContainsString("'asWithdrawalNotice'", $messageTypeDcaContents);
        self::assertStringContainsString("'asWithdrawalConfirmation' =>", $messageTypeLanguageEn);
        self::assertStringContainsString("'asWithdrawalNotice' =>", $messageTypeLanguageEn);
        self::assertStringContainsString("'asWithdrawalConfirmation' =>", $messageTypeLanguageDe);
        self::assertStringContainsString("'asWithdrawalNotice' =>", $messageTypeLanguageDe);
        self::assertStringContainsString('Eingangsbestätigung für Widerruf', $messageTypeLanguageDe);
        self::assertStringContainsString('Widerrufsbenachrichtigung an Händler', $messageTypeLanguageDe);
    }

    public function testOrderOverviewMessageTypeFilterExcludesWithdrawalAutoSendTypes(): void
    {
        $generalHelperContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/helpers/ls_shop_generalHelper.php'
        );

        self::assertStringContainsString(
            "'asOrderConfirmation',
				'asOrderNotice',
				'onRestock',
				'asWithdrawalConfirmation',
				'asWithdrawalNotice'",
            $generalHelperContents
        );
    }

    public function testMessageModelDcaContainsWithdrawalCustomerDataTypeConfiguration(): void
    {
        $messageModelDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_message_model.php');
        $messageModelLanguageEn = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/en/tl_ls_shop_message_model.php');
        $messageModelLanguageDe = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/de/tl_ls_shop_message_model.php');

        self::assertStringContainsString("'getCustomerDataTypeOptions'", $messageModelDcaContents);
        self::assertStringContainsString("'setCustomerDataType1Default'", $messageModelDcaContents);
        self::assertStringContainsString("'setCustomerDataField1Default'", $messageModelDcaContents);
        self::assertStringContainsString("'setCustomerDataType2Default'", $messageModelDcaContents);
        self::assertStringContainsString("'setCustomerDataField2Default'", $messageModelDcaContents);
        self::assertDoesNotMatchRegularExpression(
            "/'customerDataType1'\\s*=>\\s*array\\s*\\(.*?'default'\\s*=>\\s*'personalData'/s",
            $messageModelDcaContents
        );
        self::assertStringContainsString("return 'personalData';", $messageModelDcaContents);
        self::assertStringNotContainsString('isNewMessageModelRecord', $messageModelDcaContents);
        self::assertStringContainsString("'withdrawalData' => 'Withdrawal data'", $messageModelLanguageEn);
        self::assertStringContainsString("'withdrawalData' => 'Widerrufsdaten'", $messageModelLanguageDe);
    }

    public function testWithdrawalMailSubTemplatesExistWithScenarioHandling(): void
    {
        $withdrawalTemplate = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/templates/template_mail_withdrawal.html5'
        );
        $orderDataTemplate = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/templates/template_mail_withdrawal_order_data.html5'
        );

        self::assertStringContainsString("['scenario']", $withdrawalTemplate);
        self::assertStringContainsString("['items']", $withdrawalTemplate);
        self::assertStringContainsString("['freetext']", $withdrawalTemplate);
        self::assertStringContainsString("withdrawal_items_heading", $withdrawalTemplate);
        self::assertStringContainsString("withdrawal_freetext_heading", $withdrawalTemplate);

        self::assertStringContainsString("['scenario']", $orderDataTemplate);
        self::assertStringContainsString("withdrawal_order_data_heading", $orderDataTemplate);
        self::assertStringContainsString("withdrawal_order_data_fallback", $orderDataTemplate);
    }

    public function testWithdrawalLanguageFilesContainRequiredKeysAndModuleLabels(): void
    {
        $defaultLanguageEn = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/en/default.php');
        $defaultLanguageDe = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/de/default.php');
        $modulesLanguageEn = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/en/modules.php');
        $modulesLanguageDe = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/de/modules.php');
        $moduleDcaLanguageEn = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/en/tl_module.php');
        $moduleDcaLanguageDe = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/de/tl_module.php');
        $shopSettingsLanguageEn = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/en/tl_lsShopSettings.php');
        $shopSettingsLanguageDe = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/languages/de/tl_lsShopSettings.php');

        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_items_heading']", $defaultLanguageEn);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_items_heading']", $defaultLanguageDe);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_entry_submit']", $defaultLanguageEn);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_entry_submit']", $defaultLanguageDe);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_entry_error_rate_limited']", $defaultLanguageEn);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_entry_error_rate_limited']", $defaultLanguageDe);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_form_submit']", $defaultLanguageEn);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_form_submit']", $defaultLanguageDe);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_fallback_submit']", $defaultLanguageEn);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_fallback_submit']", $defaultLanguageDe);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_confirmation_id_heading']", $defaultLanguageEn);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_confirmation_id_heading']", $defaultLanguageDe);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_confirmation_text']", $defaultLanguageEn);
        self::assertStringContainsString("['ls_contao-merconis']['withdrawal_confirmation_text']", $defaultLanguageDe);
        self::assertStringContainsString('Identifikationsnummer für den Widerruf:', $defaultLanguageDe);
        self::assertStringContainsString('Bitte versuchen Sie es später erneut.', $defaultLanguageDe);

        self::assertStringContainsString("['FMD']['ls_shop_withdrawal']", $modulesLanguageEn);
        self::assertStringContainsString("['FMD']['ls_shop_withdrawal_confirmation']", $modulesLanguageEn);
        self::assertStringContainsString("['FMD']['ls_shop_withdrawal']", $modulesLanguageDe);
        self::assertStringContainsString("['FMD']['ls_shop_withdrawal_confirmation']", $modulesLanguageDe);

        self::assertStringContainsString("['tl_module']['ls_shop_withdrawalConfirmationText']", $moduleDcaLanguageEn);
        self::assertStringContainsString("['tl_module']['ls_shop_withdrawalConfirmationText']", $moduleDcaLanguageDe);

        self::assertStringContainsString("['tl_lsShopSettings']['ls_shop_withdrawalIdCounter']", $shopSettingsLanguageEn);
        self::assertStringContainsString("['tl_lsShopSettings']['ls_shop_withdrawalPages']", $shopSettingsLanguageEn);
        self::assertStringContainsString("['tl_lsShopSettings']['ls_shop_withdrawalConfirmationPages']", $shopSettingsLanguageEn);
        self::assertStringContainsString("['tl_lsShopSettings']['ls_shop_withdrawalIdCounter']", $shopSettingsLanguageDe);
        self::assertStringContainsString("['tl_lsShopSettings']['ls_shop_withdrawalPages']", $shopSettingsLanguageDe);
        self::assertStringContainsString("['tl_lsShopSettings']['ls_shop_withdrawalConfirmationPages']", $shopSettingsLanguageDe);
    }

    public function testWithdrawalModuleIsRegisteredInFrontendModuleConfig(): void
    {
        $configContents = (string) file_get_contents(__DIR__ . '/../../src/Resources/contao/config/config.php');

        self::assertStringContainsString("'ls_shop_withdrawal' => 'Merconis\\Core\\ModuleWithdrawal'", $configContents);
        self::assertStringContainsString(
            "'ls_shop_withdrawal_confirmation' => 'Merconis\\Core\\ModuleWithdrawalConfirmation'",
            $configContents
        );
    }

    public function testModuleDcaContainsWithdrawalConfirmationTextFieldAndPalette(): void
    {
        $moduleDcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_module.php');

        self::assertStringContainsString("'ls_shop_withdrawal_confirmation'", $moduleDcaContents);
        self::assertStringContainsString("['ls_shop_withdrawalConfirmationText'] = array", $moduleDcaContents);
        self::assertStringContainsString("'inputType'               => 'textarea'", $moduleDcaContents);
        self::assertStringContainsString("'rte' => 'tinyMCE'", $moduleDcaContents);
    }

    public function testWithdrawalTemplatesExistForScreenRoutingAndScreenA(): void
    {
        self::assertFileExists(__DIR__ . '/../../src/Resources/contao/templates/mod_ls_shop_withdrawal.html5');
        self::assertFileExists(__DIR__ . '/../../src/Resources/contao/templates/mod_ls_shop_withdrawal_screenA.html5');
        self::assertFileExists(__DIR__ . '/../../src/Resources/contao/templates/mod_ls_shop_withdrawal_confirmation.html5');
    }

    public function testWithdrawalModuleBackendWildcardsAreDefined(): void
    {
        $withdrawalModuleContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/frontendModules/ModuleWithdrawal.php'
        );
        $withdrawalConfirmationModuleContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/frontendModules/ModuleWithdrawalConfirmation.php'
        );

        self::assertStringContainsString('### MERCONIS Widerruf ###', $withdrawalModuleContents);
        self::assertStringContainsString(
            '### MERCONIS Widerrufsbestätigung ###',
            $withdrawalConfirmationModuleContents
        );
    }

    public function testScreenBUsesCustomerLanguageOrderItemFields(): void
    {
        $withdrawalModuleContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/frontendModules/ModuleWithdrawal.php'
        );

        self::assertStringContainsString("_productTitle_customerLanguage", $withdrawalModuleContents);
        self::assertStringContainsString("_title_customerLanguage", $withdrawalModuleContents);
        self::assertStringContainsString("_quantityUnit_customerLanguage", $withdrawalModuleContents);
    }
}
