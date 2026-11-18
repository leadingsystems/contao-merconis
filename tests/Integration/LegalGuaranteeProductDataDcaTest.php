<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeValidationHelper;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeProductDataDcaTest extends TestCase
{
    private const BACKEND_CALLBACKS_CLASS = 'LeadingSystems\\MerconisBundle\\LegalGuarantee\\ProductData\\LegalGuaranteeBackendCallbacks';
    private const BACKEND_CALLBACKS_PATH = __DIR__ . '/../../src/LegalGuarantee/ProductData/LegalGuaranteeBackendCallbacks.php';
    private const DCA_BASE_PATH = __DIR__ . '/../../src/Resources/contao/dca/';
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';
    private const SERVICES_PATH = __DIR__ . '/../../src/Resources/config/services.yml';
    private const MIGRATION_PATH = __DIR__ . '/../../src/Migration/EnableGllDefaultMigration.php';
    /*
     * Sprachschlüssel aus Implementierungsentscheidung 0019, Abschnitt "Sprachschlüssel", und
     * die Ablehnung an Varianten mit eigenen Angaben, ersetzt nach Implementierungsentscheidung 0025.
     */
    private const FORMER_LEGAL_GUARANTEE_RULE_LANGUAGE_KEYS = [
        'gll_enabled_by_garan',
        'garan_disabled_by_gll',
        'garan_not_output_without_gll',
        'garan_override_gll_required',
        'garan_disabled_gll_variant_override',
    ];
    /*
     * Die Bezeichner der entfernten GLL-Übernahme sind zusammengesetzt, damit eine
     * Repository-Suche nach ihren Resten leer bleibt.
     */
    private const FORMER_GLL_MIGRATION_MANAGER_CLASS = 'EnableGll' . 'SettingsMigrationManager';
    private const FORMER_GLL_MIGRATION_CONTROL_FIELD = 'ls_shop_enableGll' . 'MigrationControl';
    private const FORMER_GLL_MIGRATION_FLAG = 'ls_shop_enableGll' . 'MigrationApplied';
    private const FORMER_GLL_MIGRATION_MANAGER_PATH = __DIR__ . '/../../src/LegalGuarantee/ProductData/' . self::FORMER_GLL_MIGRATION_MANAGER_CLASS . '.php';
    private const MISC_BACKEND_STYLES_PATH = __DIR__ . '/../../src/Resources/public/lscss/_miscBeStyles.scss';
    private const SETTINGS_DCA_PATH = __DIR__ . '/../../src/Resources/contao/dca/tl_lsShopSettings.php';
    private const DEFAULT_LANGUAGE_DE_PATH = __DIR__ . '/../../src/Resources/contao/languages/de/default.php';
    private const DEFAULT_LANGUAGE_EN_PATH = __DIR__ . '/../../src/Resources/contao/languages/en/default.php';
    private const GUARANTEE_BRAND_DESCRIPTION_DE = "Der volle Wert wird gespeichert. Bei Überlänge wird der Markenname in der Labeldarstellung mit '...' gekürzt. Die Kürzungslänge ist in den Grundeinstellungen konfigurierbar.";
    private const GUARANTEE_BRAND_DESCRIPTION_EN = "The full value is stored. If too long, the brand name is truncated with '...' in the label display. The truncation length is configurable in the shop settings.";
    private const GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_DE = "Der volle Wert wird gespeichert. Bei Überlänge wird die Modellkennung in der Labeldarstellung mit '...' gekürzt. Die Kürzungslänge ist in den Grundeinstellungen konfigurierbar.";
    /**
     * @var array<string, mixed>|null
     */
    private static ?array $loadedVariantDca = null;

    private const GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_EN = "The full value is stored. If too long, the model identifier is truncated with '...' in the label display. The truncation length is configurable in the shop settings.";

    protected function setUp(): void
    {
        parent::setUp();
        unset(
            $GLOBALS['TL_DCA']['tl_ls_shop_product'],
            $GLOBALS['TL_DCA']['tl_ls_shop_variant']
        );
    }

    public function testProductDcaContainsGuaranteeFieldsWithExpectedSchemaAndDefaults(): void
    {
        $this->loadProductDca();

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_product'];

        self::assertArrayNotHasKey('enableGll', $tableConfig['fields']);
        self::assertSame("char(1) NOT NULL default ''", $tableConfig['fields']['enableGaran']['sql']);
        self::assertTrue($tableConfig['fields']['enableGaran']['filter']);
        self::assertSame("int(10) unsigned NULL", $tableConfig['fields']['guaranteeDurationMonths']['sql']);
        self::assertTrue($tableConfig['fields']['guaranteeDurationMonths']['eval']['mandatory']);
        self::assertSame('natural', $tableConfig['fields']['guaranteeDurationMonths']['eval']['rgxp']);
        self::assertTrue($tableConfig['fields']['guaranteeBrand']['eval']['mandatory']);
        self::assertTrue($tableConfig['fields']['guaranteeModelIdentifier']['eval']['mandatory']);
        self::assertArrayNotHasKey('maxlength', $tableConfig['fields']['guaranteeBrand']['eval']);
        self::assertArrayNotHasKey('maxlength', $tableConfig['fields']['guaranteeModelIdentifier']['eval']);
        self::assertSame("varchar(255) NOT NULL default ''", $tableConfig['fields']['guaranteeBrand']['sql']);
        self::assertSame("varchar(255) NOT NULL default ''", $tableConfig['fields']['guaranteeModelIdentifier']['sql']);
        self::assertStringContainsString(
            '{lsShopGuaranteeLabels_legend},gllMode,enableGaran;',
            str_replace(["\n", "\r", "\t", ' '], '', $tableConfig['palettes']['default'])
        );
        self::assertStringNotContainsString('enableGll', $tableConfig['palettes']['default']);
        self::assertContains('enableGaran', $tableConfig['palettes']['__selector__']);
        self::assertNotContains('gllMode', $tableConfig['palettes']['__selector__']);
        self::assertSame(
            'guaranteeDurationMonths,guaranteeBrand,guaranteeModelIdentifier',
            str_replace(["\n", "\r", "\t", ' '], '', $tableConfig['subpalettes']['enableGaran'])
        );
        self::assertArrayNotHasKey('save_callback', $tableConfig['fields']['enableGaran']);
        self::assertSame(
            [[self::BACKEND_CALLBACKS_CLASS, 'validateProductGuaranteeDurationMonths']],
            $tableConfig['fields']['guaranteeDurationMonths']['save_callback']
        );
        self::assertArrayNotHasKey('load_callback', $tableConfig['fields']['guaranteeDurationMonths']);
        self::assertArrayNotHasKey('save_callback', $tableConfig['fields']['guaranteeBrand']);
        self::assertSame(
            [[self::BACKEND_CALLBACKS_CLASS, 'loadProductGuaranteeBrand']],
            $tableConfig['fields']['guaranteeBrand']['load_callback']
        );
        self::assertArrayNotHasKey('save_callback', $tableConfig['fields']['guaranteeModelIdentifier']);
        self::assertArrayNotHasKey('maxlength', $tableConfig['fields']['guaranteeBrand']['eval']);
        self::assertArrayNotHasKey('maxlength', $tableConfig['fields']['guaranteeModelIdentifier']['eval']);
        self::assertSame("varchar(255) NOT NULL default ''", $tableConfig['fields']['guaranteeBrand']['sql']);
        self::assertSame("varchar(255) NOT NULL default ''", $tableConfig['fields']['guaranteeModelIdentifier']['sql']);
        self::assertSame(self::BACKEND_CALLBACKS_CLASS, $tableConfig['config']['onsubmit_callback'][0][0]);
        self::assertSame('addProductSubmitInfoMessages', $tableConfig['config']['onsubmit_callback'][0][1]);
        self::assertSame('saveLastBackendDataChangeTimestamp', $tableConfig['config']['onsubmit_callback'][1][1]);
        self::assertTrue($tableConfig['fields']['enableGaran']['eval']['submitOnChange']);
        self::assertArrayNotHasKey('attributes_callback', $tableConfig['fields']['enableGaran']);
        self::assertSame(
            [[self::BACKEND_CALLBACKS_CLASS, 'addProductEditInfoMessage']],
            $tableConfig['config']['onload_callback']
        );
        self::assertArrayNotHasKey('oncreate_callback', $tableConfig['config']);
        self::assertSame(
            [[self::BACKEND_CALLBACKS_CLASS, 'handleProductBeforeSubmit']],
            $tableConfig['config']['onbeforesubmit_callback']
        );
        self::assertSame(
            [[self::BACKEND_CALLBACKS_CLASS, 'handleProductGllModeSave']],
            $tableConfig['fields']['gllMode']['save_callback']
        );
    }

    /**
     * Die GLL/GARAN-Callbacks nach Implementierungsentscheidung 0025 und die
     * Feldprüfungen und Vorbelegungen aus 0015, sonst keine.
     */
    public function testDcaRegistersExactlyTheLegalGuaranteeCallbacks(): void
    {
        $this->loadProductDca();
        $this->loadVariantDca();

        self::assertSame(
            [
                'config.onsubmit_callback' => ['addProductSubmitInfoMessages'],
                'config.onload_callback' => ['addProductEditInfoMessage'],
                'config.onbeforesubmit_callback' => ['handleProductBeforeSubmit'],
                'fields.gllMode.save_callback' => ['handleProductGllModeSave'],
                'fields.guaranteeDurationMonths.save_callback' => ['validateProductGuaranteeDurationMonths'],
                'fields.guaranteeBrand.load_callback' => ['loadProductGuaranteeBrand'],
            ],
            $this->collectBackendCallbacks($GLOBALS['TL_DCA']['tl_ls_shop_product'])
        );
        self::assertSame(
            [
                'config.onload_callback' => ['addVariantEditInfoMessage'],
                'fields.garanMode.save_callback' => ['validateVariantGaranMode'],
                'fields.guaranteeDurationMonths.save_callback' => ['validateVariantGuaranteeDurationMonths'],
                'fields.guaranteeBrand.load_callback' => ['loadVariantGuaranteeBrand'],
            ],
            $this->collectBackendCallbacks($GLOBALS['TL_DCA']['tl_ls_shop_variant'])
        );
        self::assertContains(
            ['Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp'],
            $GLOBALS['TL_DCA']['tl_ls_shop_product']['config']['onsubmit_callback']
        );
        self::assertContains(
            ['Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp'],
            $GLOBALS['TL_DCA']['tl_ls_shop_variant']['config']['onsubmit_callback']
        );
        self::assertArrayNotHasKey('oncreate_callback', $GLOBALS['TL_DCA']['tl_ls_shop_variant']['config']);
    }

    public function testBackendCallbacksDoNotReadEnableGll(): void
    {
        $backendCallbacksSource = (string) file_get_contents(self::BACKEND_CALLBACKS_PATH);

        self::assertStringNotContainsString('enableGll', $backendCallbacksSource);
        self::assertStringNotContainsString('validateProductEnableGll', $backendCallbacksSource);
        self::assertStringNotContainsString('validateProductEnableGaran', $backendCallbacksSource);
        self::assertStringNotContainsString("Input::post('gllMode", $backendCallbacksSource);
        self::assertStringNotContainsString('getGroupSettings4User', $backendCallbacksSource);
    }

    public function testProductDcaContainsGllModeSelectWithReferenceDefaultSqlAndFilter(): void
    {
        $GLOBALS['TL_LANG']['tl_ls_shop_product']['gllModeOptions'] = null;
        $this->loadProductDca();

        $gllModeConfig = $GLOBALS['TL_DCA']['tl_ls_shop_product']['fields']['gllMode'];

        self::assertSame('select', $gllModeConfig['inputType']);
        self::assertSame(['inherit', 'always', 'never'], $gllModeConfig['options']);
        self::assertSame('inherit', $gllModeConfig['default']);
        self::assertSame("varchar(16) NOT NULL default 'inherit'", $gllModeConfig['sql']);
        self::assertTrue($gllModeConfig['filter']);
        self::assertTrue($gllModeConfig['exclude']);
        self::assertArrayNotHasKey('includeBlankOption', $gllModeConfig['eval']);
        self::assertArrayNotHasKey('merconis_multilanguage', $gllModeConfig['eval']);
        // Hilfe-Assistent und halbe Breite nach Implementierungsentscheidung 0027.
        self::assertTrue($gllModeConfig['eval']['helpwizard']);
        self::assertArrayNotHasKey('explanation', $gllModeConfig);
        $gllModeCssClasses = explode(' ', $gllModeConfig['eval']['tl_class']);
        self::assertContains('w50', $gllModeCssClasses);
        self::assertContains('clr', $gllModeCssClasses);

        $GLOBALS['TL_LANG']['tl_ls_shop_product']['gllModeOptions'] = ['inherit' => 'Reference check'];

        self::assertSame(['inherit' => 'Reference check'], $gllModeConfig['reference']);

        unset($GLOBALS['TL_LANG']['tl_ls_shop_product']['gllModeOptions']);
    }

    public function testVariantDcaContainsOverrideSelectorAndGuaranteeSubpalette(): void
    {
        $this->loadVariantDca();

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_variant'];

        self::assertContains('garanMode', $tableConfig['palettes']['__selector__']);
        self::assertStringContainsString('garanMode', $tableConfig['palettes']['default']);
        self::assertStringNotContainsString('enableGaran', $tableConfig['palettes']['default']);
        self::assertStringNotContainsString('guaranteeDurationMonths', $tableConfig['palettes']['default']);
        self::assertStringNotContainsString('guaranteeBrand', $tableConfig['palettes']['default']);
        self::assertStringNotContainsString('guaranteeModelIdentifier', $tableConfig['palettes']['default']);
        self::assertSame(
            'guaranteeDurationMonths,guaranteeBrand,guaranteeModelIdentifier',
            str_replace(["\n", "\r", "\t", ' '], '', $tableConfig['subpalettes']['garanMode_override'])
        );
        self::assertSame('select', $tableConfig['fields']['garanMode']['inputType']);
        self::assertSame('inherit', $tableConfig['fields']['garanMode']['default']);
        self::assertSame("varchar(16) NOT NULL default 'inherit'", $tableConfig['fields']['garanMode']['sql']);
        self::assertTrue($tableConfig['fields']['garanMode']['eval']['submitOnChange']);
        self::assertSame("int(10) unsigned NULL", $tableConfig['fields']['guaranteeDurationMonths']['sql']);
        self::assertSame('natural', $tableConfig['fields']['guaranteeDurationMonths']['eval']['rgxp']);
        self::assertTrue($tableConfig['fields']['guaranteeDurationMonths']['eval']['mandatory']);
        self::assertTrue($tableConfig['fields']['guaranteeBrand']['eval']['mandatory']);
        self::assertTrue($tableConfig['fields']['guaranteeModelIdentifier']['eval']['mandatory']);
        self::assertSame(
            [[self::BACKEND_CALLBACKS_CLASS, 'validateVariantGaranMode']],
            $tableConfig['fields']['garanMode']['save_callback']
        );
        self::assertSame(
            [[self::BACKEND_CALLBACKS_CLASS, 'validateVariantGuaranteeDurationMonths']],
            $tableConfig['fields']['guaranteeDurationMonths']['save_callback']
        );
        self::assertArrayNotHasKey('load_callback', $tableConfig['fields']['guaranteeDurationMonths']);
        self::assertArrayNotHasKey('save_callback', $tableConfig['fields']['guaranteeBrand']);
        self::assertSame(
            [[self::BACKEND_CALLBACKS_CLASS, 'loadVariantGuaranteeBrand']],
            $tableConfig['fields']['guaranteeBrand']['load_callback']
        );
        self::assertArrayNotHasKey('save_callback', $tableConfig['fields']['guaranteeModelIdentifier']);
        self::assertSame(
            'saveLastBackendDataChangeTimestamp',
            $tableConfig['config']['onsubmit_callback'][0][1]
        );
        self::assertArrayNotHasKey('garanOverride', $tableConfig['fields']);
        self::assertArrayNotHasKey('enableGaran', $tableConfig['fields']);
        self::assertArrayNotHasKey('attributes_callback', $tableConfig['fields']['garanMode']);
        self::assertSame(
            [[self::BACKEND_CALLBACKS_CLASS, 'addVariantEditInfoMessage']],
            $tableConfig['config']['onload_callback']
        );
    }

    public function testLanguageFilesContainGuaranteeLabelsAndMessagesInGermanAndEnglish(): void
    {
        $productLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_ls_shop_product.php');
        $productLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_ls_shop_product.php');
        $variantLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_ls_shop_variant.php');
        $variantLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_ls_shop_variant.php');

        self::assertStringContainsString("['gllMode']", $productLanguageDe);
        self::assertStringContainsString("['gllMode']", $productLanguageEn);
        self::assertStringNotContainsString("['enableGll']", $productLanguageDe);
        self::assertStringNotContainsString("['enableGll']", $productLanguageEn);
        self::assertStringContainsString("['garanMode']", $variantLanguageDe);
        self::assertStringContainsString("['garanMode']", $variantLanguageEn);
        self::assertStringContainsString("['lsShopGuaranteeLabels_legend']", $productLanguageDe);
        self::assertStringContainsString("['lsShopGuaranteeLabels_legend']", $variantLanguageDe);
        self::assertStringContainsString("['guaranteeValidationMessages']", $productLanguageDe);
        self::assertStringContainsString("['guaranteeValidationMessages']", $variantLanguageEn);
        self::assertStringNotContainsString('enableGaranActivationRequiresStoredGllHint', $productLanguageDe);
        self::assertStringNotContainsString('enableGaranActivationRequiresStoredGllHint', $productLanguageEn);
        self::assertStringNotContainsString('garanOverrideRequiresStoredGllHint', $variantLanguageDe);
        self::assertStringNotContainsString('garanOverrideRequiresStoredGllHint', $variantLanguageEn);
        self::assertStringContainsString('garan_disabled_invalid_duration', $productLanguageDe);
        self::assertStringContainsString('garan_invalid_mode', $variantLanguageDe);
        self::assertStringContainsString('garan_disabled_invalid_duration', $variantLanguageEn);

        // Import und Product Management API lesen ihre Meldungen aus `default` (`legalGuaranteeImport`).
        foreach ([$productLanguageDe, $productLanguageEn, $variantLanguageDe, $variantLanguageEn] as $languageFileContents) {
            foreach (['garan_disabled_gll_required', 'garan_disabled_missing_brand', 'garan_disabled_missing_model', 'garan_disabled_missing_duration'] as $importMessageCode) {
                self::assertStringNotContainsString($importMessageCode, $languageFileContents);
            }
        }
        self::assertStringContainsString('garanModeOptions', $variantLanguageEn);

        foreach ([$productLanguageDe, $productLanguageEn, $variantLanguageDe, $variantLanguageEn] as $languageFileContents) {
            foreach (self::FORMER_LEGAL_GUARANTEE_RULE_LANGUAGE_KEYS as $formerLanguageKey) {
                self::assertStringNotContainsString($formerLanguageKey, $languageFileContents);
            }
        }
        self::assertStringContainsString('Garantiedauer in Monaten', $productLanguageDe);
        self::assertStringContainsString('Brand/Trademark', $productLanguageDe);
        self::assertStringContainsString('Model identifier', $productLanguageEn);
        self::assertStringNotContainsString('Maximal 35 Zeichen.', $productLanguageDe);
        self::assertStringNotContainsString('Maximal 11 Zeichen.', $variantLanguageDe);
        self::assertStringNotContainsString('Maximum 35 characters.', $productLanguageEn);
        self::assertStringNotContainsString('Maximum 11 characters.', $variantLanguageEn);
        self::assertStringNotContainsString('garan_disabled_brand_too_long', $productLanguageDe);
        self::assertStringNotContainsString('garan_disabled_model_too_long', $variantLanguageEn);
        self::assertStringNotContainsString('Garantiedauer in Monaten *', $productLanguageDe);
        self::assertStringNotContainsString('Brand/Trademark *', $productLanguageDe);
        self::assertStringNotContainsString('Model identifier *', $productLanguageEn);
        self::assertStringContainsString('Guarantee duration in months', $variantLanguageEn);
        self::assertStringNotContainsString('Guarantee duration in months *', $variantLanguageEn);
        // Feldtitel nach Spec 27, Abschnitt 3.1: Deutsch mit Klammerzusatz, Englisch unverändert.
        $this->assertGuaranteeFieldLabelAndDescription(
            $productLanguageDe,
            'guaranteeBrand',
            'Marke (Brand/Trademark)',
            self::GUARANTEE_BRAND_DESCRIPTION_DE
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $productLanguageEn,
            'guaranteeBrand',
            'Brand/Trademark',
            self::GUARANTEE_BRAND_DESCRIPTION_EN
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $variantLanguageDe,
            'guaranteeBrand',
            'Marke (Brand/Trademark)',
            self::GUARANTEE_BRAND_DESCRIPTION_DE
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $variantLanguageEn,
            'guaranteeBrand',
            'Brand/Trademark',
            self::GUARANTEE_BRAND_DESCRIPTION_EN
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $productLanguageDe,
            'guaranteeModelIdentifier',
            'Modell (Model identifier)',
            self::GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_DE
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $productLanguageEn,
            'guaranteeModelIdentifier',
            'Model identifier',
            self::GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_EN
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $variantLanguageDe,
            'guaranteeModelIdentifier',
            'Modell (Model identifier)',
            self::GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_DE
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $variantLanguageEn,
            'guaranteeModelIdentifier',
            'Model identifier',
            self::GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_EN
        );
        foreach (
            [
                [$productLanguageDe, 'garan_disabled_invalid_duration'],
                [$productLanguageEn, 'garan_disabled_invalid_duration'],
                [$variantLanguageDe, 'garan_invalid_mode'],
                [$variantLanguageEn, 'garan_invalid_mode'],
            ] as [$languageFileContents, $messageCode]
        ) {
            self::assertStringNotContainsString(
                '`',
                $this->extractGuaranteeValidationMessage($languageFileContents, $messageCode)
            );
        }
    }

    public function testServicesRegisterGuaranteeValidationWithoutFormerGllMigrations(): void
    {
        $servicesContents = (string) file_get_contents(self::SERVICES_PATH);

        self::assertStringContainsString(
            'LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator',
            $servicesContents
        );
        self::assertStringContainsString(
            'LegalGuarantee\ProductData\LegalGuaranteeBackendCallbacks',
            $servicesContents
        );
        self::assertStringContainsString(
            'LegalGuarantee\MemberGroup\StandardMemberGroupGllMode: ~',
            $servicesContents
        );
        self::assertStringNotContainsString(self::FORMER_GLL_MIGRATION_MANAGER_CLASS, $servicesContents);
        self::assertFileDoesNotExist(self::FORMER_GLL_MIGRATION_MANAGER_PATH);
        self::assertStringContainsString(
            "LeadingSystems\\MerconisBundle\\LegalGuarantee\\MemberGroup\\MemberGroupGllModeInspector:\n    public: true",
            $servicesContents
        );
        self::assertStringNotContainsString(
            'merconis.migration.enable_gll_default_migration',
            $servicesContents
        );
        self::assertFileDoesNotExist(self::MIGRATION_PATH);
    }

    public function testGuaranteeDcaFilesDoNotReadBrandOrModelFromRequest(): void
    {
        $productDca = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_product.php');
        $variantDca = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_variant.php');

        self::assertStringNotContainsString("Input::post('guaranteeBrand", $productDca);
        self::assertStringNotContainsString("Input::post('guaranteeModelIdentifier", $productDca);
        self::assertStringNotContainsString("Input::post('guaranteeBrand", $variantDca);
        self::assertStringNotContainsString("Input::post('guaranteeModelIdentifier", $variantDca);
    }

    public function testSettingsDcaContainsTruncationLengthWithoutFormerGllMigrationControl(): void
    {
        $settingsDca = (string) file_get_contents(self::SETTINGS_DCA_PATH);
        $defaultLanguageDe = (string) file_get_contents(self::DEFAULT_LANGUAGE_DE_PATH);
        $defaultLanguageEn = (string) file_get_contents(self::DEFAULT_LANGUAGE_EN_PATH);
        $settingsLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_lsShopSettings.php');
        $settingsLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_lsShopSettings.php');
        $miscBackendStyles = (string) file_get_contents(self::MISC_BACKEND_STYLES_PATH);

        self::assertStringContainsString('{legalGuarantee_legend},garanLabelTruncationLength;', $settingsDca);
        self::assertStringContainsString("'garanLabelTruncationLength' => array(", $settingsDca);
        self::assertStringNotContainsString(self::FORMER_GLL_MIGRATION_CONTROL_FIELD, $settingsDca);
        self::assertStringNotContainsString(self::FORMER_GLL_MIGRATION_MANAGER_CLASS, $settingsDca);
        self::assertStringNotContainsString('applyEnableGllMigrationRequest', $settingsDca);
        self::assertStringNotContainsString('getEnableGllMigrationControlMarkup', $settingsDca);
        self::assertStringNotContainsString(self::FORMER_GLL_MIGRATION_FLAG, $settingsDca);
        self::assertStringNotContainsString('tl_ls_shop_product', $settingsDca);
        self::assertStringNotContainsString('tl_ls_shop_variant', $settingsDca);
        self::assertStringNotContainsString(self::FORMER_GLL_MIGRATION_CONTROL_FIELD, $miscBackendStyles);
        self::assertStringContainsString("['legalGuarantee_legend']", $settingsLanguageDe);
        self::assertStringNotContainsString(self::FORMER_GLL_MIGRATION_CONTROL_FIELD, $settingsLanguageDe);
        self::assertStringContainsString("['garanLabelTruncationLength']", $settingsLanguageDe);
        self::assertStringNotContainsString(self::FORMER_GLL_MIGRATION_CONTROL_FIELD, $settingsLanguageEn);
        self::assertStringContainsString("['garanLabelTruncationLength']", $settingsLanguageEn);
        self::assertStringContainsString('Gewährleistung und Herstellergarantie (GARAN-Label)', $settingsLanguageDe);
        self::assertStringContainsString('Legal guarantee and manufacturer guarantee (GARAN label)', $settingsLanguageEn);
        self::assertStringContainsString("['dashboard']['gllAnnouncement']", $defaultLanguageDe);
        self::assertStringContainsString("['dashboard']['gllAnnouncement']", $defaultLanguageEn);
    }

    private function loadProductDca(): void
    {
        $loader = new class {
            public function getTemplateGroup(string $prefix): array
            {
                return [];
            }

            public function requireFile(string $path): void
            {
                require $path;
            }
        };

        $loader->requireFile(self::DCA_BASE_PATH . 'tl_ls_shop_product.php');
    }

    /**
     * Die Varianten-DCA deklariert eine Klasse und lässt sich pro Prozess nur einmal laden.
     */
    private function loadVariantDca(): void
    {
        if (self::$loadedVariantDca === null) {
            require self::DCA_BASE_PATH . 'tl_ls_shop_variant.php';
            self::$loadedVariantDca = $GLOBALS['TL_DCA']['tl_ls_shop_variant'];
        }

        $GLOBALS['TL_DCA']['tl_ls_shop_variant'] = self::$loadedVariantDca;
    }

    /**
     * @param array<string, mixed> $tableConfig
     *
     * @return array<string, list<string>>
     */
    private function collectBackendCallbacks(array $tableConfig): array
    {
        $callbackMethodsByLocation = [];

        foreach ($tableConfig['config'] as $configKey => $configValue) {
            if (str_ends_with((string) $configKey, '_callback') && is_array($configValue)) {
                $this->addBackendCallbackMethods($callbackMethodsByLocation, 'config.' . $configKey, $configValue);
            }
        }

        foreach ($tableConfig['fields'] as $fieldName => $fieldConfig) {
            foreach ($fieldConfig as $fieldConfigKey => $fieldConfigValue) {
                if (str_ends_with((string) $fieldConfigKey, '_callback') && is_array($fieldConfigValue)) {
                    $this->addBackendCallbackMethods(
                        $callbackMethodsByLocation,
                        'fields.' . $fieldName . '.' . $fieldConfigKey,
                        $fieldConfigValue
                    );
                }
            }
        }

        return $callbackMethodsByLocation;
    }

    /**
     * @param array<string, list<string>> $callbackMethodsByLocation
     * @param array<mixed> $callbacks
     */
    private function addBackendCallbackMethods(array &$callbackMethodsByLocation, string $location, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            if (is_array($callback) && ($callback[0] ?? null) === self::BACKEND_CALLBACKS_CLASS) {
                $callbackMethodsByLocation[$location][] = (string) $callback[1];
            }
        }
    }

    private function extractGuaranteeValidationMessage(string $languageFileContents, string $messageCode): string
    {
        $pattern = "/'" . preg_quote($messageCode, '/') . "' => '((?:\\\\'|[^'])*)'/";

        self::assertSame(1, preg_match($pattern, $languageFileContents, $matches));

        return str_replace("\\'", "'", $matches[1]);
    }


    private function assertGuaranteeFieldLabelAndDescription(
        string $languageFileContents,
        string $fieldName,
        string $expectedLabel,
        string $expectedDescription
    ): void {
        $pattern = "/\\['" . preg_quote($fieldName, '/') . "'\\]\\s*=\\s*(?:array\\(|\\[)\\s*'((?:\\\\'|[^'])*)'\\s*,\\s*'((?:\\\\'|[^'])*)'/";

        self::assertSame(1, preg_match($pattern, $languageFileContents, $matches));

        $actualLabel = str_replace("\\'", "'", $matches[1]);
        $actualDescription = str_replace("\\'", "'", $matches[2]);

        self::assertSame($expectedLabel, $actualLabel);
        self::assertSame($expectedDescription, $actualDescription);
        self::assertStringNotContainsString('‘', $actualDescription);
        self::assertStringNotContainsString('’', $actualDescription);
        self::assertStringNotContainsString('“', $actualDescription);
        self::assertStringNotContainsString('”', $actualDescription);
    }
}
