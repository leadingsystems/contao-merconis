<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingDcaHook;
use PHPUnit\Framework\TestCase;

final class EInvoicingDcaHookTest extends TestCase
{
    private const CONFIG_FILE = __DIR__ . '/../../src/Resources/contao/config/einvoicing.php';

    private EInvoicingDcaHook $hook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hook = new EInvoicingDcaHook();

        unset(
            $GLOBALS['TL_DCA']['tl_lsShopSettings'],
            $GLOBALS['TL_DCA']['tl_member_group'],
            $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods'],
            $GLOBALS['TL_DCA']['tl_ls_shop_steuersaetze'],
            $GLOBALS['TL_LANG']['ls_contao_merconis_einvoicing'],
            $GLOBALS['TL_LANG']['tl_lsShopSettings'],
            $GLOBALS['TL_LANG']['tl_member_group'],
            $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods'],
            $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']
        );
    }

    public function testShopSettingsDcaAddsConfiguredFieldsAndLegend(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $GLOBALS['TL_DCA']['tl_lsShopSettings'] = [
            'palettes' => [
                'default' => '{basic_legend},ls_shop_country;{euSettings_legend},ls_shop_ownVATID;',
            ],
            'fields' => [],
        ];

        $this->hook->onLoadDataContainer('tl_lsShopSettings');

        self::assertArrayHasKey(
            'ls_shop_einvoicingSellerElectronicAddressScheme',
            $GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']
        );
        self::assertSame(
            ['Firmenname', 'Bitte tragen Sie hier den Firmennamen ein, der in der E-Rechnung als Verkäufername verwendet werden soll.'],
            $GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']['ls_shop_einvoicingSellerName']['label']
        );
        self::assertTrue($GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']['ls_shop_einvoicingSellerName']['eval']['mandatory']);

        $palette = $GLOBALS['TL_DCA']['tl_lsShopSettings']['palettes']['default'];
        self::assertStringContainsString('{ls_contao_merconis_einvoicing_legend}', $palette);
        self::assertLessThan(
            strpos($palette, '{euSettings_legend}'),
            strpos($palette, '{ls_contao_merconis_einvoicing_legend}')
        );
    }

    public function testMemberGroupDcaAddsMultilingualOverrideFields(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $GLOBALS['TL_DCA']['tl_member_group'] = [
            'config' => [],
            'palettes' => [
                'default' => '{redirect_legend},disable,start,stop;{lsShop_legend},lsShopStandardPaymentMethod;',
            ],
            'fields' => [],
        ];

        $this->hook->onLoadDataContainer('tl_member_group');

        self::assertSame(
            "varchar(16) NOT NULL default ''",
            $GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopEinvoicingPaymentTermsDays']['sql']
        );
        self::assertSame(
            "text NULL",
            $GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopEinvoicingPaymentTermsNote']['sql']
        );
        self::assertTrue(
            $GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopEinvoicingPaymentTermsNote']['eval']['merconis_multilanguage']
        );
        self::assertStringContainsString(
            'lsShopEinvoicingPaymentTermsDays,lsShopEinvoicingPaymentTermsNote',
            $GLOBALS['TL_DCA']['tl_member_group']['palettes']['default']
        );
    }

    public function testPaymentMethodsDcaAddsPaymentMeansCodeWithStageOneDefault(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods'] = [
            'palettes' => [
                'default' => '{title_legend},title;{published_legend},published;',
            ],
            'fields' => [],
        ];

        $this->hook->onLoadDataContainer('tl_ls_shop_payment_methods');

        $field = $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['fields']['ls_shop_einvoicingPaymentMeansCode'];

        self::assertSame('58', $field['default']);
        self::assertSame("varchar(16) NOT NULL default '58'", $field['sql']);
        self::assertSame(
            'Bitte tragen Sie hier den EN-16931 Payment Means Code für diese Zahlungsart ein. In Stufe 1 ist standardmäßig 58 für SEPA-Überweisung vorgesehen.',
            $field['label'][1]
        );
    }

    public function testTaxRatesDcaAddsTaxCategorySelectWithTranslatedOptions(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $GLOBALS['TL_DCA']['tl_ls_shop_steuersaetze'] = [
            'palettes' => [
                'default' => '{title_legend},title;{steuerPeriod1_legend},steuerProzentPeriod1;{steuerPeriod2_legend},steuerProzentPeriod2;',
            ],
            'fields' => [],
        ];

        $this->hook->onLoadDataContainer('tl_ls_shop_steuersaetze');

        $field = $GLOBALS['TL_DCA']['tl_ls_shop_steuersaetze']['fields']['ls_shop_einvoicingTaxCategory'];

        self::assertSame(['auto', 'S', 'Z', 'E', 'AE', 'K', 'G'], $field['options']);
        self::assertSame('auto', $field['default']);
        self::assertSame('Steuersatz wie angegeben', $field['reference']['auto']);
        self::assertSame('Reverse Charge (AE)', $field['reference']['AE']);
        self::assertStringContainsString(
            '{ls_contao_merconis_einvoicing_legend},ls_shop_einvoicingTaxCategory',
            $GLOBALS['TL_DCA']['tl_ls_shop_steuersaetze']['palettes']['default']
        );
    }

    public function testEnglishTranslationsAreLoaded(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'en';
        $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods'] = [
            'palettes' => [
                'default' => '{title_legend},title;{published_legend},published;',
            ],
            'fields' => [],
        ];

        $this->hook->onLoadDataContainer('tl_ls_shop_payment_methods');

        self::assertSame(
            'Payment Means Code',
            $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['fields']['ls_shop_einvoicingPaymentMeansCode']['label'][0]
        );
    }

    public function testHookRegistrationFileRegistersLoadDataContainerHook(): void
    {
        $configContents = (string) file_get_contents(self::CONFIG_FILE);

        self::assertStringContainsString("'loadDataContainer'", $configContents);
        self::assertStringContainsString(EInvoicingDcaHook::class, $configContents);
        self::assertStringContainsString("'onLoadDataContainer'", $configContents);
    }
}
