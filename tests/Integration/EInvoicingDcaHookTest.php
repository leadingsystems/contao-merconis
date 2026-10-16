<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingDcaHook;
use PHPUnit\Framework\TestCase;

final class EInvoicingDcaHookTest extends TestCase
{
    private const CONFIG_FILE = __DIR__ . '/../../src/Resources/contao/config/config.php';

    private EInvoicingDcaHook $hook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hook = new EInvoicingDcaHook();

        unset(
            $GLOBALS['TL_DCA']['tl_lsShopSettings'],
            $GLOBALS['TL_DCA']['tl_member_group'],
            $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods'],
            $GLOBALS['TL_DCA']['tl_ls_shop_product'],
            $GLOBALS['TL_DCA']['tl_ls_shop_steuersaetze'],
            $GLOBALS['TL_LANG']['ls_contao_merconis_einvoicing'],
            $GLOBALS['TL_LANG']['tl_lsShopSettings'],
            $GLOBALS['TL_LANG']['tl_member_group'],
            $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods'],
            $GLOBALS['TL_LANG']['tl_ls_shop_product'],
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
            'fields' => [
                'ls_shop_euCountrycodes' => [
                    'eval' => [],
                ],
            ],
        ];

        $this->hook->onLoadDataContainer('tl_lsShopSettings');

        self::assertArrayHasKey(
            'ls_shop_einvoicingSellerElectronicAddressScheme',
            $GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']
        );
        self::assertArrayNotHasKey(
            'einvoicingPaymentTermsDays',
            $GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']
        );
        self::assertArrayNotHasKey(
            'einvoicingPaymentTermsNote',
            $GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']
        );
        self::assertSame(
            ['Firmenname', 'Bitte tragen Sie hier den Firmennamen ein, der in der E-Rechnung als Verkäufername verwendet werden soll.'],
            $GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']['ls_shop_einvoicingSellerName']['label']
        );
        self::assertTrue($GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']['ls_shop_einvoicingSellerName']['eval']['mandatory']);
        self::assertTrue($GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']['ls_shop_euCountrycodes']['eval']['mandatory']);

        $easField = $GLOBALS['TL_DCA']['tl_lsShopSettings']['fields']['ls_shop_einvoicingSellerElectronicAddressScheme'];
        self::assertSame('select', $easField['inputType']);
        self::assertCount(102, $easField['options']);
        self::assertContains('0204', $easField['options']);
        self::assertSame(
            '0204 - Leitweg-ID',
            $easField['reference']['0204']
        );
        self::assertSame(
            $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_einvoicingSellerElectronicAddressSchemeRef'],
            $easField['reference']
        );
        self::assertTrue($easField['eval']['chosen']);
        self::assertTrue($easField['eval']['includeBlankOption']);
        self::assertTrue($easField['eval']['mandatory']);

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

    public function testPaymentMethodsDcaAddsPaymentMeansCodeSelectWithoutDefault(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods'] = [
            'palettes' => [
                'default' => '{title_legend},title;{type_legend},type;{steuersatz_legend},dynamicSteuersatzType;{published_legend},published;',
            ],
            'fields' => [],
        ];

        $this->hook->onLoadDataContainer('tl_ls_shop_payment_methods');

        $field = $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['fields']['einvoicingPaymentMeansCode'];

        self::assertSame('select', $field['inputType']);
        self::assertArrayNotHasKey('default', $field);
        self::assertSame("varchar(16) NOT NULL default ''", $field['sql']);
        self::assertSame('Zahlungsart-Code (EN 16931)', $field['label'][0]);
        self::assertSame(
            'Wählen Sie den EN-16931-Code, der diese Zahlungsart identifiziert.',
            $field['label'][1]
        );
        self::assertCount(84, $field['options']);
        self::assertContains('58', $field['options']);
        self::assertContains('ZZZ', $field['options']);
        self::assertSame('58 - SEPA credit transfer', $field['reference']['58']);
        self::assertSame('ZZZ - Mutually defined', $field['reference']['ZZZ']);
        self::assertSame(
            $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['einvoicingPaymentMeansCodeRef'],
            $field['reference']
        );
        self::assertTrue($field['eval']['chosen']);
        self::assertTrue($field['eval']['includeBlankOption']);
        self::assertTrue($field['eval']['mandatory']);
        self::assertSame(
            "varchar(16) NOT NULL default ''",
            $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['fields']['einvoicingPaymentTermsDays']['sql']
        );
        self::assertTrue(
            $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['fields']['einvoicingPaymentTermsNote']['eval']['merconis_multilanguage']
        );

        $palette = $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['palettes']['default'];
        self::assertStringContainsString(
            '{ls_contao_merconis_einvoicing_legend},einvoicingPaymentMeansCode,einvoicingPaymentTermsDays,einvoicingPaymentTermsNote',
            $palette
        );
        self::assertGreaterThan(
            strpos($palette, '{type_legend}'),
            strpos($palette, '{ls_contao_merconis_einvoicing_legend}')
        );
        self::assertLessThan(
            strpos($palette, '{steuersatz_legend}'),
            strpos($palette, '{ls_contao_merconis_einvoicing_legend}')
        );
    }

    public function testProductDcaAddsUnitCodeSelectAfterQuantityUnit(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $GLOBALS['TL_DCA']['tl_ls_shop_product'] = [
            'palettes' => [
                'default' => '{lsShopUnits_legend},lsShopProductQuantityUnit,lsShopProductMengenvergleichUnit;',
            ],
            'fields' => [],
        ];

        $this->hook->onLoadDataContainer('tl_ls_shop_product');

        $field = $GLOBALS['TL_DCA']['tl_ls_shop_product']['fields']['einvoicingUnitCode'];

        self::assertSame('select', $field['inputType']);
        self::assertSame('H87', $field['default']);
        self::assertSame("varchar(16) NOT NULL default 'H87'", $field['sql']);
        self::assertSame('Mengeneinheitencode (EN 16931)', $field['label'][0]);
        self::assertSame('H87 - piece', $field['reference']['H87']);
        self::assertSame(
            $GLOBALS['TL_LANG']['tl_ls_shop_product']['einvoicingUnitCodeRef'],
            $field['reference']
        );
        self::assertTrue($field['eval']['chosen']);
        self::assertTrue($field['eval']['mandatory']);
        self::assertStringContainsString(
            'lsShopProductQuantityUnit,einvoicingUnitCode,lsShopProductMengenvergleichUnit',
            $GLOBALS['TL_DCA']['tl_ls_shop_product']['palettes']['default']
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

        $field = $GLOBALS['TL_DCA']['tl_ls_shop_steuersaetze']['fields']['einvoicingTaxCategory'];

        self::assertSame(['auto', 'S', 'Z', 'E', 'AE', 'K', 'G'], $field['options']);
        self::assertSame('auto', $field['default']);
        self::assertSame('Steuersatz wie angegeben', $field['reference']['auto']);
        self::assertSame('Reverse Charge (AE)', $field['reference']['AE']);
        self::assertStringContainsString(
            '{ls_contao_merconis_einvoicing_legend},einvoicingTaxCategory',
            $GLOBALS['TL_DCA']['tl_ls_shop_steuersaetze']['palettes']['default']
        );
    }

    public function testEnglishTranslationsAreLoaded(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'en';
        $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods'] = [
            'palettes' => [
                'default' => '{title_legend},title;{type_legend},type;{steuersatz_legend},dynamicSteuersatzType;{published_legend},published;',
            ],
            'fields' => [],
        ];

        $this->hook->onLoadDataContainer('tl_ls_shop_payment_methods');

        self::assertSame(
            'Payment means code (EN 16931)',
            $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['fields']['einvoicingPaymentMeansCode']['label'][0]
        );
    }

    public function testElectronicAddressSchemeValidationNormalizesAndRejectsUnknownCodes(): void
    {
        $GLOBALS['TL_LANG']['tl_lsShopSettings']['einvoicingEasValidationError'] =
            'Please enter a valid EN 16931 electronic address scheme code from the official code list.';

        self::assertSame('0204', EInvoicingDcaHook::validateElectronicAddressScheme('0204'));
        self::assertSame('EM', EInvoicingDcaHook::validateElectronicAddressScheme('em'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Please enter a valid EN 16931 electronic address scheme code from the official code list.'
        );
        EInvoicingDcaHook::validateElectronicAddressScheme('unknown');
    }

    public function testHookRegistrationFileRegistersLoadDataContainerHook(): void
    {
        $configContents = (string) file_get_contents(self::CONFIG_FILE);

        self::assertStringContainsString("'loadDataContainer'", $configContents);
        self::assertStringContainsString(EInvoicingDcaHook::class, $configContents);
        self::assertStringContainsString("'onLoadDataContainer'", $configContents);
    }
}
