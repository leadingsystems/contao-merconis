<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Contao;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingCodeLists;

/**
 * DCA-Erweiterungen werden im Core-first-Modus bewusst per Hook
 * registriert. Bei einer späteren Extension-Extraktion sollen
 * diese Anpassungen in Standard-DCA-Dateien unter `contao/dca/`
 * überführt werden.
 */
final class EInvoicingDcaHook
{
    private const LEGEND_KEY = 'ls_contao_merconis_einvoicing_legend';
    private const TRANSLATION_DOMAIN = 'ls_contao_merconis_einvoicing';
    private const TRANSLATION_SCOPE = 'ls_contao-merconis';

    private const SHOP_SETTINGS_TABLE = 'tl_lsShopSettings';
    private const MEMBER_TABLE = 'tl_member';
    private const MEMBER_GROUP_TABLE = 'tl_member_group';
    private const ORDERS_TABLE = 'tl_ls_shop_orders';
    private const PAYMENT_METHODS_TABLE = 'tl_ls_shop_payment_methods';
    private const PRODUCT_TABLE = 'tl_ls_shop_product';
    private const TAX_RATES_TABLE = 'tl_ls_shop_steuersaetze';

    private static array $loadedLanguages = [];

    public function onLoadDataContainer(string $table): void
    {
        if (!in_array($table, $this->supportedTables(), true)) {
            return;
        }

        $this->loadTranslations();

        switch ($table) {
            case self::SHOP_SETTINGS_TABLE:
                $this->extendShopSettingsDca();
                return;

            case self::MEMBER_TABLE:
                $this->extendMemberDca();
                return;

            case self::MEMBER_GROUP_TABLE:
                $this->extendMemberGroupDca();
                return;

            case self::ORDERS_TABLE:
                $this->extendOrdersDca();
                return;

            case self::PAYMENT_METHODS_TABLE:
                $this->extendPaymentMethodsDca();
                return;

            case self::PRODUCT_TABLE:
                $this->extendProductDca();
                return;

            case self::TAX_RATES_TABLE:
                $this->extendTaxRatesDca();
                return;
        }
    }

    /**
     * Lädt die separaten Sprachdefinitionen nur einmal pro Sprache
     * und merged sie in die erwarteten `tl_*`-Domains.
     */
    private function loadTranslations(): void
    {
        $language = $this->resolveLanguage((string) ($GLOBALS['TL_LANGUAGE'] ?? 'en'));

        if (
            !isset(self::$loadedLanguages[$language])
            || !isset($GLOBALS['TL_LANG'][self::TRANSLATION_DOMAIN])
        ) {
            $translationFile = __DIR__ . '/../../Resources/contao/languages/' . $language . '/einvoicing.php';

            if (is_file($translationFile)) {
                require $translationFile;
            }

            self::$loadedLanguages[$language] = true;
        }

        $translations = $GLOBALS['TL_LANG'][self::TRANSLATION_DOMAIN] ?? [];

        foreach ($translations as $domain => $domainTranslations) {
            $existingTranslations = $GLOBALS['TL_LANG'][$domain] ?? [];
            $GLOBALS['TL_LANG'][$domain] = array_replace_recursive($existingTranslations, $domainTranslations);
        }
    }

    private function extendShopSettingsDca(): void
    {
        if (isset($GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_euCountrycodes']['eval'])) {
            $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_euCountrycodes']['eval']['mandatory'] = true;
        }

        if (isset($GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerName'])) {
            return;
        }

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerName'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerName'),
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
        ];

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerStreet'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerStreet'),
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
        ];

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerPostalCode'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerPostalCode'),
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 32, 'tl_class' => 'w50'],
        ];

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerCity'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerCity'),
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
        ];

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerTaxNumber'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerTaxNumber'),
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
        ];

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerElectronicAddress'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerElectronicAddress'),
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
        ];

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerElectronicAddressScheme'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerElectronicAddressScheme'),
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 16, 'tl_class' => 'w50'],
            'save_callback' => [
                [self::class, 'validateElectronicAddressScheme'],
            ],
        ];

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerIban'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerIban'),
            'inputType' => 'text',
            'eval' => ['maxlength' => 34, 'tl_class' => 'w50'],
        ];

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerBic'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerBic'),
            'inputType' => 'text',
            'eval' => ['maxlength' => 11, 'tl_class' => 'w50'],
        ];

        $GLOBALS['TL_DCA'][self::SHOP_SETTINGS_TABLE]['fields']['ls_shop_einvoicingSellerCreditorId'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::SHOP_SETTINGS_TABLE, 'ls_shop_einvoicingSellerCreditorId'),
            'inputType' => 'text',
            'eval' => ['maxlength' => 35, 'tl_class' => 'w50'],
        ];

        PaletteManipulator::create()
            ->addLegend(self::LEGEND_KEY, 'euSettings_legend', PaletteManipulator::POSITION_BEFORE)
            ->addField(
                [
                    'ls_shop_einvoicingSellerName',
                    'ls_shop_einvoicingSellerStreet',
                    'ls_shop_einvoicingSellerPostalCode',
                    'ls_shop_einvoicingSellerCity',
                    'ls_shop_einvoicingSellerTaxNumber',
                    'ls_shop_einvoicingSellerElectronicAddress',
                    'ls_shop_einvoicingSellerElectronicAddressScheme',
                    'ls_shop_einvoicingSellerIban',
                    'ls_shop_einvoicingSellerBic',
                    'ls_shop_einvoicingSellerCreditorId',
                ],
                self::LEGEND_KEY,
                PaletteManipulator::POSITION_APPEND
            )
            ->applyToPalette('default', self::SHOP_SETTINGS_TABLE);
    }

    private function extendMemberDca(): void
    {
        if (isset($GLOBALS['TL_DCA'][self::MEMBER_TABLE]['fields']['lsShopEinvoicingMandateReference'])) {
            return;
        }

        $GLOBALS['TL_DCA'][self::MEMBER_TABLE]['fields']['lsShopEinvoicingMandateReference'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::MEMBER_TABLE, 'lsShopEinvoicingMandateReference'),
            'inputType' => 'text',
            'eval' => ['maxlength' => 35, 'tl_class' => 'w50'],
            'sql' => "varchar(35) NOT NULL default ''",
        ];

        $GLOBALS['TL_DCA'][self::MEMBER_TABLE]['fields']['lsShopEinvoicingDebtorIban'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::MEMBER_TABLE, 'lsShopEinvoicingDebtorIban'),
            'inputType' => 'text',
            'eval' => ['maxlength' => 34, 'tl_class' => 'w50'],
            'sql' => "varchar(34) NOT NULL default ''",
        ];

        PaletteManipulator::create()
            ->addLegend(self::LEGEND_KEY, 'lsShop_legend', PaletteManipulator::POSITION_AFTER)
            ->addField(
                [
                    'lsShopEinvoicingMandateReference',
                    'lsShopEinvoicingDebtorIban',
                ],
                self::LEGEND_KEY,
                PaletteManipulator::POSITION_APPEND
            )
            ->applyToPalette('default', self::MEMBER_TABLE);
    }

    private function extendMemberGroupDca(): void
    {
        if (isset($GLOBALS['TL_DCA'][self::MEMBER_GROUP_TABLE]['fields']['lsShopEinvoicingPaymentTermsDays'])) {
            return;
        }

        $GLOBALS['TL_DCA'][self::MEMBER_GROUP_TABLE]['fields']['lsShopEinvoicingPaymentTermsDays'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::MEMBER_GROUP_TABLE, 'lsShopEinvoicingPaymentTermsDays'),
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'maxlength' => 5, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default ''",
        ];

        $GLOBALS['TL_DCA'][self::MEMBER_GROUP_TABLE]['fields']['lsShopEinvoicingPaymentTermsNote'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::MEMBER_GROUP_TABLE, 'lsShopEinvoicingPaymentTermsNote'),
            'inputType' => 'textarea',
            'eval' => [
                'rows' => 4,
                'tl_class' => 'clr',
                'merconis_multilanguage' => true,
                'merconis_multilanguage_noTopLinedGroup' => true,
            ],
            'sql' => "text NULL",
        ];

        PaletteManipulator::create()
            ->addLegend(self::LEGEND_KEY, 'lsShop_legend', PaletteManipulator::POSITION_AFTER)
            ->addField(
                [
                    'lsShopEinvoicingPaymentTermsDays',
                    'lsShopEinvoicingPaymentTermsNote',
                ],
                self::LEGEND_KEY,
                PaletteManipulator::POSITION_APPEND
            )
            ->applyToPalette('default', self::MEMBER_GROUP_TABLE);
    }

    private function extendOrdersDca(): void
    {
        if (isset($GLOBALS['TL_DCA'][self::ORDERS_TABLE]['fields']['einvoicingDeliveryDate'])) {
            return;
        }

        $GLOBALS['TL_DCA'][self::ORDERS_TABLE]['fields']['einvoicingDeliveryDate'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::ORDERS_TABLE, 'einvoicingDeliveryDate'),
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ];

        PaletteManipulator::create()
            ->addLegend(self::LEGEND_KEY, 'paymentInfo_legend', PaletteManipulator::POSITION_AFTER)
            ->addField(
                'einvoicingDeliveryDate',
                self::LEGEND_KEY,
                PaletteManipulator::POSITION_APPEND
            )
            ->applyToPalette('default', self::ORDERS_TABLE);
    }

    private function extendPaymentMethodsDca(): void
    {
        if (isset($GLOBALS['TL_DCA'][self::PAYMENT_METHODS_TABLE]['fields']['einvoicingPaymentMeansCode'])) {
            return;
        }

        $GLOBALS['TL_DCA'][self::PAYMENT_METHODS_TABLE]['fields']['einvoicingPaymentMeansCode'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::PAYMENT_METHODS_TABLE, 'einvoicingPaymentMeansCode'),
            'inputType' => 'select',
            'options' => EInvoicingCodeLists::getPaymentMeansCodeOptions(),
            'reference' => &$GLOBALS['TL_LANG'][self::PAYMENT_METHODS_TABLE]['einvoicingPaymentMeansCodeRef'],
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default ''",
        ];

        $GLOBALS['TL_DCA'][self::PAYMENT_METHODS_TABLE]['fields']['einvoicingPaymentTermsDays'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::PAYMENT_METHODS_TABLE, 'einvoicingPaymentTermsDays'),
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'maxlength' => 5, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default ''",
        ];

        $GLOBALS['TL_DCA'][self::PAYMENT_METHODS_TABLE]['fields']['einvoicingPaymentTermsNote'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::PAYMENT_METHODS_TABLE, 'einvoicingPaymentTermsNote'),
            'inputType' => 'textarea',
            'eval' => [
                'rows' => 4,
                'tl_class' => 'clr',
                'merconis_multilanguage' => true,
                'merconis_multilanguage_noTopLinedGroup' => true,
            ],
            'sql' => "text NULL",
        ];

        PaletteManipulator::create()
            ->addLegend(self::LEGEND_KEY, 'steuersatz_legend', PaletteManipulator::POSITION_BEFORE)
            ->addField(
                [
                    'einvoicingPaymentMeansCode',
                    'einvoicingPaymentTermsDays',
                    'einvoicingPaymentTermsNote',
                ],
                self::LEGEND_KEY,
                PaletteManipulator::POSITION_APPEND
            )
            ->applyToPalette('default', self::PAYMENT_METHODS_TABLE);
    }

    private function extendProductDca(): void
    {
        if (isset($GLOBALS['TL_DCA'][self::PRODUCT_TABLE]['fields']['einvoicingUnitCode'])) {
            return;
        }

        $GLOBALS['TL_DCA'][self::PRODUCT_TABLE]['fields']['einvoicingUnitCode'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::PRODUCT_TABLE, 'einvoicingUnitCode'),
            'default' => EInvoicingCodeLists::DEFAULT_UNIT_CODE,
            'inputType' => 'select',
            'options' => EInvoicingCodeLists::getUnitCodeOptions(),
            'reference' => &$GLOBALS['TL_LANG'][self::PRODUCT_TABLE]['einvoicingUnitCodeRef'],
            'eval' => ['chosen' => true, 'mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'H87'",
        ];

        PaletteManipulator::create()
            ->addField('einvoicingUnitCode', 'lsShopProductQuantityUnit', PaletteManipulator::POSITION_AFTER)
            ->applyToPalette('default', self::PRODUCT_TABLE);
    }

    private function extendTaxRatesDca(): void
    {
        if (isset($GLOBALS['TL_DCA'][self::TAX_RATES_TABLE]['fields']['einvoicingTaxCategory'])) {
            return;
        }

        $GLOBALS['TL_DCA'][self::TAX_RATES_TABLE]['fields']['einvoicingTaxCategory'] = [
            'exclude' => true,
            'label' => $this->fieldLabel(self::TAX_RATES_TABLE, 'einvoicingTaxCategory'),
            'default' => 'auto',
            'inputType' => 'select',
            'options' => ['auto', 'S', 'Z', 'E', 'AE', 'K', 'G'],
            'reference' => $this->fieldOptions(self::TAX_RATES_TABLE, 'einvoicingTaxCategory'),
            'eval' => ['includeBlankOption' => false, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'auto'",
        ];

        PaletteManipulator::create()
            ->addLegend(self::LEGEND_KEY, 'title_legend', PaletteManipulator::POSITION_AFTER)
            ->addField(
                'einvoicingTaxCategory',
                self::LEGEND_KEY,
                PaletteManipulator::POSITION_APPEND
            )
            ->applyToPalette('default', self::TAX_RATES_TABLE);
    }

    private function fieldLabel(string $domain, string $field): array
    {
        $translation = $this->translationNode($domain, [self::TRANSLATION_SCOPE, $field], []);

        return [
            $translation[0] ?? $field,
            $translation[1] ?? '',
        ];
    }

    private function fieldOptions(string $domain, string $field): array
    {
        $options = $this->translationNode($domain, [self::TRANSLATION_SCOPE, $field, 'options'], []);

        return is_array($options) ? $options : [];
    }

    private function translationNode(string $domain, array $path, mixed $default): mixed
    {
        $node = $GLOBALS['TL_LANG'][$domain] ?? [];

        foreach ($path as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return $default;
            }

            $node = $node[$segment];
        }

        return $node;
    }

    public static function validateElectronicAddressScheme(mixed $value): string
    {
        $normalizedValue = EInvoicingCodeLists::normalizeCode($value);

        if ($normalizedValue === null || !EInvoicingCodeLists::hasElectronicAddressScheme($normalizedValue)) {
            throw new \RuntimeException(self::resolveElectronicAddressSchemeErrorMessage());
        }

        return $normalizedValue;
    }

    private static function resolveElectronicAddressSchemeErrorMessage(): string
    {
        $message = $GLOBALS['TL_LANG'][self::SHOP_SETTINGS_TABLE]['einvoicingEasValidationError'] ?? null;

        return is_string($message) && $message !== ''
            ? $message
            : 'Please enter a valid EN 16931 electronic address scheme code from the official code list.';
    }

    private function resolveLanguage(string $language): string
    {
        if (str_starts_with($language, 'de')) {
            return 'de';
        }

        return 'en';
    }

    /**
     * @return list<string>
     */
    private function supportedTables(): array
    {
        return [
            self::SHOP_SETTINGS_TABLE,
            self::MEMBER_TABLE,
            self::MEMBER_GROUP_TABLE,
            self::ORDERS_TABLE,
            self::PAYMENT_METHODS_TABLE,
            self::PRODUCT_TABLE,
            self::TAX_RATES_TABLE,
        ];
    }
}
